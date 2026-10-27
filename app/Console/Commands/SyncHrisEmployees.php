<?php

namespace App\Console\Commands;

use App\Services\HrisEmployeeSync;
use App\Support\JobRuns;
use Illuminate\Console\Command;
use RuntimeException;

/**
 * Monthly HRIS employee sync (scheduled in routes/console.php); same as the Sync from HRIS button.
 */
class SyncHrisEmployees extends Command
{
    protected $signature = 'employees:sync-hris';

    protected $description = 'Add new HRIS employees (permanent, casual, COS) and update the ones already here';

    public function handle(HrisEmployeeSync $sync): int
    {
        try {
            $result = $sync->sync(scheduled: true);
        } catch (RuntimeException $e) {
            $this->error('Sync from HRIS failed: ' . $e->getMessage());
            JobRuns::record('hris_sync', false, 'Monthly sync failed: ' . $e->getMessage());

            return self::FAILURE;
        }

        $this->info($result['message']);
        JobRuns::record('hris_sync', true, $result['message']);

        return self::SUCCESS;
    }
}
