<?php

namespace Tests\Feature;

use App\Models\Office;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** /api/employees: the whole list for the pickers, or one page at a time for the Employees page */
class EmployeesApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_employees_page_gets_one_page_at_a_time(): void
    {
        foreach (range(1, 25) as $i) {
            $this->makeEmployee(['name' => sprintf('Employee %02d', $i)]);
        }

        $response = $this->actingAs($this->makeUser('Administrator'))
            ->getJson('/api/employees?page=2&per_page=10&sort=name&order=asc')
            ->assertOk();

        $response->assertJsonPath('total', 25)
            ->assertJsonPath('last_page', 3)
            ->assertJsonPath('current_page', 2)
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('data.0.name', 'Employee 11');
    }

    public function test_the_employees_page_search_looks_at_names_ids_positions_and_offices(): void
    {
        $this->makeEmployee(['name' => 'Samuel B. Soliman', 'employee_id' => '01323001737']);
        $this->makeEmployee(['name' => 'Maria C. Santos', 'position_title' => 'Budget Officer III']);
        $kdh = Office::create(['office_abbreviation' => 'KDH', 'office_name' => 'Kapangan District Hospital']);
        $this->makeEmployee(['name' => 'Abigail L. Sito', 'office_id' => $kdh->id]);
        $admin = $this->makeUser('Administrator');

        $names = fn (string $search) => collect($this->actingAs($admin)->getJson('/api/employees?page=1&search=' . urlencode($search))->json('data'))->pluck('name')->all();

        $this->assertSame(['Samuel B. Soliman'], $names('soliman'));
        $this->assertSame(['Samuel B. Soliman'], $names('001737'));
        $this->assertSame(['Maria C. Santos'], $names('budget officer'));
        $this->assertSame(['Abigail L. Sito'], $names('Kapangan'));
        $this->assertSame([], $names('nobody'));
    }

    public function test_the_employees_page_rejects_an_unknown_sort_column(): void
    {
        $this->actingAs($this->makeUser('Administrator'))
            ->getJson('/api/employees?page=1&sort=password')
            ->assertUnprocessable();
    }

    public function test_the_picker_list_is_unchanged_and_carries_only_the_office_fields_used(): void
    {
        $this->makeEmployee(['name' => 'Samuel B. Soliman']);

        $rows = $this->actingAs($this->makeUser('Administrator'))->getJson('/api/employees')->assertOk()->json();

        $this->assertIsList($rows);
        $this->assertSame(['id', 'office_abbreviation', 'office_name'], array_keys($rows[0]['office']));
        $this->assertNull($rows[0]['work_office']);
    }

    public function test_an_employee_detailed_to_the_pbo_works_at_the_pbo(): void
    {
        $kdh = Office::create(['office_abbreviation' => 'KDH', 'office_name' => 'Kapangan District Hospital']);
        $this->makeEmployee(['name' => 'Samuel B. Soliman', 'office_id' => $kdh->id, 'detailed_to_pbo' => true]);

        $rows = $this->actingAs($this->makeUser('Administrator'))->getJson('/api/employees')->json();

        $this->assertSame('KDH', $rows[0]['office']['office_abbreviation']);
        $this->assertSame('PBO', $rows[0]['work_office']['office_abbreviation']);
    }

    public function test_responses_are_gzipped_for_browsers_that_accept_it(): void
    {
        foreach (range(1, 30) as $i) {
            $this->makeEmployee();
        }
        $admin = $this->makeUser('Administrator');

        $gzipped = $this->actingAs($admin)->get('/api/employees', ['Accept-Encoding' => 'gzip, deflate, br']);
        $gzipped->assertOk()->assertHeader('Content-Encoding', 'gzip');
        $this->assertCount(30, json_decode(gzdecode($gzipped->getContent()), true));

        $plain = $this->actingAs($admin)->get('/api/employees');
        $plain->assertHeaderMissing('Content-Encoding');
        $this->assertCount(30, $plain->json());
    }
}
