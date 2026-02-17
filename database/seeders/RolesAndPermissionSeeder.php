<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Http\Services\RoleService;

class RolesAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Define permissions from central RoleService
        foreach (RoleService::$modules as $moduleKey => $config) {
            foreach ($config['permissions'] as $action) {
                $permissionName = "{$moduleKey}.{$action}";
                Permission::firstOrCreate(['name' => $permissionName]);
            }
        }

        // Create roles
        $admin = Role::firstOrCreate(['name' => 'Admin']);
        $userRole = Role::firstOrCreate(['name' => 'User']);

        // Assign permissions to roles
        $admin->syncPermissions(Permission::all());
        
        $userRole->syncPermissions([
            'dashboard.view',
        ]);

        // Create default users
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'System Administrator',
                'password' => Hash::make('admin123'),
                'department' => 'IT',
                'position' => 'System Administrator',
                'email_verified_at' => now(),
            ]
        );
        $adminUser->assignRole('Admin');

        $regularUser = User::firstOrCreate(
            ['email' => 'user@gmail.com'],
            [
                'name' => 'Regular User',
                'password' => Hash::make('user123'),
                'department' => 'Sales',
                'position' => 'Sales Associate',
                'email_verified_at' => now(),
            ]
        );
        $regularUser->assignRole('User');

        $this->command->info('✅ Roles and permissions created successfully!');
        $this->command->info('  - Admin: admin@gmail.com / admin123');
        $this->command->info('  - User: user@gmail.com / user123');
    }
}
