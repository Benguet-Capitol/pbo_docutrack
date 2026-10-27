<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Records uploads (Administrative records included) were on the public disk, which anyone can open by URL once
 * `storage:link` exists. They move to the private "records" disk, opened only through the permission-checked endpoints.
 * The stored paths ("records/<file>") stay the same. Safe to run again: files already moved are skipped.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->move('public', 'records');
    }

    public function down(): void
    {
        $this->move('records', 'public');
    }

    private function move(string $from, string $to): void
    {
        $source = Storage::disk($from);
        $target = Storage::disk($to);

        DB::table('records')->whereNotNull('image_path')->pluck('image_path')->each(function (string $path) use ($source, $target) {
            if (!$source->exists($path) || $target->exists($path)) {
                return;
            }
            // Copy, check, then delete: a failure part-way never loses a file
            $target->writeStream($path, $source->readStream($path));
            if ($target->exists($path) && $target->size($path) === $source->size($path)) {
                $source->delete($path);
            }
        });
    }
};
