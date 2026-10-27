<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Record;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

/**
 * Shared Records API (read-only) for other PBO systems, e.g. pbo-registryV2's Archives / Documents.
 *
 * Only the shared categories in config/records.php are exposed (Provincial Budget, Municipal Budget, Issuances /
 * Circulars / Other References and Documents); Administrative and trashed records never are. Requests carry an
 * API key (EnsureApiKey, api.key:records). See docs/records-api.md.
 */
class SharedRecordController extends Controller
{
    private const MAX_PER_PAGE = 100;

    /** GET /api/v1/records/categories: the shared categories, their subcategories and record counts */
    public function categories(): JsonResponse
    {
        $counts = Record::query()
            ->whereIn('record_type', $this->sharedNames())
            ->selectRaw('record_type, record_subtype, COUNT(*) as total')
            ->groupBy('record_type', 'record_subtype')
            ->get();

        $data = collect($this->sharedCategories())->map(function (array $category, string $key) use ($counts) {
            $rows = $counts->where('record_type', $category['name']);
            $subcategories = collect($category['subcategories'])
                // Subcategories in use that aren't in the list yet still show
                ->merge($rows->pluck('record_subtype')->filter())
                ->unique()
                ->values()
                ->map(fn ($name) => ['name' => $name, 'count' => (int) $rows->where('record_subtype', $name)->sum('total')]);

            return [
                'key' => $key,
                'name' => $category['name'],
                'code' => $category['code'],
                'count' => (int) $rows->sum('total'),
                'subcategories' => $subcategories,
            ];
        })->values();

        return response()->json(['data' => $data]);
    }

    /**
     * GET /api/v1/records: shared records, newest first.
     * Filters: category (key, e.g. "municipal-budget", or name), subcategory, search, year, updated_since (ISO 8601,
     * for syncing only what changed), sort (created_at | updated_at | record_no | title), order (asc | desc),
     * per_page (1-100), page.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category' => ['nullable', 'string'],
            'subcategory' => ['nullable', 'string', 'max:255'],
            'search' => ['nullable', 'string', 'max:255'],
            'year' => ['nullable', 'integer', 'min:1900', 'max:2999'],
            'updated_since' => ['nullable', 'date'],
            'sort' => ['nullable', Rule::in(['created_at', 'updated_at', 'record_no', 'title'])],
            'order' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:' . self::MAX_PER_PAGE],
        ]);

        $query = $this->sharedQuery();

        if (!empty($validated['category'])) {
            $name = $this->categoryName($validated['category']);
            if ($name === null) {
                return response()->json(['message' => 'Unknown category.', 'categories' => array_keys($this->sharedCategories())], 422);
            }
            $query->where('record_type', $name);
        }
        if (!empty($validated['subcategory'])) {
            $query->where('record_subtype', $validated['subcategory']);
        }
        if (!empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(fn (Builder $q) => $q
                ->where('title', 'like', "%{$search}%")
                ->orWhere('record_no', 'like', "%{$search}%")
                ->orWhere('record_subtype', 'like', "%{$search}%")
                ->orWhere('remarks', 'like', "%{$search}%"));
        }
        if (!empty($validated['year'])) {
            $query->whereYear('created_at', $validated['year']);
        }
        if (!empty($validated['updated_since'])) {
            $query->where('updated_at', '>', Carbon::parse($validated['updated_since']));
        }

        $records = $query
            ->orderBy($validated['sort'] ?? 'created_at', $validated['order'] ?? 'desc')
            ->orderBy('id', $validated['order'] ?? 'desc')
            ->paginate($validated['per_page'] ?? 25)
            ->withQueryString();

        return response()->json([
            'data' => collect($records->items())->map(fn (Record $record) => $this->present($record))->values(),
            'meta' => [
                'current_page' => $records->currentPage(),
                'per_page' => $records->perPage(),
                'total' => $records->total(),
                'last_page' => $records->lastPage(),
            ],
            'links' => [
                'next' => $records->nextPageUrl(),
                'prev' => $records->previousPageUrl(),
            ],
        ]);
    }

    /** GET /api/v1/records/{id} */
    public function show(int $id): JsonResponse
    {
        $record = $this->sharedQuery()->find($id);

        return $record
            ? response()->json(['data' => $this->present($record)])
            : response()->json(['message' => 'Record not found.'], 404);
    }

