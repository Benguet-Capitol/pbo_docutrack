# Shared Locator API (v1)

Read-only API that lets other PBO systems (e.g. **pbo-registryV2**'s Locator Chart page) show where PBO staff are, per day: the same statuses as **Locator Chart** here.

It returns computed statuses only, never the underlying records. A leave shows its type (Sick Leave, Vacation Leave …) and nothing else from the record, and undertime shows its times without the reason. Salary, signatories, disapproved leaves and trashed records are never returned.

## Authentication

Every request sends a **Locator** API key:

```
X-API-Key: <key>
```

(`Authorization: Bearer <key>` also works.) The keys are separate from the Records and Leaves keys. Set them on the DocuTrack server in `.env`:

```
LOCATOR_API_KEYS=key-for-registryv2
```

Generate a key with `php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"`, then run `php artisan config:clear` (or `config:cache`). Call the API **from the client's server** and keep the key in its `.env`. Never call it from browser JavaScript.

| Status | Meaning |
|---|---|
| 401 | Missing or wrong key |
| 422 | Invalid dates, or more than 31 days |
| 503 | No keys configured (API off) |
| 429 | More than 120 requests a minute |

Refused calls are written to Activity Logs (module "Shared API"). Successful calls aren't, since the chart refreshes every few minutes and names no single person.

## Endpoint

### `GET /api/v1/locator?from=2026-11-02&to=2026-11-03`

`from` and `to` are `Y-m-d`, at most 31 days apart.

```json
{
  "data": {
    "from": "2026-11-02",
    "to": "2026-11-03",
    "days": ["2026-11-02", "2026-11-03"],
    "employees": [
      {
        "employee_id": "01323001799",
        "name": "Ronaldo B. Alvarez",
        "designation": "Administrative Assistant II",
        "days": {
          "2026-11-02": { "status": "On Official Business", "label": "Travel Order", "text": "Seminar at Baguio City", "meetings": [] },
          "2026-11-03": { "status": "Present with Meetings", "label": null, "text": null, "meetings": [{ "time": "10:00 AM", "particulars": "Budget review" }] }
        }
      }
    ]
  }
}
```

| Field | Meaning |
|---|---|
| `employees` | PBO staff, the Governor and anyone existing records still name, with at least one record to show. The Provincial Budget Officer comes first, then by last name. |
| `status` | `Off Day`, `Undertime`, `On Leave`, `On Official Business`, `Present with Meetings` or `Present` |
| `label` | `Pass Slip` or `Travel Order` for `On Official Business`, `Half Day` for a half day of `On Leave`, otherwise null |
| `text` | The detail: the leave's type, e.g. "Sick Leave" ("Sick Leave (AM)" or "Sick Leave (PM)" for a half day), the undertime's times, or "purpose at place" |
| `meetings` | Only with `Present with Meetings`, by time |

The first rule that applies wins, in the order of `App\Services\LocatorChart`: weekend, undertime (on the day it happened, not the day it was filed), leave, pass slip, travel order, meetings, an off day inside a leave (`Off Day`), Present.

Only a leave's working days count as `On Leave`. A day inside its dates that it marks as an off day (for example a Friday, for someone off on Fridays) is `Off Day`, unless another record is on it. Morning and afternoon half days on the same day show as a whole day of `On Leave`.
