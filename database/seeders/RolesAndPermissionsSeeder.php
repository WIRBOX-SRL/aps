<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // create roles
        $roleSuperAdmin = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $roleAdmin = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $roleUser = Role::firstOrCreate(['name' => 'User', 'guard_name' => 'web']);

        // Assign roles to existing users
        $superAdmin = User::where('email', 'superadmin@example.com')->first();
        if ($superAdmin) {
            $superAdmin->assignRole($roleSuperAdmin);
        }
        $admin = User::where('email', 'admin@example.com')->first();
        if ($admin) {
            $admin->assignRole($roleAdmin);
        }
        $user = User::where('email', 'test@example.com')->first();
        if ($user) {
            $user->assignRole($roleUser);
        }
    }
}
