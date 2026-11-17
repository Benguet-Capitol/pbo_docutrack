<?php

namespace Tests\Feature;

use App\Services\ConnectionChecks;
use App\Services\ServerChecks;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * System → Connections: who may open it, HRIS tested live, the shared APIs' last accepted and refused calls,
 * and the scheduler check (stopped, or not every minute)
 */
class ConnectionsTest extends TestCase
{
    use RefreshDatabase;

    private function check(array $checks, string $name): array
    {
        return collect($checks)->firstWhere('name', $name);
    }

    public function test_only_roles_with_the_permission_open_the_page(): void
    {
        $this->actingAs($this->makeUser('Administrator'))->get('/connections')->assertOk();
        $this->actingAs($this->makeUser('Reviewer'))->get('/connections')->assertForbidden();
    }

    public function test_hris_is_tested_with_its_key(): void
    {
        config(['services.hris.url' => 'http://hris.test/api/v1', 'services.hris.key' => 'secret']);
        Http::fake(['hris.test/*' => Http::response(['data' => [['id' => 1], ['id' => 2]]])]);

        $hris = ConnectionChecks::hris();

        $this->assertSame('ok', $hris['status']);
        $this->assertSame('2 employees returned.', $hris['message']);
        Http::assertSent(fn ($request) => $request->hasHeader('X-API-KEY', 'secret'));
    }

    public function test_a_refused_hris_key_is_a_problem(): void
    {
        config(['services.hris.url' => 'http://hris.test/api/v1', 'services.hris.key' => 'wrong']);
        Http::fake(['hris.test/*' => Http::response([], 401)]);

        $this->assertSame('error', ConnectionChecks::hris()['status']);
    }

    public function test_a_shared_api_reports_its_last_accepted_and_refused_calls(): void
    {
        config(['services.leaves_api.keys' => ['good-key']]);
        $this->assertSame('warning', ConnectionChecks::sharedApi('leaves')['status'], 'no call received yet');

        $this->getJson('/api/v1/leaves/employees/unknown', ['X-API-Key' => 'good-key']);
        $this->assertSame('ok', ConnectionChecks::sharedApi('leaves')['status']);

        $this->travel(2)->minutes();
        $this->getJson('/api/v1/leaves/employees/unknown', ['X-API-Key' => 'wrong-key']);
        $leaves = ConnectionChecks::sharedApi('leaves');
        $this->assertSame('warning', $leaves['status']);
        $this->assertStringContainsString('wrong API key', $leaves['message']);
        $this->assertStringNotContainsString('good-key', json_encode(ConnectionChecks::all()));
    }

    public function test_a_shared_api_without_keys_is_not_set_up(): void
    {
        config(['services.locator_api.keys' => []]);

        $this->assertSame('not_configured', ConnectionChecks::sharedApi('locator')['status']);
    }

    public function test_the_scheduler_check_sees_a_stopped_or_slow_scheduler(): void
    {
        Storage::fake('local');
        $this->assertSame('error', $this->check(ServerChecks::all(), 'Scheduler')['status'], 'never ran');

        ServerChecks::beat();
        $this->travel(1)->minutes();
        ServerChecks::beat();
        $this->assertSame('ok', $this->check(ServerChecks::all(), 'Scheduler')['status']);

        // Task Scheduler repeating every 5 minutes
        $this->travel(5)->minutes();
        ServerChecks::beat();
        $scheduler = $this->check(ServerChecks::all(), 'Scheduler');
        $this->assertSame('warning', $scheduler['status']);
        $this->assertStringContainsString('every 5 minutes', $scheduler['message']);

        $this->travel(30)->minutes();
        $this->assertSame('error', $this->check(ServerChecks::all(), 'Scheduler')['status']);
    }
}
