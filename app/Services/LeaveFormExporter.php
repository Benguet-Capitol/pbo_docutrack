<?php

namespace App\Services;

use App\Models\Leave;
use Illuminate\Support\Carbon;
use RuntimeException;
use ZipArchive;

/**
 * Fills the official CS Form No. 6 (Revised 2020) Application for Leave workbook
 * (resources/templates/cs-form-6-application-for-leave.xlsx) with a leave's details.
 *
 * The template is edited in place (cell XML and checkbox state) instead of being re-saved by a
 * spreadsheet library, because those drop the form's checkboxes and the embedded instructions page.
 */
class LeaveFormExporter
{
    public const TEMPLATE = 'templates/cs-form-6-application-for-leave.xlsx';

    private const SHEET = 'xl/worksheets/sheet1.xml';
    private const SHEET_RELS = 'xl/worksheets/_rels/sheet1.xml.rels';
    private const VML = 'xl/drawings/vmlDrawing1.vml';

    /** Checkbox shape ids in the template, by what they mean */
    private const LEAVE_TYPE_BOXES = [
        'Vacation Leave' => 1029,
        'Mandatory/Forced Leave' => 1027,
        'Sick Leave' => 1028,
        'Maternity Leave' => 1030,
        'Paternity Leave' => 1031,
        'Special Privilege Leave' => 1032,
        'Solo Parent Leave' => 1033,
        'Study Leave' => 1034,
        '10-Day VAWC Leave' => 1035,
        'Rehabilitation Leave' => 1036,
        'Special Leave Benefits for Women' => 1037,
        'Special Emergency (Calamity) Leave' => 1038,
        'Adoption Leave' => 1039,
    ];
    private const BOX_WITHIN_PHILIPPINES = 1040;
    private const BOX_ABROAD = 1041;
    private const BOX_IN_HOSPITAL = 1042;
    private const BOX_OUT_PATIENT = 1043;
    private const BOX_MASTERS = 1044;
    private const BOX_BAR = 1045;
    private const BOX_MONETIZATION = 1046;
    private const BOX_TERMINAL = 1047;
    private const BOX_COMMUTATION_NOT_REQUESTED = 1048;

    /** Leave types whose 6.B location details apply */
    private const LOCATION_TYPES = ['Vacation Leave', 'Special Privilege Leave', 'Wellness Leave'];

    /**
     * Build the filled workbook and return its path (a temp file the caller should delete after sending).
     */
    public function export(Leave $leave): string
    {
        $template = resource_path(self::TEMPLATE);
        if (!is_file($template)) {
            throw new RuntimeException('Leave form template not found.');
        }

        // Unique name without tempnam(), which would leave an empty placeholder file behind
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'leave-form-' . \Illuminate\Support\Str::uuid() . '.xlsx';
        copy($template, $path);

        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException('Could not open the leave form template.');
        }

        $leave->loadMissing('employee.office');
        $fields = $this->fields($leave);

        // Cell text
        $sheet = $zip->getFromName(self::SHEET);
        foreach ($fields['cells'] as $ref => $text) {
            $sheet = $this->setCellText($sheet, $ref, $text);
        }
        $zip->addFromString(self::SHEET, $sheet);

        // Checkboxes: set every box, so leftovers saved in the template are cleared
        $checked = $fields['checked'];
        $controls = $this->checkboxControls($zip, $sheet);
        $vml = $zip->getFromName(self::VML);
        foreach ($controls as $shapeId => $ctrlPropPath) {
            $isChecked = in_array($shapeId, $checked, true);
            $zip->addFromString($ctrlPropPath, $this->setCtrlPropChecked($zip->getFromName($ctrlPropPath), $isChecked));
            $vml = $this->setVmlChecked($vml, $shapeId, $isChecked);
        }
        $zip->addFromString(self::VML, $vml);

        $zip->close();

