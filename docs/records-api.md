# Shared Records API (v1)

Read-only API that lets other PBO systems (e.g. **pbo-registryV2** Archives / Documents) list and fetch the records kept in PBO|DocuTrack's **Records** module.

Only these categories are shared; **Administrative** records and records in the Trash are never returned:

| Key | Category | Code |
|---|---|---|
| `provincial-budget` | Provincial Budget | PB |
| `municipal-budget` | Municipal Budget (subcategories: the 13 municipalities) | MB |
| `issuances` | Issuances / Circulars / Other References and Documents (subcategories: DBM Budget Circulars, COA Circulars, …) | ISO |

Categories and subcategories are defined in `config/records.php`.

## Authentication

Every request sends an API key:

```
X-API-Key: <key>
```

(`Authorization: Bearer <key>` also works.) Keys are set on the DocuTrack server in `.env`:

```
RECORDS_API_KEYS=key-for-registryv2,key-for-another-app
```

Give each client app its own key so one can be revoked alone. Call the API **from the client's server** (keep the key in its `.env`), never from browser JavaScript.

| Status | Meaning |
|---|---|
| 401 | Missing or wrong key |
| 503 | No keys configured (API off) |
| 429 | More than 120 requests a minute |

## Endpoints

Base URL: `https://<docutrack-host>/api/v1/records`

### `GET /categories`

The shared categories with their subcategories and record counts.

```json
{ "data": [
  { "key": "municipal-budget", "name": "Municipal Budget", "code": "MB", "count": 12,
    "subcategories": [ { "name": "Atok", "count": 3 }, … ] }
] }
```

### `GET /` (list)

Newest first, paginated.

| Parameter | Description |
|---|---|
| `category` | Category key (`municipal-budget`) or name |
| `subcategory` | e.g. `Atok`, `DBM Budget Circulars` |
| `search` | Title, record no., subcategory or remarks |
| `year` | Year the record was added |
| `updated_since` | ISO 8601 date/time: only records added or changed after it (for syncing) |
| `sort` | `created_at` (default), `updated_at`, `record_no`, `title` |
| `order` | `desc` (default) or `asc` |
| `per_page` | 1–100 (default 25) |
| `page` | Page number |

```json
{
  "data": [
    {
      "id": 5,
      "record_no": "ISO-2026-04-001",
      "category": { "key": "issuances", "name": "Issuances / Circulars / Other References and Documents", "code": "ISO" },
      "subcategory": "DBM Budget Circulars",
      "title": "…",
      "remarks": "…",
      "file": {
        "name": "SAAOB_OG_Main_2026.xlsx",
        "extension": "xlsx",
        "mime_type": "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
        "size": 589230,
        "view_url": "https://<host>/api/v1/records/5/file",
        "download_url": "https://<host>/api/v1/records/5/file?download=1"
      },
      "created_at": "2026-04-22T17:22:05+08:00",
      "updated_at": "2026-04-22T17:22:05+08:00"
    }
  ],
  "meta": { "current_page": 1, "per_page": 25, "total": 2, "last_page": 1 },
  "links": { "next": null, "prev": null }
}
```

`file` is `null` when the record has no file. An unknown `category` returns 422 with the valid keys.

### `GET /{id}`

One record (same shape as a list item). 404 if it doesn't exist, is trashed, or isn't shared.

### `GET /{id}/file`

The record's file, shown inline; add `?download=1` to download it. The key is required here too, so a client app streams the file to its users through its own server.

### `GET /deleted?since=<ISO 8601>`

Shared records moved to the Trash (after `since`, if given), so a client keeping copies can remove them:

```json
{ "data": [ { "id": 5, "record_no": "ISO-2026-04-001", "deleted_at": "2026-10-23T09:59:55+08:00" } ] }
```

## Keeping a copy in sync

1. First run: page through `GET /` (all categories).
2. Later runs: `GET /?updated_since=<last run>` for new and changed records, and `GET /deleted?since=<last run>` for removed ones.
3. Store the record `id` to match records between runs; files are fetched on demand from `view_url` / `download_url` with the key.
