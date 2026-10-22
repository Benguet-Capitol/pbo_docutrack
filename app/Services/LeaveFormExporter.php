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
    private const DRAWING = 'xl/drawings/drawing1.xml';
    private const SHARED_STRINGS = 'xl/sharedStrings.xml';

    /** Font size (pt) of the 6.C values: working days and inclusive dates */
    private const SECTION_6C_VALUE_SIZE = 11;

    /** Font size (pt) of every signatory's name and title (7.A, 7.B and the authorized official) */
    private const SIGNATORY_SIZE = 11;
    private const STYLES = 'xl/styles.xml';

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
        // Added to the template's 6.A by addWellnessCheckbox()
        'Wellness Leave' => self::BOX_WELLNESS,
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
    private const BOX_FOR_APPROVAL = 1050;
    private const BOX_WELLNESS = 1052;
    private const BOX_FOR_DISAPPROVAL = 1051;

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

        $this->addWellnessCheckbox($path);

        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException('Could not open the leave form template.');
        }

        $leave->loadMissing('employee.office');
        $fields = $this->fields($leave);

        // Cell text
        $sheet = $zip->getFromName(self::SHEET);
        foreach ($fields['cells'] as $ref => $text) {
            // Plain text, or rich text runs (label in normal type, value in bold like the print)
            $sheet = $this->ensureCell($sheet, $ref);
            $sheet = is_array($text) ? $this->setCellRich($sheet, $ref, $text) : $this->setCellText($sheet, $ref, $text);
        }
        // Row 37 (Wellness Leave) as tall as the other leave type rows
        $sheet = preg_replace('#(<row r="37"[^>]*\sht=")[\d.]+(")#', '${1}20.1${2}', $sheet, 1);
        $sheet = $this->fillSignatories($zip, $sheet, $leave, $fields['dates']);
        $zip->addFromString(self::SHEET, $this->longPaperPageSetup($sheet));

        // 6.A: the legal citations in smaller type, with a space before each, so every line fits its box
        $zip->addFromString(self::SHARED_STRINGS, $this->compactCitations($zip->getFromName(self::SHARED_STRINGS)));

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

        // The template carries text boxes from a filled-in sample form over the value cells: remove them
        $zip->addFromString(self::DRAWING, $this->withoutValueTextBoxes($zip->getFromName(self::DRAWING)));

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
     * Cell values and checked boxes for the leave's application: every leave type in it is checked, and 6.C lists
     * the total days and, when there are several types, each type's dates.
     */
    private function fields(Leave $leave): array
    {
        $rows = $leave->applicationRows();
        $employee = $leave->employee;
        $name = $this->splitName($employee?->name ?? '');
        $checked = [self::BOX_COMMUTATION_NOT_REQUESTED];

        $cells = [
            'B5' => mb_strtoupper($employee?->workOffice()?->office_name ?? ''),
            'E5' => [[$this->nameUnderLabels($name), true, 12]],
            'A6' => $this->labeled('3.  DATE OF FILING ', mb_strtoupper(Carbon::parse($leave->date_of_filing)->format('F j, Y')), 12),
            'E6' => array_merge(
                $this->labeled('4.  POSITION ', mb_strtoupper((string) $employee?->designation), 12, '_____________________________'),
                $this->labeled('  5.  SALARY ', $leave->salary !== null ? number_format((float) $leave->salary, 2) : '', 12),
            ),
            'B44' => [[$this->applicationDaysText($rows), true, self::SECTION_6C_VALUE_SIZE]],
        ];

        $others = [];
        $purposes = ['I13' => [], 'I15' => []];
        foreach ($rows as $row) {
            $type = $row->type_of_leave;

            // 6.A Type of leave (types not on the 2020 form go on the "Others" line)
            if (isset(self::LEAVE_TYPE_BOXES[$type])) {
                $checked[] = self::LEAVE_TYPE_BOXES[$type];
            } else {
                $others[] = $this->typeName($row);
            }

            // 6.B Details of leave
            if (in_array($type, self::LOCATION_TYPES, true) && $row->within_philippines !== null) {
                $checked[] = $row->within_philippines ? self::BOX_WITHIN_PHILIPPINES : self::BOX_ABROAD;
                if (trim((string) $row->purpose) !== '') {
                    $located = $rows->filter(fn (Leave $other) => in_array($other->type_of_leave, self::LOCATION_TYPES, true))->count();
                    $purposes[$row->within_philippines ? 'I13' : 'I15'][] = $located > 1 ? "{$row->purpose} ({$this->typeShort($row)})" : $row->purpose;
                }
            }
            if ($type === 'Sick Leave' && $row->in_hospital !== null) {
                if ($row->in_hospital) {
                    $checked[] = self::BOX_IN_HOSPITAL;
                    $cells['I19'] = $this->labeled('In Hospital (Specify Illness) ', (string) $row->illness, 11);
                } else {
                    $checked[] = self::BOX_OUT_PATIENT;
                    $cells['I21'] = $this->labeled('Out Patient (Specify Illness) ', (string) $row->illness, 11);
                }
            }
            if ($type === 'Special Leave Benefits for Women' && $row->illness) {
                $cells['H27'] = $this->labeled('(Specify Illness) ', (string) $row->illness, 11);
            }
            if ($type === 'Study Leave') {
                if ($row->completion_type === "Completion of Master's Degree") $checked[] = self::BOX_MASTERS;
                if ($row->completion_type === 'BAR/Board Examination Review') $checked[] = self::BOX_BAR;
            }
            if ($type === 'Others') {
                if ($row->other_type === 'Monetization of Leave Credits') $checked[] = self::BOX_MONETIZATION;
                if ($row->other_type === 'Terminal Leave') $checked[] = self::BOX_TERMINAL;
            }
        }
        if ($others) {
            $cells['B40'] = [[implode(', ', $others), true, 12]];
        }
        // Wellness Leave: not on the 2020 form, so it gets its own line (and checkbox) in the blank row under Adoption Leave
        $cells['C37'] = [['Wellness Leave', false, 12], [' (CSC MC No. 3, s. 2025)', false, 7, false, 'Arial Narrow']];
        // 7.B Recommendation
        if ($leave->recommendation === 'approval') {
            $checked[] = self::BOX_FOR_APPROVAL;
        } elseif ($leave->recommendation === 'disapproval') {
            $checked[] = self::BOX_FOR_DISAPPROVAL;
            // The reason itself is written on the line below (I55, see fillSignatories)
            $cells['I54'] = 'For disapproval due to';
        }
        // 6.B: purposes of every Vacation/Special Privilege/Wellness leave in the application
        if (in_array(self::BOX_WITHIN_PHILIPPINES, $checked, true)) {
            $cells['I13'] = $this->labeled('Within the Philippines ', implode('; ', $purposes['I13']), 11);
        }
        if (in_array(self::BOX_ABROAD, $checked, true)) {
            $cells['I15'] = $this->labeled('Abroad (Specify) ', implode('; ', $purposes['I15']), 11);
        }

        $dates = $rows->count() === 1
            ? $this->inclusiveDatesText($rows->first()->inclusive_dates ?? [])
            : $rows->map(fn (Leave $row) => $this->typeShort($row) . ': ' . $this->inclusiveDatesText($row->inclusive_dates ?? []) . ' (' . $this->daysText($row) . ')')->implode("\n");
        $offDays = $rows->map(fn (Leave $row) => self::offDaysText($row->off_days ?? []))->unique()->implode('; ');

        return ['cells' => $cells, 'checked' => array_values(array_unique($checked)), 'dates' => [$dates, $offDays]];
    }

    /**
     * A label and its value as rich text runs: the label in normal type and the value in bold; with no value,
     * the label is followed by the blank line.
     */
    private function labeled(string $label, string $value, int $size, string $blank = '_______________', ?int $valueSize = null): array
    {
        $value = trim($value);

        return $value === ''
            ? [[$label . $blank, false, $size]]
            : [[$label, false, $size], [' ' . $value . ' ', true, $valueSize ?? $size - 1]];
    }

    /**
     * "2. NAME :  (Last)  (First)  (Middle)" is one cell with the labels spaced out, so the name (in capitals, as
     * printed) is padded with spaces to put each part under its label. Widths are Arial's, in ems.
     */
    private function nameUnderLabels(array $name): string
    {
        $label = '2.  NAME :            (Last)                               (First)                         (Middle)';
        $width = function (string $text, bool $bold): float {
            $total = 0.0;
            foreach (mb_str_split($text) as $char) {
                $total += match (true) {
                    in_array($char, [' ', '.', ',', ':', "'"], true) => 0.278,
                    in_array($char, ['(', ')', '-'], true) => 0.333,
                    ctype_upper($char) => $bold ? 0.722 : 0.667,
                    default => 0.556,
                };
            }
            return $total;
        };
        $line = '';
        foreach (['(Last)' => 'last', '(First)' => 'first', '(Middle)' => 'middle'] as $caption => $part) {
            $text = mb_strtoupper($name[$part] ?? '');
            if ($text === '') continue;
            $at = mb_strpos($label, $caption);
            $center = $width(mb_substr($label, 0, $at), false) + $width($caption, false) / 2;
            $start = $center - $width($text, true) / 2;
            $spaces = (int) round(($start - $width($line, true)) / 0.278);
            $line .= str_repeat(' ', max($line === '' ? 0 : 2, $spaces)) . $text;
        }

        return $line;
    }

    /** Long (folio) paper and margins, in points */
    private const PAGE_WIDTH = 8.5 * 72;
    private const PAGE_HEIGHT = 13 * 72;
    private const PAGE_MARGIN = 0.3 * 72;

    /** Printed rows (the template's print area is A1:I64) and the 7.A / 7.B signature space row */
    private const LAST_PRINTED_ROW = 64;
    private const SIGNATURE_SPACE_ROW = 58;
    private const MIN_SIGNATURE_SPACE = 45;

    /**
     * One long (folio, 8.5" x 13") page, all columns on it: the scale is the largest that fits the columns and the
     * rows, then the 7.A / 7.B signature space (row 58) takes up whatever height is left, so the form ends exactly
     * at the bottom margin.
     */
    private function longPaperPageSetup(string $sheet): string
    {
        // Column widths: Arial 10 (the workbook font) has a 7px digit, 96 px per inch
        $width = 0.0;
        if (preg_match('#<cols>(.*?)</cols>#s', $sheet, $cols)) {
            preg_match_all('#<col min="(\d+)" max="(\d+)" width="([\d.]+)"#', $cols[1], $list, PREG_SET_ORDER);
            foreach ($list as [, $min, $max, $w]) {
                for ($col = (int) $min; $col <= min((int) $max, 9); $col++) {
                    $width += floor((float) $w * 7 + 0.5) * 0.75;
                }
            }
        }

        // Row heights (points; rows without one are 15pt), the signature space at its minimum
        preg_match('#defaultRowHeight="([\d.]+)"#', $sheet, $default);
        $height = 0.0;
        for ($row = 1; $row <= self::LAST_PRINTED_ROW; $row++) {
            if ($row === self::SIGNATURE_SPACE_ROW) {
                $height += self::MIN_SIGNATURE_SPACE;
                continue;
            }
            preg_match('#<row r="' . $row . '"[^>]*>#', $sheet, $tag);
            if (str_contains($tag[0] ?? '', 'hidden="1"')) continue;
            $height += preg_match('#\sht="([\d.]+)"#', $tag[0] ?? '', $ht) ? (float) $ht[1] : (float) ($default[1] ?? 15);
        }

        $printWidth = self::PAGE_WIDTH - 2 * self::PAGE_MARGIN;
        $printHeight = self::PAGE_HEIGHT - 2 * self::PAGE_MARGIN;
        $scale = max(10, min(100, (int) floor(100 * min($printWidth / max($width, 1), $printHeight / max($height, 1)))));

        // The signature space fills the rest of the page (a point short, so nothing spills onto a second page)
        $space = self::MIN_SIGNATURE_SPACE + $printHeight / ($scale / 100) - $height - 1;
        $sheet = preg_replace('#(<row r="' . self::SIGNATURE_SPACE_ROW . '"[^>]*\sht=")[\d.]+(")#', '${1}' . round($space, 2) . '${2}', $sheet, 1);

        $margin = self::PAGE_MARGIN / 72;
        $sheet = str_replace('<pageSetUpPr fitToPage="1"/>', '<pageSetUpPr fitToPage="0"/>', $sheet);
        $sheet = preg_replace('#<pageMargins[^>]*/>#', '<pageMargins left="' . $margin . '" right="' . $margin . '" top="' . $margin . '" bottom="' . $margin . '" header="0" footer="0.1"/>', $sheet, 1);
        $sheet = preg_replace('#<pageSetup\b[^>]*?(r:id="[^"]*")?\s*/>#', '<pageSetup paperSize="14" scale="' . $scale . '" orientation="portrait" $1/>', $sheet, 1);
        if (!str_contains($sheet, '<printOptions')) {
            $sheet = str_replace('<pageMargins', '<printOptions horizontalCentered="1"/><pageMargins', $sheet);
        }

        // Footnote like the print's: "Generated on October 22, 2026 at 1:14 PM · PBO|DocuTrack" (small, gray, bottom left)
        $generated = 'Generated on ' . now()->format('F j, Y \a\t g:i A') . ' · PBO|DocuTrack';
        $footer = '<headerFooter alignWithMargins="0"><oddFooter>' . htmlspecialchars('&L&"Arial,Regular"&8&K808080' . $generated, ENT_XML1, 'UTF-8') . '</oddFooter></headerFooter>';
        $sheet = preg_match('#<headerFooter\b[^>]*/>|<headerFooter\b.*?</headerFooter>#s', $sheet)
            ? preg_replace('#<headerFooter\b[^>]*/>|<headerFooter\b.*?</headerFooter>#s', $footer, $sheet, 1)
            : preg_replace('#(<pageSetup\b[^>]*/>)#', '$1' . $footer, $sheet, 1);

        return $sheet;
    }

    /** 6.A labels: citations "(Sec. 51, ...)" at 7pt (were 8pt) with a space before them */
    private function compactCitations(string $strings): string
    {
        return preg_replace_callback('#<si>(.*?)</si>#s', function ($si) {
            if (!preg_match('#<sz val="8"/><rFont val="Arial Narrow"/>.*?<t[^>]*>\(#s', $si[1])) {
                return $si[0];
            }
            $xml = preg_replace('#(<rPr>(?:(?!</rPr>).)*?)<sz val="8"/>(<rFont val="Arial Narrow"/>)#s', '${1}<sz val="7"/>${2}', $si[1]);
            // "Mandatory/Forced Leave(Sec. 25 ..." -> "Mandatory/Forced Leave (Sec. 25 ..."
            $xml = preg_replace('#(<t)( xml:space="preserve")?>\((?=Sec\.|R\.A\.|RA |CSC)#', '<t xml:space="preserve"> (', $xml);
            $xml = str_replace('<t xml:space="preserve"> </t></r><r><rPr><sz val="7"/><rFont val="Arial Narrow"/><family val="2"/></rPr><t xml:space="preserve"> (', '<t xml:space="preserve"> </t></r><r><rPr><sz val="7"/><rFont val="Arial Narrow"/><family val="2"/></rPr><t xml:space="preserve">(', $xml);

            return '<si>' . $xml . '</si>';
        }, $strings);
    }

    /**
     * 6.A has no Wellness Leave box (it isn't on the 2020 form): add one like the others, a copy of Adoption Leave's
     * checkbox (shape 1039) two rows down, in the blank row under it (row 37). A form-control checkbox lives in five
     * parts: the sheet's control list, its relationship, its properties, the VML shape and the drawing shape.
     */
    private function addWellnessCheckbox(string $path): void
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException('Could not open the leave form template.');
        }
        $sheet = $zip->getFromName(self::SHEET);
        $rels = $zip->getFromName(self::SHEET_RELS);
        $id = self::BOX_WELLNESS;
        $shift = fn (string $xml) => preg_replace_callback('#<xdr:row>(\d+)</xdr:row>#', fn ($m) => '<xdr:row>' . ((int) $m[1] + 2) . '</xdr:row>', $xml);

        // Sheet control list + its relationship to a new properties part
        preg_match('#<mc:AlternateContent\b(?:(?!</mc:AlternateContent>).)*shapeId="1039".*?</mc:AlternateContent>#s', $sheet, $control);
        preg_match_all('#Id="rId(\d+)"#', $rels, $rids);
        $rid = 'rId' . (max(array_map('intval', $rids[1])) + 1);
        $props = 1;
        while ($zip->locateName("xl/ctrlProps/ctrlProp{$props}.xml") !== false) $props++;
        $newControl = $shift(str_replace(['shapeId="1039"', 'name="Check Box 15"'], ['shapeId="' . $id . '"', 'name="Check Box Wellness"'], preg_replace('#r:id="rId\d+"#', 'r:id="' . $rid . '"', $control[0])));
        $sheet = str_replace('</controls>', $newControl . '</controls>', $sheet);
        $rels = str_replace('</Relationships>', '<Relationship Id="' . $rid . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/ctrlProp" Target="../ctrlProps/ctrlProp' . $props . '.xml"/></Relationships>', $rels);
        $zip->addFromString("xl/ctrlProps/ctrlProp{$props}.xml", '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n" . '<formControlPr xmlns="http://schemas.microsoft.com/office/spreadsheetml/2009/9/main" objectType="CheckBox" lockText="1" noThreeD="1"/>');
        $types = $zip->getFromName('[Content_Types].xml');
        $zip->addFromString('[Content_Types].xml', str_replace('</Types>', '<Override PartName="/xl/ctrlProps/ctrlProp' . $props . '.xml" ContentType="application/vnd.ms-excel.controlproperties+xml"/></Types>', $types));

        // VML shape (what Excel draws)
        $vml = $zip->getFromName(self::VML);
        preg_match('#<v:shape id="_x0000_s1039".*?</v:shape>#s', $vml, $shape);
        $newShape = str_replace('_x0000_s1039', '_x0000_s' . $id, $shape[0]);
        $newShape = preg_replace_callback('#<x:Anchor>\s*([^<]+)</x:Anchor>#', function ($m) {
            $parts = array_map('trim', explode(',', $m[1]));
            $parts[2] += 2;
            $parts[6] += 2;
            return '<x:Anchor>' . "\n    " . implode(', ', $parts) . '</x:Anchor>';
        }, $newShape);
        $vml = str_replace('</xml>', $newShape . "\n</xml>", $vml);

        // Drawing shape (DrawingML copy)
        $drawing = $zip->getFromName(self::DRAWING);
        preg_match('#<mc:AlternateContent\b(?:(?!</mc:AlternateContent>).)*name="Check Box 15".*?</mc:AlternateContent>#s', $drawing, $anchor);
        $newAnchor = $shift(str_replace(['cNvPr id="1039"', 'name="Check Box 15"', 'spid="_x0000_s1039"', '-00000F040000}'], ['cNvPr id="' . $id . '"', 'name="Check Box Wellness"', 'spid="_x0000_s' . $id . '"', '-00001C040000}'], $anchor[0]));
        $drawing = str_replace('</xdr:wsDr>', $newAnchor . '</xdr:wsDr>', $drawing);

        $zip->addFromString(self::SHEET, $sheet);
        $zip->addFromString(self::SHEET_RELS, $rels);
        $zip->addFromString(self::VML, $vml);
        $zip->addFromString(self::DRAWING, $drawing);
        $zip->close();
    }

    /** Drawing without the template's text boxes (sample data, and the blank one behind the left logo); keeps the logos, checkboxes and the "Stamp of Date of Receipt" box */
    private function withoutValueTextBoxes(string $drawing): string
    {
        return preg_replace_callback(
            '#<xdr:(twoCellAnchor|oneCellAnchor)\b.*?</xdr:\1>#s',
            fn ($anchor) => preg_match('#name="TextBox (\d+)"#', $anchor[0], $box) && (int) $box[1] !== 5 ? '' : $anchor[0],
            $drawing
        );
    }

    private const WEEKDAYS = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

    /**
     * Off days as printed, e.g. "Saturdays and Sundays" (the default when none are entered). Weekday names
     * are recognized in the entries; anything else is printed as entered.
     */
    public static function offDaysText(array $offDays): string
    {
        $text = implode(' ', $offDays);
        $days = array_values(array_filter(self::WEEKDAYS, fn ($day) => stripos($text, substr($day, 0, 3)) !== false));
        if (!$offDays) {
            $days = ['Saturday', 'Sunday'];
        } elseif (!$days) {
            return implode(', ', $offDays);
        }
        // Monday first, Sunday last
        usort($days, fn ($a, $b) => ((array_search($a, self::WEEKDAYS) + 6) % 7) <=> ((array_search($b, self::WEEKDAYS) + 6) % 7));
        $plural = array_map(fn ($day) => $day . 's', $days);
        $last = array_pop($plural);

        return $plural ? implode(', ', $plural) . ' and ' . $last : $last;
    }

    /** Short names used when several leave types share a line. Mirrors LEAVE_ABBREVIATIONS in useLeaveFormPrint.ts */
    private const LEAVE_ABBREVIATIONS = [
        'Vacation Leave' => 'VL',
        'Mandatory/Forced Leave' => 'FL',
        'Sick Leave' => 'SL',
        'Maternity Leave' => 'ML',
        'Paternity Leave' => 'PL',
        'Special Privilege Leave' => 'SPL',
        'Solo Parent Leave' => 'SoPL',
        'Study Leave' => 'StL',
        '10-Day VAWC Leave' => 'VAWC',
        'Rehabilitation Leave' => 'RL',
        'Special Leave Benefits for Women' => 'SLBW',
        'Special Emergency (Calamity) Leave' => 'SEL',
        'Adoption Leave' => 'AL',
        'Wellness Leave' => 'WL',
    ];

    /** "VL", "SPL", ...; an "Others" leave keeps its kind, e.g. "Terminal Leave" */
    private function typeShort(Leave $row): string
    {
        return $row->type_of_leave === 'Others' ? ($row->other_type ?: 'Others') : (self::LEAVE_ABBREVIATIONS[$row->type_of_leave] ?? $row->type_of_leave);
    }

    /** "Others" leaves print their kind, e.g. "Terminal Leave" */
    private function typeName(Leave $row): string
    {
        return $row->type_of_leave === 'Others' ? ($row->other_type ?: 'Others') : $row->type_of_leave;
    }

    /** Total working days of the application, e.g. "3 days" (a lone half day keeps its AM/PM) */
    private function applicationDaysText(\Illuminate\Support\Collection $rows): string
    {
        if ($rows->count() === 1) {
            return $this->daysText($rows->first());
        }
        $days = $rows->sum(fn (Leave $row) => Leave::daysCounted((bool) $row->is_half_day, $row->number_of_working_days_applied_for));

        return rtrim(rtrim(number_format($days, 1), '0'), '.') . ($days <= 1 ? ' working day' : ' working days');
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
        $text = rtrim(rtrim(number_format($days, 1), '0'), '.') . ($days == 1 || $days < 1 ? ' working day' : ' working days');

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
     * Signatories, printed name (bold) and title under the signature lines: 7.A (C59), 7.B (G59) and the
     * authorized official (A64). The 7.A/7.B caption cells get a wrapping copy of their style and a taller row.
     */
    private function fillSignatories(ZipArchive $zip, string $sheet, Leave $leave, array $dates): string
    {
        $styles = $zip->getFromName(self::STYLES);

        // 6.C inclusive dates (bold) with the off days below
        [$datesText, $offDays] = $dates;
        preg_match('#<c r="B47"[^>]*\ss="(\d+)"#', $sheet, $style);
        [$styles, $wrapStyle] = $this->addWrapStyle($styles, (int) ($style[1] ?? 0), true);
        // 6.C values (the days and the dates, one line per leave type) at 11pt
        $lines = explode("\n", $datesText);
        $dateSize = self::SECTION_6C_VALUE_SIZE;
        $sheet = $this->setCellRich($sheet, 'B47', [[$datesText, true, $dateSize]], $wrapStyle);

        // Off days on the row below, left-aligned (B48 is empty in the template)
        $sheet = $this->setCellRich($this->ensureCell($sheet, 'B48'), 'B48', [['OFF DAYS:  ', false, self::SECTION_6C_VALUE_SIZE], [$offDays, true, self::SECTION_6C_VALUE_SIZE]]);
        $sheet = preg_replace('#(<row r="48"[^>]*\sht=")[\d.]+(")#', '${1}18${2}', $sheet, 1);

        // 6.C values centered across the section, like the print
        preg_match('#<c r="B44"[^>]*\ss="(\d+)"#', $sheet, $style);
        [$styles, $centerStyle] = $this->addWrapStyle($styles, (int) ($style[1] ?? 0), true);
        $sheet = preg_replace('#(<c r="B44"[^>]*\ss=")\d+(")#', '${1}' . $centerStyle . '${2}', $sheet, 1);
        $sheet = $this->addMerges($sheet, ['B44:E44', 'B47:E47']);
        // Room for one line per leave type
        $height = round(count($lines) * $dateSize * 1.3 + 8, 2);
        $sheet = preg_replace('#(<row r="47"[^>]*\sht=")[\d.]+(")#', '${1}' . $height . '${2}', $sheet, 1);

        foreach (['C59' => 'certified_by', 'G59' => 'recommended_by'] as $ref => $signatory) {
            ['name' => $name, 'title' => $title] = $leave->signatory($signatory);
            preg_match('#<c r="' . $ref . '"[^>]*\ss="(\d+)"#', $sheet, $style);
            [$styles, $wrapStyle] = $this->addWrapStyle($styles, (int) ($style[1] ?? 0));
            $sheet = $this->setCellRich($sheet, $ref, [[mb_strtoupper($name), true, self::SIGNATORY_SIZE], ["\n" . $title, false, self::SIGNATORY_SIZE]], $wrapStyle);
        }
        foreach ([40, 44, 47] as $row) {
            // B40 (Others) only when it was filled; B44 and B47 always are
            if ($row === 40 && !preg_match('#<c r="B40"[^>]*t="inlineStr"#', $sheet)) continue;
            [$sheet, $styles] = $this->underlineCells($sheet, $styles, $row, ['B', 'C', 'D', 'E']);
        }

        // 6.B lines with a value (purpose / illness): the line under the cell replaces the "____" text
        foreach (['I13' => ['I'], 'I15' => ['I'], 'I19' => ['I'], 'I21' => ['I'], 'H27' => ['H', 'I']] as $ref => $columns) {
            if (preg_match('#<c r="' . $ref . '"[^>]*t="inlineStr"[^>]*>.*?<b/>#s', $sheet)) {
                [$sheet, $styles] = $this->underlineCells($sheet, $styles, (int) substr($ref, 1), $columns);
            }
        }

        // 7.B "For disapproval due to": the reason goes on the line below (I55), wrapped, with the line under it
        if ($leave->recommendation === 'disapproval' && trim((string) $leave->disapproval_reason) !== '') {
            $reason = trim((string) $leave->disapproval_reason);
            preg_match('#<c r="I55"[^>]*\ss="(\d+)"#', $sheet, $style);
            [$styles, $reasonStyle] = $this->addWrapStyle($styles, (int) ($style[1] ?? 0));
            $sheet = $this->setCellRich($sheet, 'I55', [[$reason, true, 11]], $reasonStyle);
            [$sheet, $styles] = $this->underlineCells($sheet, $styles, 55, ['I']);
            // About 60 characters a line at 11pt in column I
            $reasonLines = max(1, (int) ceil(mb_strlen($reason) / 60));
            $sheet = preg_replace('#(<row r="55"[^>]*\sht=")[\d.]+(")#', '${1}' . max(19.5, round($reasonLines * 14.5 + 4, 2)) . '${2}', $sheet, 1);
        }
        $zip->addFromString(self::STYLES, $styles);
        // Name and (possibly two-line) title at the signatory size
        $sheet = preg_replace('#(<row r="59"[^>]*\sht=")[\d.]+(")#', '${1}' . round(3 * self::SIGNATORY_SIZE * 1.3 + 4, 2) . '${2}', $sheet, 1);

        ['name' => $name, 'title' => $title] = $leave->signatory('approved_by');
        return $this->setCellRich($sheet, 'A64', [
            ["\n_________________________________\n", false, self::SIGNATORY_SIZE],
            [mb_strtoupper($name), true, self::SIGNATORY_SIZE],
            ["\n" . $title, false, self::SIGNATORY_SIZE],
        ]);
    }

    /** Append a copy of cell style $index with wrapped text; returns [styles xml, new style index] */
    private function addWrapStyle(string $styles, int $index, bool $center = false): array
    {
        if (!preg_match('#<cellXfs count="(\d+)"[^>]*>(.*?)</cellXfs>#s', $styles, $xfs)
            || !preg_match_all('#<xf\b[^>]*?(?:/>|>.*?</xf>)#s', $xfs[2], $list)
            || !isset($list[0][$index])) {
            throw new RuntimeException('Leave form template styles not found.');
        }

        $xf = $list[0][$index];
        if (str_contains($xf, '<alignment')) {
            $xf = preg_replace('#<alignment\b#', '<alignment wrapText="1"', preg_replace('#\swrapText="\d"#', '', $xf), 1);
        } else {
            $xf = preg_replace('#\s*/>$#', ' applyAlignment="1"><alignment horizontal="center" vertical="top" wrapText="1"/></xf>', $xf);
        }
        if ($center) {
            $xf = preg_replace('#<alignment\b[^>]*?(/?>)#', '<alignment horizontal="center" vertical="center" wrapText="1"$1', $xf, 1);
            $xf = str_contains($xf, 'applyAlignment') ? $xf : preg_replace('#<xf\b#', '<xf applyAlignment="1"', $xf, 1);
        }
        $count = (int) $xfs[1];
        $styles = str_replace($xfs[0], str_replace(['count="' . $count . '"', '</cellXfs>'], ['count="' . ($count + 1) . '"', $xf . '</cellXfs>'], $xfs[0]), $styles);

        return [$styles, $count];
    }

    /**
     * A thin bottom border under cells of a row (the line a filled-in value sits on), keeping each cell's other
     * borders; missing cells are added in column order.
     */
    private function underlineCells(string $sheet, string $styles, int $row, array $columns): array
    {
        $bottomStyles = [];
        foreach ($columns as $col) {
            $ref = $col . $row;
            $sheet = $this->ensureCell($sheet, $ref);
            preg_match('#<c r="' . $ref . '"(?:\s[^>]*)?(?:/>|>)#', $sheet, $cell);
            $xf = preg_match('#\ss="(\d+)"#', $cell[0], $current) ? (int) $current[1] : 0;
            if (!isset($bottomStyles[$xf])) {
                [$styles, $bottomStyles[$xf]] = $this->addBottomBorderStyle($styles, $xf);
            }
            $replacement = preg_match('#\ss="\d+"#', $cell[0])
                ? preg_replace('#\ss="\d+"#', ' s="' . $bottomStyles[$xf] . '"', $cell[0], 1)
                : preg_replace('#^<c r="' . $ref . '"#', '<c r="' . $ref . '" s="' . $bottomStyles[$xf] . '"', $cell[0], 1);
            $sheet = str_replace($cell[0], $replacement, $sheet);
        }

        return [$sheet, $styles];
    }

    /** Adds an empty cell (e.g. "B48") to its row, in column order, when the template has none there */
    private function ensureCell(string $sheet, string $ref): string
    {
        if (preg_match('#<c r="' . $ref . '"(?:\s[^>]*)?(?:/>|>)#', $sheet)) {
            return $sheet;
        }
        preg_match('#^([A-Z]+)(\d+)$#', $ref, $parts);
        [, $col, $row] = $parts;

        return preg_replace_callback('#(<row r="' . $row . '"[^>]*>)(.*?)(</row>)#s', function ($m) use ($ref, $col, $row) {
            $cells = $m[2];
            if (preg_match_all('#<c r="([A-Z]+)' . $row . '"#', $cells, $found, PREG_OFFSET_CAPTURE)) {
                foreach ($found[1] as [$letter, $offset]) {
                    if (strlen($letter) > strlen($col) || (strlen($letter) === strlen($col) && strcmp($letter, $col) > 0)) {
                        $at = strrpos(substr($cells, 0, $offset), '<c ');
                        return $m[1] . substr($cells, 0, $at) . '<c r="' . $ref . '"/>' . substr($cells, $at) . $m[3];
                    }
                }
            }
            return $m[1] . $cells . '<c r="' . $ref . '"/>' . $m[3];
        }, $sheet, 1);
    }

    /** Copy of cell style $index whose border also has a thin bottom line; returns [styles xml, new style index] */
    private function addBottomBorderStyle(string $styles, int $index): array
    {
        preg_match('#<borders count="(\d+)"[^>]*>(.*?)</borders>#s', $styles, $borders);
        preg_match_all('#<border\b[^>]*/>|<border\b.*?</border>#s', $borders[2], $borderList);
        preg_match('#<cellXfs count="(\d+)"[^>]*>(.*?)</cellXfs>#s', $styles, $xfs);
        preg_match_all('#<xf\b[^>]*?(?:/>|>.*?</xf>)#s', $xfs[2], $xfList);
        $xf = $xfList[0][$index] ?? '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>';

        // The border with a bottom line added
        $borderId = preg_match('#borderId="(\d+)"#', $xf, $b) ? (int) $b[1] : 0;
        $border = $borderList[0][$borderId] ?? '<border><left/><right/><top/><bottom/><diagonal/></border>';
        if (str_ends_with($border, '/>') && !str_contains($border, '</border>')) {
            $border = '<border><left/><right/><top/><bottom/><diagonal/></border>';
        }
        $border = preg_replace('#<bottom\b[^>]*/>|<bottom\b.*?</bottom>#s', '<bottom style="thin"><color indexed="64"/></bottom>', $border, 1);
        $newBorderId = (int) $borders[1];
        $styles = str_replace($borders[0], str_replace(['<borders count="' . $borders[1] . '"', '</borders>'], ['<borders count="' . ($newBorderId + 1) . '"', $border . '</borders>'], $borders[0]), $styles);

        // The cell style pointing at it
        $xf = preg_replace('#borderId="\d+"#', 'borderId="' . $newBorderId . '"', $xf, 1);
        $xf = str_contains($xf, 'applyBorder') ? $xf : preg_replace('#<xf\b#', '<xf applyBorder="1"', $xf, 1);
        preg_match('#<cellXfs count="(\d+)"[^>]*>(.*?)</cellXfs>#s', $styles, $xfs);
        $count = (int) $xfs[1];
        $styles = str_replace($xfs[0], str_replace(['<cellXfs count="' . $count . '"', '</cellXfs>'], ['<cellXfs count="' . ($count + 1) . '"', $xf . '</cellXfs>'], $xfs[0]), $styles);

        return [$styles, $count];
    }

    /** Merge cell ranges (the template's other merges are kept) */
    private function addMerges(string $sheet, array $ranges): string
    {
        $xml = implode('', array_map(fn ($range) => '<mergeCell ref="' . $range . '"/>', $ranges));

        return preg_replace_callback('#<mergeCells count="(\d+)">#', fn ($m) => '<mergeCells count="' . ((int) $m[1] + count($ranges)) . '">', str_replace('</mergeCells>', $xml . '</mergeCells>', $sheet), 1);
    }

    /** Replace a cell's content with rich text runs [text, bold, size, underline?] (Arial), optionally changing its style */
    private function setCellRich(string $sheet, string $ref, array $runs, ?int $style = null): string
    {
        $pattern = '#<c r="' . $ref . '"((?:\s+[a-z]+="[^"]*")*?)\s*(?:/>|>.*?</c>)#s';
        if (!preg_match($pattern, $sheet, $match)) {
            throw new RuntimeException("Cell {$ref} not found in the leave form template.");
        }
        if ($style === null && preg_match('/\ss="(\d+)"/', $match[1], $current)) {
            $style = (int) $current[1];
        }

        $xml = '';
        foreach ($runs as $run) {
            [$text, $bold, $size] = $run;
            $underline = $run[3] ?? false;
            $font = htmlspecialchars($run[4] ?? 'Arial', ENT_XML1 | ENT_QUOTES, 'UTF-8');
            $xml .= '<r><rPr>' . ($bold ? '<b/>' : '') . ($underline ? '<u/>' : '') . '<sz val="' . $size . '"/><rFont val="' . $font . '"/><family val="2"/></rPr>'
                . '<t xml:space="preserve">' . htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</t></r>';
        }
        $cell = '<c r="' . $ref . '"' . ($style !== null ? ' s="' . $style . '"' : '') . ' t="inlineStr"><is>' . $xml . '</is></c>';

        return preg_replace($pattern, str_replace(['\\', '$'], ['\\\\', '\\$'], $cell), $sheet, 1);
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
