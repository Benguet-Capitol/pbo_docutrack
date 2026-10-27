<?php

namespace Tests\Feature;

use App\Console\Commands\BackupRun;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/** backup:run (the dump itself needs MySQL and is checked by hand, see docs/backups.md); pruning old backups */
class BackupTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'pbo-backup-test-' . uniqid();
        File::ensureDirectoryExists($this->dir);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->dir);
        parent::tearDown();
    }

    private function backupFromDaysAgo(int $days): string
    {
        $path = $this->dir . DIRECTORY_SEPARATOR . BackupRun::PREFIX . now()->subDays($days)->format('Y-m-d-His') . ".zip";
        file_put_contents($path, 'zip');
        touch($path, now()->subDays($days)->getTimestamp());

        return $path;
    }

    public function test_backups_older_than_the_limit_are_deleted(): void
    {
        $recent = $this->backupFromDaysAgo(2);
        $old = $this->backupFromDaysAgo(40);
        $other = $this->dir . DIRECTORY_SEPARATOR . 'notes.txt';
        file_put_contents($other, 'not a backup');
        touch($other, now()->subDays(90)->getTimestamp());

        $this->assertSame(1, BackupRun::prune($this->dir, 30));

        $this->assertFileExists($recent);
        $this->assertFileDoesNotExist($old);
        $this->assertFileExists($other);
    }

    public function test_the_newest_backup_is_kept_even_when_old(): void
    {
        $newest = $this->backupFromDaysAgo(60);
        $older = $this->backupFromDaysAgo(90);

        BackupRun::prune($this->dir, 30);

        $this->assertFileExists($newest);
        $this->assertFileDoesNotExist($older);
    }

    public function test_the_second_copy_is_written_complete(): void
    {
        $zip = $this->backupFromDaysAgo(0);
        $copyDir = $this->dir . DIRECTORY_SEPARATOR . 'offsite';

        $copy = BackupRun::copyTo($zip, $copyDir);

        $this->assertFileExists($copy);
        $this->assertSame(file_get_contents($zip), file_get_contents($copy));
    }

    public function test_an_unwritable_second_copy_location_is_an_error(): void
    {
        $zip = $this->backupFromDaysAgo(0);
        // A file where the folder should be
        $blocked = $this->dir . DIRECTORY_SEPARATOR . 'blocked';
        file_put_contents($blocked, 'not a folder');

        $this->expectException(\Throwable::class);
        BackupRun::copyTo($zip, $blocked);
    }

    public function test_a_database_that_is_not_mysql_fails_without_leaving_a_zip(): void
    {
        config(['backup.path' => $this->dir]);

        $this->artisan('backup:run')->assertFailed();

        $this->assertSame([], glob($this->dir . DIRECTORY_SEPARATOR . '*.zip'));
    }
}
