<?php

namespace App\Services;

use App\Support\JobRuns;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Throwable;

/**
 * Whether this server is set up to run safely (the Connections page, as in pbo-registryV2): a stopped scheduler, a scheduler
 * that runs too rarely, or a failing backup raises no error anywhere else.
 */
class ServerChecks
{
    private const HEARTBEAT_FILE = 'scheduler-heartbeat.json';

    /** No beat for this long: the scheduler has stopped */
    private const HEARTBEAT_MAX_MINUTES = 10;

    /** Beats further apart than this: Task Scheduler repeats less often than every minute, so timed jobs can be skipped */
    private const HEARTBEAT_MAX_GAP_SECONDS = 90;

    /** Written every minute by the scheduler (routes/console.php): the last two beats give its age and how often it runs */
    public static function beat(): void
    {
        $previous = self::beats()['last'] ?? null;
        Storage::disk('local')->put(self::HEARTBEAT_FILE, json_encode(['last' => now()->getTimestamp(), 'previous' => $previous]));
    }

    public static function all(): array
    {
        return [
            self::debug(),
            self::logLevel(),
            self::scheduler(),
            ...self::jobs(),
            self::backupTool(),
            self::backupCopy(),
            self::migrations(),
            self::storage(),
        ];
    }

    private static function beats(): array
    {
        try {
            return json_decode((string) Storage::disk('local')->get(self::HEARTBEAT_FILE), true) ?: [];
        } catch (Throwable) {
            return [];
        }
    }

    private static function debug(): array
    {
        return config('app.debug')
            ? self::row('Debug mode', 'error', 'APP_DEBUG is on: an error page shows code and settings to users. Set APP_DEBUG=false in .env.')
            : self::row('Debug mode', 'ok', 'Off.');
    }

    private static function logLevel(): array
    {
        // A stack has no level of its own: its channels' levels decide, and the most talkative one counts
        $default = config('logging.default');
        $channels = config("logging.channels.{$default}.driver") === 'stack' ? (array) config("logging.channels.{$default}.channels") : [$default];
        $levels = collect($channels)->map(fn ($channel) => strtolower((string) config('logging.channels.' . trim($channel) . '.level', 'debug')));
        $level = $levels->first(fn ($l) => in_array($l, ['debug', 'info'], true)) ?? $levels->first();

        return in_array($level, ['debug', 'info'], true)
            ? self::row('Log level', 'warning', "LOG_LEVEL is {$level}: the log grows quickly. Set LOG_LEVEL=warning in .env.")
            : self::row('Log level', 'ok', ucfirst($level) . '.');
    }

    private static function scheduler(): array
    {
        ['last' => $last, 'previous' => $previous] = self::beats() + ['last' => null, 'previous' => null];
        if (!$last) {
            return self::row('Scheduler', 'error', 'It has not run yet. Without it there are no backups or HRIS syncs: Task Scheduler must run `php artisan schedule:run` every minute.');
        }
        $minutes = (int) floor((now()->getTimestamp() - $last) / 60);
        if ($minutes > self::HEARTBEAT_MAX_MINUTES) {
            return self::row('Scheduler', 'error', "It last ran {$minutes} minutes ago, so backups and HRIS syncs have stopped. Check the task in Task Scheduler.");
        }
        if ($previous && $last - $previous > self::HEARTBEAT_MAX_GAP_SECONDS) {
            $every = (int) round(($last - $previous) / 60);

            return self::row('Scheduler', 'warning', "It runs about every {$every} minutes. Jobs run only in their exact minute (backup Monday 1:00 AM), so one can be skipped: set \"Repeat task every\" to 1 minute (type it in the box).");
        }

        return self::row('Scheduler', 'ok', $minutes ? "Runs every minute; last ran {$minutes} minute(s) ago." : 'Runs every minute.');
    }

    /** The weekly backup and the monthly HRIS sync, from their recorded runs (job_runs) */
    private static function jobs(): array
    {
        return array_map(function (array $job) {
            $when = $job['last_success_at'] ? date('M j, Y g:i A', strtotime($job['last_success_at'])) : null;

            return match ($job['status']) {
                'ok' => self::row($job['label'], 'ok', "Last succeeded {$when}."),
                'failed' => self::row($job['label'], 'error', 'The last run failed: ' . ($job['message'] ?: 'see the Laravel log') . '.'),
                'overdue' => self::row($job['label'], 'error', ($when ? "Last succeeded {$when}, longer ago than expected" : 'It has never succeeded') . ': check the scheduler.'),
                default => self::row($job['label'], 'warning', 'Not run yet.'),
            };
        }, array_values(JobRuns::statuses()));
    }

    private static function backupTool(): array
    {
        try {
            $process = new Process([config('backup.mysqldump'), '--version'], null, null, null, 10);
            $process->run();
            if ($process->isSuccessful()) {
                return self::row('Backup tool', 'ok', 'mysqldump is available.');
            }
        } catch (Throwable) {
            // Not found: reported below
        }

        return self::row('Backup tool', 'error', 'mysqldump cannot be run. Set BACKUP_MYSQLDUMP in .env to its full path, e.g. D:\xampp\mysql\bin\mysqldump.exe.');
    }

    /** The second copy on another PC or NAS (BACKUP_COPY_PATH), checked as the web server's account sees it */
    private static function backupCopy(): array
    {
        $path = config('backup.copy_path');
        if (!$path) {
            return self::row('Backup copy', 'warning', 'No second copy: set BACKUP_COPY_PATH in .env to a shared folder on another PC or NAS (docs/backups.md).');
        }
        if (!File::isDirectory($path)) {
            return self::row('Backup copy', 'error', "{$path} can't be opened from here. The scheduler task's user may still reach it; Weekly backup above shows whether the copy worked.");
        }

        return File::isWritable($path)
            ? self::row('Backup copy', 'ok', "{$path} is reachable.")
            : self::row('Backup copy', 'warning', "{$path} is reachable but read-only for this account; the scheduler task's user needs Change permission.");
    }

    private static function migrations(): array
    {
        $migrator = app('migrator');
        $ran = $migrator->getRepository()->getRan();
        $pending = array_values(array_diff(array_keys($migrator->getMigrationFiles(database_path('migrations'))), $ran));

        return $pending
            ? self::row('Database updates', 'error', count($pending) . ' migration(s) not run yet: back up, then run `php artisan migrate:reconcile` and `php artisan migrate --force` (docs/deploy.md).')
            : self::row('Database updates', 'ok', 'All migrations have run.');
    }

    /** Uploaded Records files and backups are written under storage/app */
    private static function storage(): array
    {
        $folders = array_filter([storage_path('app/private'), storage_path('app/public'), storage_path('logs')], fn ($folder) => !File::isWritable($folder));

        return $folders
            ? self::row('Storage', 'error', 'Not writable: ' . implode(', ', $folders) . '. Uploads, backups or logs will fail.')
            : self::row('Storage', 'ok', 'Uploads, backups and logs can be written.');
    }

    private static function row(string $name, string $status, string $message): array
    {
        return compact('name', 'status', 'message');
    }
}
