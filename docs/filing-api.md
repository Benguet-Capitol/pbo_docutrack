# Shared Filing API (v1)

Lets PBO staff file and edit their **own** leaves, pass slips, time slips and tardiness/undertime from another PBO system (e.g. **pbo-registryV2**'s Profile → My Filings). Unlike the other shared APIs, this one writes.

## Authentication

Every request sends a **Filing** API key, separate from the Records, Leaves and Locator keys:

```
X-API-Key: <key>
```

Set it on the DocuTrack server in `.env` and run `php artisan config:clear` (or `config:cache`):

```
FILING_API_KEYS=key-for-registryv2
```

Call the API **from the client's server** only, keep the key in its `.env`, and send only the signed-in user's own HRIS employee ID. Optionally send `X-Acting-User: <name>`: Activity Logs then show "<name> (PBO Registry)" as who saved it.

| Status | Meaning |
|---|---|
| 401 | Missing or wrong key |
| 404 | No PBO employee (own or detailed, not separated) with that ID, or the record isn't theirs |
| 409 | A pass slip naming others too: changed in DocuTrack only |
| 422 | The module's own checks failed (`errors` by field, e.g. `entries.0.inclusive_dates`) |
| 503 | No keys configured (API off) |

## What the API decides

- **Employee:** only the one in the URL. A pass slip names only them.
- **Control number:** the next one of the year, as the forms make it (`L-`, `PS-`, `TS-`, `TU-` + `YYYY-MM-NNNN`; trashed records keep theirs).
- **Leave:** one application holds one or more leave types (not "Others"), each with its dates and off days; the salary is the employee's from HRIS; the filing date is today; signatories (`certified_by`, `recommended_by`, `approved_by`, each `_employee_id` and `_title`: the officer, `OIC, <title>` or `Acting <title>`) are optional: when left out, a new leave prints the current officers and an edit keeps the leave's own. A new 7.A signatory must be from the PHRMDO; 7.B and the authorized official can be anyone still with the Province. 7.B is the Provincial Budget Officer by default and may be the Provincial Governor instead (e.g. for staff who are OIC or Acting department head), each as the officer, OIC or Acting; for a department head (e.g. the Provincial Budget Officer) it is the Provincial Governor only, who also approves. The Leaves module's checks apply (overlapping days, yearly limits). The recommendation (7.B) is For approval unless `recommendation: disapproval` comes with a `disapproval_reason`. Always editable here; on edit, entries sent without their `id` are added and rows left out are removed.
- **Slips:** a supervisor (recommending approval on a pass slip, certified by on a time slip, supervisor on tardiness) from PBO staff other than the employee, or none to go straight to the approver; and the approving officer (`approved_by_employee_id` from PBO staff, `approved_by_title` Provincial Budget Officer / OIC / Acting; none = the Provincial Budget Officer). When the Provincial Budget Officer files a pass slip or tardiness, the Provincial Governor recommends / approves, as on the forms. Slips naming only the employee stay editable.

## Endpoints

All under `/api/v1/filing/employees/{employeeId}`; `{module}` is `leaves`, `pass-slips`, `time-slips` or `tardiness`.

| Method | Path | |
|---|---|---|
| GET | `/?year=2026` | The employee's records of the year by module, each with `editable`; `leave_types`; `signatories` (`supervisors`, `approvers`, `titles`, `budget_officer_id`, `governor_approves`) |
| POST | `/{module}` | File a new record |
| PUT | `/{module}/{id}` | Edit one (a leave by its control number, the others by id) |
| GET | `/{module}/{id}/print` | `data.path`: a 30-minute signed link (path only; prefix the DocuTrack address users' browsers use) to `/filing-print/{module}/{id}`, the module's own print preview without sign-in. `{module}` may also be `travel-orders` (by id, from the Leaves API's `travel_orders`): one naming the employee, previewed only, as HR makes them. Closing it posts `{ type: 'docutrack-filing-print-close' }` to the parent window |

The GET of the records also returns `leave_check` (`days` already on leave, `limits`, and the yearly-limited leaves filed) so a form can warn about overlapping days and yearly limits as the Leaves page does before saving, and `leave_signatories`: `employees` (`id`, `name`, `designation`, `office`, `separated`) and, per signatory (`certified_by`, `recommended_by`, `approved_by`), its `titles`, `candidates` (employee ids who can sign; null = anyone) and `default` (`employee_id`, `title`: who signs when nothing is chosen, e.g. the Provincial Governor recommends and approves a department head's leave), `department_head` (the applicant is one), and `acting_head` (`recommended_by`, `approved_by`: the Provincial Governor, for the form's "OIC / Acting department head" box).

Fields, as in the modules' own forms:

| Module | Fields |
|---|---|
| leaves | `recommendation` (approval / disapproval; default approval), `disapproval_reason`, `certified_by_employee_id`, `certified_by_title`, `recommended_by_employee_id`, `recommended_by_title`, `approved_by_employee_id`, `approved_by_title`, `entries`: [{ `id` (on edit), `type_of_leave`, `number_of_working_days_applied_for`, `is_half_day`, `half_day_period` (AM/PM), `inclusive_dates` (["Y-m-d" or "Y-m-d - Y-m-d", …]), `off_days` (weekday names, e.g. ["Friday", "Saturday", "Sunday"]; none = Saturdays and Sundays), `within_philippines`, `purpose`, `in_hospital`, `illness`, `completion_type` }] |
| pass-slips | `date`, `inclusive_dates` (default: the date), `requested_time` (H:i), `purpose`, `location`, `expected_return_time` (H:i or ASAP / NWD …), `remarks`, `vehicle` (RP Vehicle / PUJ), `recommending_approval_employee_id`, `approved_by_employee_id`, `approved_by_title` |
| time-slips | `date`, `time_in_am`, `time_out_am`, `time_in_pm`, `time_out_pm` (H:i, at least one), `reason`, `certified_by_employee_id`, `approved_by_employee_id`, `approved_by_title` |
| tardiness | `type` (Tardiness / Undertime), `requested_date`, `requested_time` (H:i), `return_time`, `reason`, `supervisor_employee_id`, `approved_by_employee_id`, `approved_by_title` |
