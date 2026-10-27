<?php

namespace Tests;

use App\Models\Employee;
use App\Models\Office;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\RoleService;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Role permissions are cached for the request (a static): start every test from the database
        RoleService::clearCache();
    }

    /** The Provincial Budget Office (the leave, slip and order modules are for its staff) */
    protected function pboOffice(): Office
    {
        return Office::find(Employee::PBO_OFFICE_ID) ?? Office::forceCreate([
            'id' => Employee::PBO_OFFICE_ID,
            'office_abbreviation' => 'PBO',
            'office_name' => 'Provincial Budget Office',
        ]);
    }

    /** A PBO employee with an HRIS-style employee ID */
    protected function makeEmployee(array $attributes = []): Employee
    {
        static $sequence = 0;
        $sequence++;

        return Employee::create($attributes + [
            'employee_id' => '0132300' . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT),
            'name' => "Test Employee {$sequence}",
            'position_title' => 'Administrative Assistant II',
            'office_id' => $this->pboOffice()->id,
        ]);
    }

    /** A user with a role, linked to an employee when one is given */
    protected function makeUser(string $role, ?Employee $employee = null): User
    {
        return User::factory()->role($role)->create(['employee_id' => $employee?->id]);
    }

    /** A custom role holding exactly these permission keys (e.g. a clerk who files leaves but sees only their own) */
    protected function makeRole(string $name, array $permissionKeys): Role
    {
        $role = Role::create(['name' => $name, 'description' => $name, 'is_system' => false]);
        $role->permissions()->sync(Permission::whereIn('key', $permissionKeys)->pluck('id'));
        RoleService::clearCache();

        return $role;
    }
}
