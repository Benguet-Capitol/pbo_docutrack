# Deploying PBO|DocuTrack

How to put a new version on the server, and what has to be set up there. It works whatever version the server has now: the migration steps work out what's new.

---

## A. On your computer: commit and push

1. Make sure everything works:

   ```
   php artisan test
   npx vue-tsc --noEmit
   npm run build
   ```

2. Commit and push:

   ```
   git add -A
   git status
   git commit -m "..."
   git push
   ```

   Check `git status` before committing. `.env` must not be in the list; it's ignored. `public/build` isn't committed either, because the server builds it.

---

## B. On the server

### 1. Back up first

Export the database in phpMyAdmin (Export → Quick → SQL), or:

```
D:\xampp\mysql\bin\mysqldump.exe -u root -p pbo-mbm > D:\pbo-mbm-before-update.sql
```

Also copy `storage\app\public\records` somewhere safe. This update moves those files (step 5).

### 2. Turn on maintenance mode

```
php artisan down
```

Users see a "be right back" page until step 9.

### 3. Get the code

```
cd D:\xampp\htdocs\pbo-mbm
git status
git pull
```

`git status` should show no local changes before you pull. If it does, ask before discarding them.

### 4. Install and build

```
composer install --no-dev --optimize-autoloader
npm ci
npm run build
```

Check the PHP extensions: `php -m` must list `zip` and `pdo_mysql`. XAMPP has both.

### 5. Update the database

```
php artisan migrate:status
php artisan migrate:reconcile
```

`migrate:reconcile` lists the pending migrations and checks whether each one's changes are already in the database. It executes nothing.

| It says | Do |
|---|---|
| "No pending migrations" | Nothing; go to step 6 |
| "already in the database" | `php artisan migrate:reconcile --apply` (records them as run) |
| "needs to run" | Leave them: the next command runs them |
| "needs a person" | Stop and check that migration by hand (it changes data) |

Then:

```
php artisan migrate --force
```

This version's migrations:

| Migration | What it does |
|---|---|
| `2026_10_23_000000_add_leaves_view_all_permission` | "View all employees' leaves" for Administrative, Administrator and Developer; HR summary for every role |
| `2026_10_27_000000_move_record_files_to_private_storage` | Moves Records uploads from `storage\app\public\records` to `storage\app\private\records` (no public URL) |
| `2026_10_27_000001_create_job_runs_table` | Backup/HRIS sync status for the Dashboard; the "See backup and HRIS sync status" permission for Administrator and Developer |
| `2026_10_28_000000_create_trip_tickets_table` | Driver's Trip Tickets: the control no. issued to each travel order's ticket |
| `2026_10_28_000001_number_existing_trip_tickets` | Numbers the trip tickets of the travel orders already in the system (driver and RP vehicle), in date order; new ones continue the series |

If the first one already ran on the server, `migrate` skips it.

### 6. Update `.env`

Open `D:\xampp\htdocs\pbo-mbm\.env` and check or add these lines (the rest stays as it is):

```
APP_ENV=production
APP_DEBUG=false

# Shared APIs for pbo-registryV2: one key each, different keys
RECORDS_API_KEYS=<records key>
LEAVES_API_KEYS=<leaves key>

# Nightly backups
BACKUP_PATH=E:\backups\pbo-mbm
BACKUP_KEEP_DAYS=30
BACKUP_MYSQLDUMP=D:\xampp\mysql\bin\mysqldump.exe

# A second copy of every backup on another PC or NAS (see step 8)
BACKUP_COPY_PATH=\\192.168.2.25\backups\pbo-mbm
BACKUP_COPY_KEEP_DAYS=30

# HRIS (for the employee sync), if not set yet
HRIS_URL=http://192.168.2.26/api/v1
HRIS_KEY=<HRIS key>
```

- **New API key:**

  ```
  php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
  ```

  Run it once for each key.
- **`BACKUP_PATH`:** use another drive than the one with the database if the server has one. Create the folder.
- **Real keys:** never put them in `.env.example`, and never commit them.

### 7. Caches

```
php artisan optimize:clear
php artisan config:cache
php artisan view:cache
```

Run `php artisan config:cache` again after any later `.env` change.

### 8. Scheduler and backups

The nightly backup (1:00 AM) and the monthly HRIS sync (1st of the month, 2:00 AM) run only if `schedule:run` runs every minute.