        return $path;
    }

    /** Download name, e.g. "Leave Form - LV-2026-03-0004 - Atayoc.xlsx" */
    public function filename(Leave $leave): string
    {
        $name = $this->splitName($leave->employee?->name ?? '');
        $safe = preg_replace('/[^A-Za-z0-9\- ]+/', '', trim("{$leave->control_no} - {$name['last']}"));

        return "Leave Form - {$safe}.xlsx";
    }

    /**
     * Cell values and checked boxes for a leave.
     */
    private function fields(Leave $leave): array
    {
        $employee = $leave->employee;
        $name = $this->splitName($employee?->name ?? '');
        $type = $leave->type_of_leave;
        $checked = [self::BOX_COMMUTATION_NOT_REQUESTED];

        $cells = [
            'B5' => $employee?->office?->office_name ?? '',
            'E5' => sprintf('%-30s %-30s %s', mb_strtoupper($name['last']), mb_strtoupper($name['first']), mb_strtoupper($name['middle'])),
            'A6' => '3.   DATE OF FILING  ' . Carbon::parse($leave->date_of_filing)->format('F j, Y'),
            'E6' => '4.   POSITION  ' . ($employee?->full_position ?: '_____________________________') . '   5.  SALARY  _______________',
            'B44' => $this->daysText($leave),
            'B47' => $this->inclusiveDatesText($leave->inclusive_dates ?? []),
        ];

        // 6.A Type of leave (types not on the 2020 form go on the "Others" line)
        if (isset(self::LEAVE_TYPE_BOXES[$type])) {
            $checked[] = self::LEAVE_TYPE_BOXES[$type];
        } else {
            $cells['B40'] = $type === 'Others' ? ($leave->other_type ?: 'Others') : $type;
        }

        // 6.B Details of leave
        if (in_array($type, self::LOCATION_TYPES, true) && $leave->within_philippines !== null) {
            $where = $leave->purpose ?: '';
            if ($leave->within_philippines) {
                $checked[] = self::BOX_WITHIN_PHILIPPINES;
                $cells['I13'] = 'Within the Philippines ' . $where;
            } else {
                $checked[] = self::BOX_ABROAD;
                $cells['I15'] = 'Abroad (Specify) ' . $where;
            }
        }
        if ($type === 'Sick Leave' && $leave->in_hospital !== null) {
            if ($leave->in_hospital) {
                $checked[] = self::BOX_IN_HOSPITAL;
                $cells['I19'] = 'In Hospital (Specify Illness) ' . ($leave->illness ?? '');
            } else {
                $checked[] = self::BOX_OUT_PATIENT;
                $cells['I21'] = 'Out Patient (Specify Illness) ' . ($leave->illness ?? '');
            }
        }
        if ($type === 'Special Leave Benefits for Women' && $leave->illness) {
            $cells['H27'] = '(Specify Illness) ' . $leave->illness;
        }
        if ($type === 'Study Leave') {
            if ($leave->completion_type === "Completion of Master's Degree") $checked[] = self::BOX_MASTERS;
            if ($leave->completion_type === 'BAR/Board Examination Review') $checked[] = self::BOX_BAR;
        }
        if ($type === 'Others') {
            if ($leave->other_type === 'Monetization of Leave Credits') $checked[] = self::BOX_MONETIZATION;
            if ($leave->other_type === 'Terminal Leave') $checked[] = self::BOX_TERMINAL;
        }

        return ['cells' => $cells, 'checked' => $checked];
    }

    /**
     * Split "Juan D. Dela Cruz Jr." into last ("Dela Cruz Jr."), first ("Juan") and middle ("D.").
     * The middle name is the single initial before the last name, when present.
     */
    public function splitName(string $fullName): array
    {
        $tokens = preg_split('/\s+/', trim($fullName), -1, PREG_SPLIT_NO_EMPTY);
        $suffix = '';
        if ($tokens && preg_match('/^(jr\.?|sr\.?|ii|iii|iv|v)$/i', end($tokens))) {
            $suffix = ' ' . array_pop($tokens);
        }
        if (count($tokens) < 2) {
            return ['last' => trim(($tokens[0] ?? '') . $suffix), 'first' => '', 'middle' => ''];
        }

        $initialAt = null;
        foreach ($tokens as $i => $token) {
            if ($i > 0 && preg_match('/^[A-Za-z]\.?$/', $token)) $initialAt = $i;
        }
        if ($initialAt !== null && $initialAt < count($tokens) - 1) {
            return [
                'first' => implode(' ', array_slice($tokens, 0, $initialAt)),
                'middle' => $tokens[$initialAt],
                'last' => implode(' ', array_slice($tokens, $initialAt + 1)) . $suffix,
            ];
        }

        $last = array_pop($tokens);
        return ['first' => implode(' ', $tokens), 'middle' => '', 'last' => $last . $suffix];
    }

    /** "3 days", "1 day", "0.5 day (AM)" */
    private function daysText(Leave $leave): string
    {
        $days = Leave::daysCounted((bool) $leave->is_half_day, $leave->number_of_working_days_applied_for);
        $text = rtrim(rtrim(number_format($days, 1), '0'), '.') . ($days == 1 || $days < 1 ? ' day' : ' days');

        return $leave->is_half_day && $leave->half_day_period ? "{$text} ({$leave->half_day_period})" : $text;
    }

    /**
     * "January 5-9, 2026; February 2, 2026" from entries like "2026-01-05 - 2026-01-09" or "2026-02-02".
     */
    public function inclusiveDatesText(array $entries): string
    {
        $parts = [];
        foreach ($entries as $entry) {
            if (!$entry) continue;
            if (str_contains($entry, ' - ')) {
                [$from, $to] = array_map(fn ($d) => Carbon::parse(trim($d)), explode(' - ', $entry, 2));
                if ($from->isSameDay($to)) {
                    $parts[] = $from->format('F j, Y');
                } elseif ($from->isSameMonth($to)) {
                    $parts[] = $from->format('F j') . '-' . $to->format('j, Y');
                } elseif ($from->isSameYear($to)) {
                    $parts[] = $from->format('F j') . ' - ' . $to->format('F j, Y');
                } else {
                    $parts[] = $from->format('F j, Y') . ' - ' . $to->format('F j, Y');
                }
            } else {
                $parts[] = Carbon::parse(trim($entry))->format('F j, Y');
            }
        }

        return implode('; ', $parts);
    }

    /**
     * Replace a cell's content with an inline string, keeping its style (s="...").
     */
    private function setCellText(string $sheet, string $ref, string $text): string
    {
        $escaped = htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $pattern = '#<c r="' . $ref . '"((?:\s+[a-z]+="[^"]*")*?)\s*(?:/>|>.*?</c>)#s';

        if (!preg_match($pattern, $sheet, $match)) {
            throw new RuntimeException("Cell {$ref} not found in the leave form template.");
        }

        preg_match('/\ss="(\d+)"/', $match[1], $style);
        $styleAttr = $style ? ' s="' . $style[1] . '"' : '';
        $cell = '<c r="' . $ref . '"' . $styleAttr . ' t="inlineStr"><is><t xml:space="preserve">' . $escaped . '</t></is></c>';

        return preg_replace($pattern, str_replace(['\\', '$'], ['\\\\', '\\$'], $cell), $sheet, 1);
    }

    /**
     * Checkbox controls on the sheet: shape id => ctrlProp part path.
     */
    private function checkboxControls(ZipArchive $zip, string $sheet): array
    {
        $rels = simplexml_load_string($zip->getFromName(self::SHEET_RELS));
        $targets = [];
        foreach ($rels->Relationship as $rel) {
            $targets[(string) $rel['Id']] = 'xl/' . str_replace('../', '', (string) $rel['Target']);
        }

        preg_match_all('#<control\s[^>]*shapeId="(\d+)"[^>]*r:id="(rId\d+)"#', $sheet, $matches, PREG_SET_ORDER);
        $controls = [];
        foreach ($matches as [, $shapeId, $rid]) {
            if (isset($targets[$rid]) && str_contains($targets[$rid], 'ctrlProps/')) {
                $controls[(int) $shapeId] = $targets[$rid];
            }
        }

        return $controls;
    }

    private function setCtrlPropChecked(string $xml, bool $checked): string
    {
        $xml = preg_replace('/\schecked="[^"]*"/', '', $xml);

        return $checked ? str_replace('objectType="CheckBox"', 'objectType="CheckBox" checked="Checked"', $xml) : $xml;
    }

    /** Legacy VML copy of the checkbox state, which some Excel versions read instead */
    private function setVmlChecked(string $vml, int $shapeId, bool $checked): string
    {
        $pattern = '#(<v:shape\s[^>]*id="_x0000_s' . $shapeId . '".*?<x:ClientData ObjectType="Checkbox">)(.*?)(</x:ClientData>)#s';

        return preg_replace_callback($pattern, function ($m) use ($checked) {
            $data = preg_replace('#\s*<x:Checked>\d</x:Checked>#', '', $m[2]);
            if ($checked) {
                $data = rtrim($data) . "\n   <x:Checked>1</x:Checked>\n  ";
            }

            return $m[1] . $data . $m[3];
        }, $vml, 1);
    }
}
