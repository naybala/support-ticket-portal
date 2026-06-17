<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // create permissions
        \Spatie\Permission\Models\Permission::create(['name' => 'manage users']);
        \Spatie\Permission\Models\Permission::create(['name' => 'manage roles']);
        \Spatie\Permission\Models\Permission::create(['name' => 'manage permissions']);
        \Spatie\Permission\Models\Permission::create(['name' => 'manage organizations']);
        \Spatie\Permission\Models\Permission::create(['name' => 'manage agents']);
        
        \Spatie\Permission\Models\Permission::create(['name' => 'view tickets']);
        \Spatie\Permission\Models\Permission::create(['name' => 'manage tickets']);

        // create roles and assign created permissions
        $clientRole = \Spatie\Permission\Models\Role::create(['name' => 'client']);
        $clientRole->givePermissionTo('view tickets');

        $agentRole = \Spatie\Permission\Models\Role::create(['name' => 'agent']);
        $agentRole->givePermissionTo(['view tickets', 'manage tickets']);

        $adminRole = \Spatie\Permission\Models\Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo(['manage users', 'manage organizations', 'manage agents', 'view tickets', 'manage tickets']);

        $superAdminRole = \Spatie\Permission\Models\Role::create(['name' => 'super-admin']);
        // gets all permissions via Gate::before rule; see AuthServiceProvider or AppServiceProvider
    }
}