**Windows: Task Scheduler**

1. Open Task Scheduler and choose **Create Task** (not "Basic Task").
2. **General** tab:
   - Name: `PBO DocuTrack scheduler`.
   - Choose **Run whether user is logged on or not**.
   - Under "When running the task, use the following user account", pick a Windows user that can write to `BACKUP_PATH` and open the `BACKUP_COPY_PATH` share. SYSTEM usually can't open network shares.
3. **Triggers** tab → New:
   - Daily, starting today at 12:00 AM.
   - **Repeat task every: 1 minute**, for a duration of: **Indefinitely**.
4. **Actions** tab → New → Start a program:
   - Program: `D:\xampp\php\php.exe`
   - Arguments: `artisan schedule:run`
   - Start in: `D:\xampp\htdocs\pbo-mbm`
5. **Settings** tab: "If the task is already running" → **Do not start a new instance**.
6. Save. Windows asks for that user's password.

**Linux: cron**

```
* * * * * cd /path/to/pbo-mbm && php artisan schedule:run >> /dev/null 2>&1
```

**The second copy (`BACKUP_COPY_PATH`)**

1. On another PC or the NAS, make a folder, e.g. `D:\backups\pbo-mbm`.
2. Share it (Properties → Sharing → Advanced Sharing). Give the scheduler task's user **Change** permission.
3. From the server, check that `\\<that PC>\<share>` opens in File Explorer while signed in as that user.

**Run both jobs once now**, so the Dashboard has a status straight away:

```
php artisan backup:run
php artisan employees:sync-hris
```

The backup prints "Backup saved: … copied to …". Check that the zip is in both folders.

### 9. Back online

```
php artisan up
```

### 10. Check

| Where | What to check |
|---|---|
| Sign in | Works as usual. One deliberately wrong password shows in **Activity Logs** as "Failed Sign-in" |
| Dashboard (as Administrator) | The header shows **Nightly backup** and **HRIS employee sync** in green |
| Documents | List, cards, tabs, search. Create a test document, edit it, forward and receive it, open a checklist, then delete it |
| Records | View and download a file, including an Administrative one, and add a record with a file |
| Leaves | Create a leave as someone without "View all employees' leaves": they see only their own |
| Employees | Search and move through the pages |
| Profile | My Summary shows at full width |
| Old file links | `http://<server>/storage/records/<file>` no longer opens a Records file |

---

## C. pbo-registryV2

In pbo-registryV2's `.env` on its server:

```
DOCUTRACK_URL=http://192.168.2.19
DOCUTRACK_RECORDS_KEY=<the same key as RECORDS_API_KEYS>
DOCUTRACK_LEAVES_KEY=<the same key as LEAVES_API_KEYS>
DOCUTRACK_TIMEOUT=10
# HRIS IDs of staff from other offices detailed to the PBO (comma-separated)
DOCUTRACK_PBO_DETAILED_EMPLOYEES=
```

Then run `php artisan config:clear` there (or `config:cache` if it caches). Check that a PBO staff member's profile shows their leaves, and that an Archives page shows the shared records.

API calls show in DocuTrack's Activity Logs as "API Read". Refused ones (a wrong key) show as "API Refused".

---

## D. If something goes wrong

- **The site errors after the update:** check `storage\logs\laravel.log`. With `APP_DEBUG=false`, details are only there.
- **Undo the update** (in this order):
  1. `php artisan migrate:rollback --step=4`, while the new code is still in place. This undoes the last four migrations (trip ticket numbering, trip tickets, the status table and the file move), and undoing the file move puts the Records files back in `storage\app\public\records`.
  2. `git log --oneline -3`, then `git checkout <previous commit>`.
  3. `npm run build`, then `php artisan optimize:clear`.
  4. Only if something is still wrong: restore the database from step B1.
- **Dashboard backup status is red:** hover over it to see the reason.
  - Most often the share can't be reached: wrong path, PC off, or no access for the task's user.
  - Or the scheduler task stopped: check its "Last Run Result" in Task Scheduler.

## E. Every future update

The short version, once the setup above is done:

```
php artisan down
git pull
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate:reconcile
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan view:cache
php artisan up
```

See [backups.md](backups.md) for restoring a backup, and [records-api.md](records-api.md) and [leaves-api.md](leaves-api.md) for the APIs.
