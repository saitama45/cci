<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Company;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Http\Services\RoleService;

class RolesAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Default Company
        $defaultCompany = Company::firstOrCreate(
            ['code' => 'CCI'],
            [
                'name' => 'City Communities Inc.',
                'description' => 'Real Estate Property Developer',
                'is_active' => true,
            ]
        );

        // 2. Define permissions from central RoleService
        foreach (RoleService::$modules as $moduleKey => $config) {
            foreach ($config['permissions'] as $action) {
                $permissionName = "{$moduleKey}.{$action}";
                Permission::firstOrCreate(['name' => $permissionName]);
            }
        }

        // 3. Create roles
        $admin = Role::firstOrCreate(['name' => 'Admin']);
        $userRole = Role::firstOrCreate(['name' => 'User']);

        // 4. Assign permissions to roles
        $admin->syncPermissions(Permission::all());
        
        $userRole->syncPermissions([
            'dashboard.view',
        ]);

        // 5. Create default users with company association
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'System Administrator',
                'password' => Hash::make('admin123'),
                'department' => 'IT',
                'position' => 'System Administrator',
                'email_verified_at' => now(),
                'company_id' => $defaultCompany->id,
            ]
        );
        $adminUser->assignRole('Admin');

        // Force update company_id if user existed but was NULL
        if (!$adminUser->company_id) {
            $adminUser->update(['company_id' => $defaultCompany->id]);
        }

        $regularUser = User::firstOrCreate(
            ['email' => 'user@gmail.com'],
            [
                'name' => 'Regular User',
                'password' => Hash::make('user123'),
                'department' => 'Sales',
                'position' => 'Sales Associate',
                'email_verified_at' => now(),
                'company_id' => $defaultCompany->id,
            ]
        );
        $regularUser->assignRole('User');

        if (!$regularUser->company_id) {
            $regularUser->update(['company_id' => $defaultCompany->id]);
        }

        $this->command->info('✅ Roles, permissions, and default company created successfully!');
    }
}
