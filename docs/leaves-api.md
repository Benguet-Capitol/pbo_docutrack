# Shared Leaves API (v1)

Read-only API that lets other PBO systems (e.g. **pbo-registryV2**'s profile page) show a PBO staff member **their own** leaves from PBO|DocuTrack: days used per leave type and the days left of the leaves with yearly limits. These are the same figures as the Dashboard's **My Summary**.

Leaves are personal data (Data Privacy Act). The client app must only ask for the **signed-in user's own** employee ID. Never take the ID from the browser or the URL, and never show one person's leaves to another.

## Authentication

Every request sends a **Leaves** API key:

```
X-API-Key: <key>
```

(`Authorization: Bearer <key>` also works.) The keys are separate from the Records API keys, so a Records key can't read leaves. Set them on the DocuTrack server in `.env`:

```
LEAVES_API_KEYS=key-for-registryv2
```

Generate a key with `php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"`. Then run `php artisan config:clear` (or `config:cache`). Call the API **from the client's server** and keep the key in its `.env`. Never call it from browser JavaScript.

| Status | Meaning |
|---|---|
| 401 | Missing or wrong key |
| 404 | No employee with that HRIS employee ID |
| 422 | Invalid `year` |
| 503 | No keys configured (API off) |
| 429 | More than 120 requests a minute |

## Endpoint

### `GET /api/v1/leaves/employees/{employeeId}`

`employeeId` is the **HRIS employee ID** (`employees.employee_id`, e.g. `01323001799`), not the database id. Both systems sync it from HRIS. In pbo-registryV2 it is `auth()->user()->employee->employee_id`.

| Parameter | Description |
|---|---|
| `year` | Year of filing (default: the current year) |

```json
{
  "data": {
    "employee": { "employee_id": "01323001799", "name": "Ronaldo B. Alvarez", "position_title": "Administrative Assistant II", "office": "PBO" },
    "year": 2026,
    "years": [2026, 2025],
    "total_days_used": 6,
    "used_by_type": [
      { "type": "Vacation Leave", "days": 2 },
      { "type": "Sick Leave", "days": 0 },
      { "type": "Mandatory/Forced Leave", "days": 4 }
    ],
    "limited_leaves": [
      { "type": "Mandatory/Forced Leave", "limit": 5, "used": 4, "remaining": 1 },
      { "type": "Special Privilege Leave", "limit": 3, "used": 0, "remaining": 3 },
      { "type": "Wellness Leave", "limit": 5, "used": 0, "remaining": 5 }
    ],
    "applications": [
      {
        "control_no": "L-2026-04-0004",
        "date_of_filing": "2026-04-22",
        "disapproved": false,
        "days": 2,
        "entries": [
          { "type": "Vacation Leave", "days": 2, "is_half_day": false, "half_day_period": null, "inclusive_dates": ["2026-04-30 - 2026-05-01"] }
        ]
      }
    ]
  }
}
```

| Field | Meaning |
|---|---|
| `years` | Years with leaves filed, plus the requested year, newest first (for a year picker) |
| `used_by_type` | Days used per type, counting only leaves that proceeded (disapproved ones left out). Vacation and Sick Leave are always listed. |
| `total_days_used` | Sum of `used_by_type` |
| `limited_leaves` | Leaves with a yearly limit: `used` and `remaining` count **every filed leave, disapproved ones included**. This is the same rule DocuTrack uses when filing. |
| `applications` | The year's leave applications, newest first. One per control number; an application can hold several leave types (`entries`). |
| `days` | A half day counts as 0.5 |
| `inclusive_dates` | `Y-m-d` dates, or `Y-m-d - Y-m-d` ranges |

Salary, signatories, disapproval reasons and trashed leaves are never returned.

## Example (pbo-registryV2, Laravel)

`.env`: `DOCUTRACK_LEAVES_KEY=<key>` (`DOCUTRACK_URL` is already set for the Records API). In `config/services.php` under `docutrack`: `'leaves_key' => env('DOCUTRACK_LEAVES_KEY')`.

```php
$hrisId = auth()->user()->employee?->employee_id;   // the signed-in user's own ID only

$summary = $hrisId ? Http::baseUrl(config('services.docutrack.url'))
    ->withHeaders(['X-API-Key' => config('services.docutrack.leaves_key')])
    ->timeout(config('services.docutrack.timeout'))
    ->get('/api/v1/leaves/employees/' . rawurlencode($hrisId), ['year' => $year])
    ->json('data') : null;
```

Handle a failed call or a `null` summary (account not linked to an employee, DocuTrack unreachable) by showing "Leave summary unavailable" instead of an error page.
