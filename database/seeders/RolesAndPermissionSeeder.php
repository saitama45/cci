<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Define permissions
        $permissions = [
            // Dashboard
            'dashboard.view' => 'View dashboard',
            
            // Users
            'users.view' => 'View users',
            'users.create' => 'Create users',
            'users.edit' => 'Edit users',
            'users.delete' => 'Delete users',
            
            // Roles & Permissions
            'roles.view' => 'View roles',
            'roles.create' => 'Create roles',
            'roles.edit' => 'Edit roles',
            'roles.delete' => 'Delete roles',

            // Companies
            'companies.view' => 'View companies',
            'companies.create' => 'Create companies',
            'companies.edit' => 'Edit companies',
            'companies.delete' => 'Delete companies',
        ];

        // Create permissions
        foreach ($permissions as $name => $description) {
            Permission::firstOrCreate(['name' => $name]);
        }

        // Create roles
        $admin = Role::firstOrCreate(['name' => 'Admin']);
        $userRole = Role::firstOrCreate(['name' => 'User']);

        // Assign permissions to roles
        $admin->givePermissionTo(Permission::all());
        
        $userRole->givePermissionTo([
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