# Backups

`php artisan backup:run` saves **one zip** with:

| In the zip | What it is |
|---|---|
| `database.sql` | The whole database (mysqldump: tables, data, routines, triggers) |
| `storage/app/public/…` | Uploaded files (Records attachments) |
| `storage/app/private/…` | Private files |

The scheduler runs it **every Monday at 1:00 AM** (`routes/console.php`), weekly to save space on the server. Backups older than `BACKUP_KEEP_DAYS` (default 30) are then deleted. The newest backup is always kept, even if it's old.

## Running a backup by hand

Use this before an update or a big data change, or when you want a backup that can't wait for Monday. A backup made by hand doesn't change the Monday schedule.

1. On the server, open **Command Prompt** or **PowerShell**. If `BACKUP_COPY_PATH` is a network share, sign in to Windows as the same user the scheduler task runs as, or the copy may fail with "access denied".
2. Go to the app folder:

   ```
   cd D:\xampp\htdocs\pbo-mbm
   ```

3. Run the backup:

   ```
   php artisan backup:run
   ```

   If `php` isn't recognized, use the full path instead:

   ```
   D:\xampp\php\php.exe artisan backup:run
   ```

4. Wait for it to finish; it can take a few minutes. Don't close the window while it runs.
   - **Success:** a line in green, e.g. `Backup saved: E:\backups\pbo-mbm\pbo-mbm-2026-11-18-143005.zip (512.3 MB, database + 2140 files); copied to \\192.168.2.25\backups\pbo-mbm\pbo-mbm-2026-11-18-143005.zip`.
   - **Failure:** a line in red, `Backup failed: …`, with the reason (e.g. `mysqldump failed: …` when `BACKUP_MYSQLDUMP` is wrong, or a copy error when the share can't be reached).
5. Check that the new zip is in `BACKUP_PATH` (and in `BACKUP_COPY_PATH`, if set). **Weekly backup** on System → Connections also shows the run.

Other forms:

| Command | What it does |
|---|---|
| `php artisan backup:run` | Database and uploaded files (the same as the Monday backup) |
| `php artisan backup:run --no-files` | The database only: smaller and faster, e.g. right before running migrations |
| `php artisan schedule:list` | When the next Monday backup (and HRIS sync) will run |

Old backups are pruned after a manual run too, using the same `BACKUP_KEEP_DAYS` rule.

## Setup on the server

1. Set these in `.env`:

   ```
   BACKUP_PATH=E:\backups\pbo-mbm          # another drive or a network share, not the app's own disk
   BACKUP_KEEP_DAYS=30
   BACKUP_MYSQLDUMP=D:\xampp\mysql\bin\mysqldump.exe   # or just mysqldump if it is on PATH
   ```

   Then run `php artisan config:clear` (or `config:cache`).
2. Make sure the scheduler runs every minute. It already has to for the monthly HRIS sync.
   - **Windows Task Scheduler:** a task running `php D:\xampp\htdocs\pbo-mbm\artisan schedule:run` every minute.
   - **Linux cron:** `* * * * * cd /path/to/pbo-mbm && php artisan schedule:run >> /dev/null 2>&1`
3. Run it once by hand and check that the zip appears in `BACKUP_PATH` (see [Running a backup by hand](#running-a-backup-by-hand)):

   ```
   php artisan backup:run
   ```

The database password is passed to mysqldump in a temporary options file that is deleted right away. It never appears in the process list or the logs. Each result, success or failure, is written to `storage/logs/laravel.log` as "Backup saved: …" or "Backup failed: …".

## A second copy off the server

A backup on the same server doesn't survive that server being lost. Set `BACKUP_COPY_PATH` to a shared folder on another PC or a NAS, and every backup is copied there too, then checked to be complete:

```
BACKUP_COPY_PATH=\\192.168.2.25\backups\pbo-mbm
BACKUP_COPY_KEEP_DAYS=30
```

- **Access:** the account running the scheduler needs write access to that share.
  - With Windows Task Scheduler, run the task as a user that can open the share.
  - Using the SYSTEM account usually won't work for network shares.
- **If the copy fails** (share offline, no access): the backup on the server is kept, but the run counts as failed. "Weekly backup" on System → Connections turns red, and the message says why.
- **Old copies** are pruned the same way as on the server.

If you can't set up a share, copy the newest zip to another machine or an external drive by hand, about once a week.

## Restoring

1. Unzip the backup.
2. Restore the database into an **empty** database:

   ```
   mysql -u root -p -e "CREATE DATABASE `pbo-mbm-restored`"
   mysql -u root -p pbo-mbm-restored < database.sql
   ```

   Check it, then point `DB_DATABASE` in `.env` at it, or restore over the live database during a maintenance window.
3. Copy `storage/app/public` and `storage/app/private` from the zip back into the app's `storage/app/`.
4. Run `php artisan storage:link` if the `public/storage` link is missing, then `php artisan optimize:clear`.

**Test a restore every few months.** A backup is only proven once it has been restored.

## Options

- `php artisan backup:run --no-files`: the database only (smaller, faster).
- `BACKUP_TIMEOUT` (seconds, default 900): how long mysqldump may run.
