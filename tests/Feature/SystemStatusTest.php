<?php

namespace Tests\Feature;

use App\Support\JobRuns;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** The Dashboard's backup and HRIS sync status */
class SystemStatusTest extends TestCase
{
    use RefreshDatabase;

    private function jobStatus(string $job): array
    {
        return collect(JobRuns::statuses())->firstWhere('job', $job);
    }

    public function test_a_job_that_never_ran_says_so(): void
    {
        $this->assertSame('never', $this->jobStatus('backup')['status']);
    }

    public function test_the_last_run_decides_ok_or_failed(): void
    {
        JobRuns::record('backup', true, 'Backup saved');
        $this->assertSame('ok', $this->jobStatus('backup')['status']);

        JobRuns::record('backup', false, 'Backup failed: disk full');
        $status = $this->jobStatus('backup');
        $this->assertSame('failed', $status['status']);
        $this->assertSame('Backup failed: disk full', $status['message']);
        $this->assertNotNull($status['last_success_at']);
    }

    public function test_a_job_without_a_recent_success_is_overdue(): void
    {
        JobRuns::record('backup', true);
        DB::table('job_runs')->update(['ran_at' => now()->subDays(3)]);

        $this->assertSame('overdue', $this->jobStatus('backup')['status']);
    }

    public function test_a_failed_backup_is_recorded(): void
    {
        // The test database isn't MySQL, so the backup fails
        config(['backup.path' => sys_get_temp_dir() . '/pbo-status-test']);
        $this->artisan('backup:run')->assertFailed();

        $this->assertSame('failed', $this->jobStatus('backup')['status']);
    }

    public function test_a_failed_hris_sync_is_recorded(): void
    {
        config(['services.hris.key' => 'test', 'services.hris.url' => 'http://hris.test/api/v1']);
        Http::fake(['hris.test/*' => Http::response('down', 500)]);

        $this->artisan('employees:sync-hris')->assertFailed();

        $this->assertSame('failed', $this->jobStatus('hris_sync')['status']);
        $this->assertStringContainsString('Monthly sync failed', $this->jobStatus('hris_sync')['message']);
    }

    public function test_only_roles_with_the_permission_see_the_status(): void
    {
        $this->actingAs($this->makeUser('Administrator'))->getJson('/api/system-status')->assertOk()->assertJsonCount(2);
        $this->actingAs($this->makeUser('Reviewer'))->getJson('/api/system-status')->assertForbidden();
    }

    public function test_old_runs_are_trimmed(): void
    {
        foreach (range(1, 70) as $i) {
            JobRuns::record('backup', true);
        }

        $this->assertSame(60, DB::table('job_runs')->where('job', 'backup')->count());
    }
}