    /** GET /api/v1/records/{id}/file[?download=1]: the record's file, shown inline or downloaded */
    public function file(Request $request, int $id): Response
    {
        $record = $this->sharedQuery()->find($id);
        if (!$record) {
            return response()->json(['message' => 'Record not found.'], 404);
        }
        if (!$record->image_path || !Record::files()->exists($record->image_path)) {
            return response()->json(['message' => 'This record has no file.'], 404);
        }

        $name = $this->fileName($record);

        return $request->boolean('download')
            ? Record::files()->download($record->image_path, $name)
            : response()->file(Record::files()->path($record->image_path), [
                'Content-Disposition' => 'inline; filename="' . addslashes($name) . '"',
            ]);
    }

    /**
     * GET /api/v1/records/deleted?since=: shared records moved to the Trash (since the given time), so a client that
     * keeps a copy can remove them. Permanently deleted records are already gone from the Trash.
     */
    public function deleted(Request $request): JsonResponse
    {
        $validated = $request->validate(['since' => ['nullable', 'date']]);

        $records = Record::onlyTrashed()
            ->whereIn('record_type', $this->sharedNames())
            ->when($validated['since'] ?? null, fn ($q, $since) => $q->where('deleted_at', '>', Carbon::parse($since)))
            ->orderBy('deleted_at')
            ->get(['id', 'record_no', 'deleted_at']);

        return response()->json(['data' => $records->map(fn (Record $record) => [
            'id' => $record->id,
            'record_no' => $record->record_no,
            'deleted_at' => $record->deleted_at?->toIso8601String(),
        ])->values()]);
    }

    /** Live records of the shared categories */
    private function sharedQuery(): Builder
    {
        return Record::query()->whereIn('record_type', $this->sharedNames());
    }

    /** key => category, shared ones only */
    private function sharedCategories(): array
    {
        return array_filter(config('records.categories', []), fn ($category) => !empty($category['shared']));
    }

    private function sharedNames(): array
    {
        return array_values(array_map(fn ($category) => $category['name'], $this->sharedCategories()));
    }

    /** Category name from its key ("municipal-budget") or its name (case-insensitive) */
    private function categoryName(string $value): ?string
    {
        foreach ($this->sharedCategories() as $key => $category) {
            if ($key === $value || strcasecmp($category['name'], $value) === 0) {
                return $category['name'];
            }
        }

        return null;
    }

    private function present(Record $record): array
    {
        $key = collect($this->sharedCategories())->search(fn ($category) => $category['name'] === $record->record_type);
        $category = $this->sharedCategories()[$key] ?? null;
        $hasFile = $record->image_path && Record::files()->exists($record->image_path);

        return [
            'id' => $record->id,
            'record_no' => $record->record_no,
            'category' => ['key' => $key ?: null, 'name' => $record->record_type, 'code' => $category['code'] ?? null],
            'subcategory' => $record->record_subtype,
            'title' => $record->title,
            'remarks' => $record->remarks,
            'file' => $hasFile ? [
                'name' => $this->fileName($record),
                'extension' => strtolower(pathinfo($record->image_path, PATHINFO_EXTENSION)),
                'mime_type' => Record::files()->mimeType($record->image_path) ?: null,
                'size' => Record::files()->size($record->image_path),
                'view_url' => url("/api/v1/records/{$record->id}/file"),
                'download_url' => url("/api/v1/records/{$record->id}/file?download=1"),
            ] : null,
            'created_at' => $record->created_at?->toIso8601String(),
            'updated_at' => $record->updated_at?->toIso8601String(),
        ];
    }

    /** The uploaded file's name without the "1776849702_" timestamp prefix added on upload */
    private function fileName(Record $record): string
    {
        return preg_replace('/^\d{9,}_/', '', basename((string) $record->image_path));
    }
}
