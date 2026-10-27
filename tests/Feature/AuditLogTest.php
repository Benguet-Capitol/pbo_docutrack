<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** The audit trail: failed sign-ins, lockouts and every call to the shared Leaves API */
class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_wrong_password_is_logged_against_the_account_without_the_password(): void
    {
        $user = $this->makeUser('Reviewer');

        $this->post('/login', ['username' => $user->username, 'password' => 'not-the-password']);

        $log = ActivityLog::where('action', 'login_failed')->sole();
        $this->assertSame($user->id, $log->user_id);
        $this->assertStringContainsString('wrong password', $log->description);
        $this->assertStringNotContainsString('not-the-password', json_encode($log->toArray()));
    }

    public function test_an_unknown_username_is_logged_as_a_guest(): void
    {
        $this->post('/login', ['username' => 'nobody-here', 'password' => 'secret123']);

        $log = ActivityLog::where('action', 'login_failed')->sole();
        $this->assertNull($log->user_id);
        $this->assertSame('Guest', $log->user_name);
        $this->assertSame('nobody-here', $log->properties['username']);
    }

    public function test_too_many_failed_attempts_are_logged_as_a_lockout(): void
    {
        $user = $this->makeUser('Reviewer');

        foreach (range(1, 6) as $attempt) {
            $this->post('/login', ['username' => $user->username, 'password' => 'wrong']);
        }

        $this->assertSame(1, ActivityLog::where('action', 'login_locked')->count());
    }

    public function test_every_leaves_api_call_is_logged_with_the_employee_asked_for(): void
    {
        config(['services.leaves_api.keys' => ['first-key', 'registry-key']]);
        $this->makeEmployee(['employee_id' => '01323001799']);

        $this->getJson('/api/v1/leaves/employees/01323001799?year=2026', ['X-API-Key' => 'registry-key'])->assertOk();

        $log = ActivityLog::where('action', 'api_access')->sole();
        $this->assertSame('Shared API', $log->module);
        $this->assertSame('API client', $log->user_name);
        $this->assertSame('01323001799', $log->subject_label);
        $this->assertStringStartsWith('key #2 (', $log->properties['client']);
        $this->assertSame(200, $log->properties['status']);
        // The key itself is never stored
        $this->assertStringNotContainsString('registry-key', json_encode($log->toArray()));
    }

    public function test_refused_api_calls_are_logged(): void
    {
        config(['services.leaves_api.keys' => ['registry-key']]);

        $this->getJson('/api/v1/leaves/employees/01323001799', ['X-API-Key' => 'guessed-key'])->assertUnauthorized();
        $this->getJson('/api/v1/leaves/employees/01323001799')->assertUnauthorized();

        $this->assertSame(2, ActivityLog::where('action', 'api_denied')->count());
        $this->assertStringNotContainsString('guessed-key', ActivityLog::pluck('properties')->toJson());
    }

    public function test_records_api_listing_is_not_logged(): void
    {
        config(['services.records_api.keys' => ['records-key']]);

        $this->getJson('/api/v1/records/categories', ['X-API-Key' => 'records-key'])->assertOk();

        $this->assertSame(0, ActivityLog::where('module', 'Shared API')->count());
    }
}
