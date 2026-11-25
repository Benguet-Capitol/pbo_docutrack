<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A pass slip's immediate supervisors (Recommending Approval): several when the requesting employees are under
 * different supervisors. pass_slips.recommending_approval_employee_id stays as the first of them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pass_slip_recommending_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pass_slip_id')->constrained('pass_slips')->onDelete('cascade');
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();

            $table->unique(['pass_slip_id', 'employee_id']);
        });

        // The existing slips' one supervisor
        $now = now();
        DB::table('pass_slips')->whereNotNull('recommending_approval_employee_id')->orderBy('id')
            ->chunkById(500, function ($slips) use ($now) {
                DB::table('pass_slip_recommending_approvals')->insert($slips->map(fn ($slip) => [
                    'pass_slip_id' => $slip->id,
                    'employee_id' => $slip->recommending_approval_employee_id,
                    'position' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all());
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('pass_slip_recommending_approvals');
    }
};
