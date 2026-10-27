<?php

namespace App\Console\Commands;

use App\Support\JobRuns;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use RuntimeException;
use Symfony\Component\Finder\Finder;
use ZipArchive;

/**
 * One zip with the database (mysqldump) and the uploaded files, then old backups are pruned. Runs nightly
 * (routes/console.php); see docs/backups.md for restoring.
 */
class BackupRun extends Command
{
    protected $signature = 'backup:run {--no-files : Back up the database only}';

    protected $description = 'Back up the database and uploaded files to a zip, and delete old backups';

    /** Backup zips are named pbo-mbm-YYYY-MM-DD-HHMMSS.zip */
    public const PREFIX = 'pbo-mbm-';

    public function handle(): int
    {
        $dir = rtrim((string) config('backup.path'), '\\/');
        $zipPath = $dir . DIRECTORY_SEPARATOR . self::PREFIX . now()->format('Y-m-d-His') . '.zip';
        $dump = null;

        try {
            File::ensureDirectoryExists($dir);
            $dump = $this->dumpDatabase();

            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException("Couldn't create {$zipPath}.");
            }
            $zip->addFile($dump, 'database.sql');
            $files = $this->option('no-files') ? 0 : $this->addFolders($zip);
            if (!$zip->close()) {
                throw new RuntimeException("Couldn't write {$zipPath}.");
            }

            $pruned = self::prune($dir, (int) config('backup.keep_days'));
            $size = round(filesize($zipPath) / 1048576, 1);
            $message = "Backup saved: {$zipPath} ({$size} MB, database + {$files} files)" . ($pruned ? ", {$pruned} old backup(s) deleted" : '');
        } catch (\Throwable $e) {
            if (is_file($zipPath)) @unlink($zipPath);
            Log::error('Backup failed: ' . $e->getMessage());
            JobRuns::record('backup', false, 'Backup failed: ' . $e->getMessage());
            $this->error('Backup failed: ' . $e->getMessage());

            return self::FAILURE;
        } finally {
            if ($dump && is_file($dump)) @unlink($dump);
        }

        // The second copy, off this server (another PC or a network share): a failure here keeps the backup above but
        // is reported, since a backup only on this server doesn't survive losing it
        if ($copyDir = rtrim((string) config('backup.copy_path'), '\\/')) {
            try {
                $copied = self::copyTo($zipPath, $copyDir);
                $prunedCopies = self::prune($copyDir, (int) config('backup.copy_keep_days'));
                $message .= "; copied to {$copied}" . ($prunedCopies ? " ({$prunedCopies} old copies deleted)" : '');
            } catch (\Throwable $e) {
                $message .= "; but copying it to {$copyDir} failed: " . $e->getMessage();
                Log::error($message);
                JobRuns::record('backup', false, $message);
                $this->error($message);

                return self::FAILURE;
            }
        }

        Log::info($message);
        JobRuns::record('backup', true, $message);
        $this->info($message);

        return self::SUCCESS;
    }

    /** Copies the backup into $dir and checks the copy is complete; returns the copy's path */
    public static function copyTo(string $zipPath, string $dir): string
    {
        File::ensureDirectoryExists($dir);
        $target = $dir . DIRECTORY_SEPARATOR . basename($zipPath);
        if (!@copy($zipPath, $target)) {
            throw new RuntimeException(error_get_last()['message'] ?? "couldn't write {$target}");
        }
        clearstatcache(true, $target);
        if (filesize($target) !== filesize($zipPath)) {
            @unlink($target);
            throw new RuntimeException("the copy at {$target} is incomplete");
        }

        return $target;
    }

    /**
     * mysqldump into a temporary .sql file. The credentials go in a temporary options file (deleted right after),
     * so the password never shows in the process list.
     */
    private function dumpDatabase(): string
    {
        $connection = config('database.connections.' . config('database.default'));
        if (!in_array($connection['driver'] ?? null, ['mysql', 'mariadb'], true)) {
            throw new RuntimeException('Only MySQL/MariaDB databases can be backed up (this one is ' . ($connection['driver'] ?? 'unknown') . ').');
        }

        $options = tempnam(sys_get_temp_dir(), 'bkp');
        $dump = tempnam(sys_get_temp_dir(), 'bkp');
        $quote = fn ($value) => '"' . addcslashes((string) $value, '"\\') . '"';
        file_put_contents($options, implode("\n", [
            '[client]',
            'user=' . $quote($connection['username']),
            'password=' . $quote($connection['password']),
            'host=' . $quote($connection['host']),
            'port=' . $quote($connection['port']),
        ]) . "\n");

        try {
            $result = Process::timeout((int) config('backup.timeout'))->run([
                config('backup.mysqldump'),
                '--defaults-extra-file=' . $options,
                '--single-transaction',
                '--routines',
                '--triggers',
                '--default-character-set=utf8mb4',
                '--result-file=' . $dump,
                $connection['database'],
            ]);
        } finally {
            @unlink($options);
        }

        if (!$result->successful() || !filesize($dump)) {
            @unlink($dump);
            throw new RuntimeException('mysqldump failed: ' . trim($result->errorOutput() ?: $result->output() ?: 'no output'));
        }

        return $dump;
    }

    /** The uploaded files, under their folder name in the zip */
    private function addFolders(ZipArchive $zip): int
    {
        $count = 0;
        foreach (config('backup.folders', []) as $name => $folder) {
            if (!is_dir($folder)) continue;
            foreach (Finder::create()->files()->in($folder)->ignoreDotFiles(true) as $file) {
                $zip->addFile($file->getRealPath(), $name . '/' . str_replace('\\', '/', $file->getRelativePathname()));
                $count++;
            }
        }

        return $count;
    }

    /**
     * Deletes backups older than $keepDays; the newest backup is always kept, even when it is old (so a server whose
     * backups stopped still has its last one). Returns how many were deleted.
     */
    public static function prune(string $dir, int $keepDays): int
    {
        if ($keepDays < 1 || !is_dir($dir)) return 0;

        $backups = collect(glob($dir . DIRECTORY_SEPARATOR . self::PREFIX . '*.zip') ?: [])
            ->sortByDesc(fn ($path) => filemtime($path))
            ->values();
        $cutoff = now()->subDays($keepDays)->getTimestamp();

        return $backups->slice(1)
            ->filter(fn ($path) => filemtime($path) < $cutoff)
            ->each(fn ($path) => @unlink($path))
            ->count();
    }
}
