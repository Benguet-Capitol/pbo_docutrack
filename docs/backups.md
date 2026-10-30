# Backups

`php artisan backup:run` saves **one zip** with:

| In the zip | What it is |
|---|---|
| `database.sql` | The whole database (mysqldump: tables, data, routines, triggers) |
| `storage/app/public/…` | Uploaded files (Records attachments) |
| `storage/app/private/…` | Private files |

The scheduler runs it **every Monday at 1:00 AM** (`routes/console.php`), weekly to save space on the server. Backups older than `BACKUP_KEEP_DAYS` (default 30) are then deleted. The newest backup is always kept, even if it's old.

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
3. Run it once by hand and check that the zip appears in `BACKUP_PATH`:

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
- **If the copy fails** (share offline, no access): the backup on the server is kept, but the run counts as failed. The Dashboard's backup status turns red, and the message says why.
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
