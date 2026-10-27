<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/** migrate:reconcile: pending migrations already in the database are found, and recorded only with --apply */
class ReconcileMigrationsTest extends TestCase
{
    use RefreshDatabase;

    private const ADD_COLUMN = '2026_03_02_000004_add_city_class_to_municipalities_table';
    private const CREATE_TABLES = '2026_03_02_000000_create_document_checklist_templates_table';
    private const WITH_DATA = '2026_10_23_000000_add_leaves_view_all_permission';

    private function forget(string $migration): void
    {
        DB::table('migrations')->where('migration', $migration)->delete();
    }

    private function recorded(string $migration): bool
    {
        return DB::table('migrations')->where('migration', $migration)->exists();
    }

    public function test_nothing_pending_says_so(): void
    {
        $this->artisan('migrate:reconcile')->expectsOutputToContain('No pending migrations')->assertSuccessful();
    }

    public function test_migrations_already_in_the_database_are_found_but_recorded_only_with_apply(): void
    {
        $this->forget(self::ADD_COLUMN);
        $this->forget(self::CREATE_TABLES);

        $this->artisan('migrate:reconcile')->expectsOutputToContain('2 migration(s) are already in the database')->assertSuccessful();
        $this->assertFalse($this->recorded(self::ADD_COLUMN));

        $this->artisan('migrate:reconcile --apply')->expectsOutputToContain('Recorded 2 migration(s) as run')->assertSuccessful();
        $this->assertTrue($this->recorded(self::ADD_COLUMN));
        $this->assertTrue($this->recorded(self::CREATE_TABLES));
    }

    public function test_a_migration_whose_change_is_missing_is_left_to_run(): void
    {
        $this->forget(self::ADD_COLUMN);
        Schema::table('municipalities', fn ($table) => $table->dropColumn('city_class'));

        $this->artisan('migrate:reconcile --apply')
            ->expectsOutputToContain('Nothing to record')
            ->assertSuccessful();
        $this->assertFalse($this->recorded(self::ADD_COLUMN));

        // ...and migrate then runs it
        $this->artisan('migrate')->assertSuccessful();
        $this->assertTrue(Schema::hasColumn('municipalities', 'city_class'));
    }

    public function test_a_migration_that_changes_data_is_left_for_a_person(): void
    {
        $this->forget(self::WITH_DATA);

        $this->artisan('migrate:reconcile --apply')->expectsOutputToContain('Nothing to record')->assertSuccessful();
        $this->assertFalse($this->recorded(self::WITH_DATA));
    }
}
