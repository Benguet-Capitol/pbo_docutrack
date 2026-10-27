<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * How the background jobs went (job_runs table), for the Dashboard's system status: a job is "ok", "failed" (its last
 * run failed), "overdue" (no success for longer than expected: the scheduler may have stopped) or "never" (no run yet).
 */
class JobRuns
{
    /** Each job: its label and how long after a success it counts as overdue */
    public const JOBS = [
        'backup' => ['label' => 'Nightly backup', 'overdue_after_hours' => 36],
        'hris_sync' => ['label' => 'HRIS employee sync', 'overdue_after_hours' => 24 * 33],
    ];

    /** Runs kept per job (older ones are removed) */
    private const KEEP = 60;

    /** Records a run. Never throws: reporting must not break the job. */
    public static function record(string $job, bool $succeeded, ?string $message = null): void
    {
        try {
            DB::table('job_runs')->insert([
                'job' => $job,
                'succeeded' => $succeeded,
                'message' => $message !== null ? mb_substr($message, 0, 2000) : null,
                'ran_at' => now(),
            ]);

            $keep = DB::table('job_runs')->where('job', $job)->orderByDesc('id')->skip(self::KEEP)->take(1)->value('id');
            if ($keep) {
                DB::table('job_runs')->where('job', $job)->where('id', '<=', $keep)->delete();
            }
        } catch (\Throwable $e) {
            Log::warning("Couldn't record the {$job} run: " . $e->getMessage());
        }
    }

    /** Each job's status for the Dashboard */
    public static function statuses(): array
    {
        return collect(self::JOBS)->map(function (array $job, string $key) {
            $last = DB::table('job_runs')->where('job', $key)->orderByDesc('id')->first();
            $lastSuccess = DB::table('job_runs')->where('job', $key)->where('succeeded', true)->orderByDesc('id')->value('ran_at');
            $lastSuccess = $lastSuccess ? Carbon::parse($lastSuccess) : null;

            $status = match (true) {
                !$last => 'never',
                !$last->succeeded => 'failed',
                !$lastSuccess || $lastSuccess->lt(now()->subHours($job['overdue_after_hours'])) => 'overdue',
                default => 'ok',
            };

            return [
                'job' => $key,
                'label' => $job['label'],
                'status' => $status,
                'last_run_at' => $last ? Carbon::parse($last->ran_at)->toIso8601String() : null,
                'last_success_at' => $lastSuccess?->toIso8601String(),
                'message' => $last?->message,
            ];
        })->values()->all();
    }
}
