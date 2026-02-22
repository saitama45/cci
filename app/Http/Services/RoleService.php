<?php

namespace App\Http\Services;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleService
{
    /**
     * Centralized module configuration
     * Each module contains a label and standard permissions.
     */
    public static array $modules = [
        'dashboard' => [
            'label' => 'Dashboard',
            'permissions' => ['view']
        ],
        'users' => [
            'label' => 'Users',
            'permissions' => ['view', 'create', 'edit', 'delete']
        ],
        'roles' => [
            'label' => 'Roles & Permissions',
            'permissions' => ['view']
        ],
        'companies' => [
            'label' => 'Companies',
            'permissions' => ['view', 'create', 'edit', 'delete']
        ],
        'projects' => [
            'label' => 'Projects',
            'permissions' => ['view', 'create', 'edit', 'delete']
        ],
        'units' => [
            'label' => 'Units / Lots',
            'permissions' => ['view', 'create', 'edit', 'delete']
        ],
        'price_lists' => [
            'label' => 'Price Lists',
            'permissions' => ['view', 'create', 'edit', 'delete']
        ],
        'customers' => [
            'label' => 'Customers',
            'permissions' => ['view', 'show', 'create', 'edit', 'delete', 'export', 'upload_documents', 'delete_documents']
        ],
        'brokers' => [
            'label' => 'Brokers',
            'permissions' => ['view', 'create', 'edit', 'delete']
        ],
        'document_requirements' => [
            'label' => 'Document Requirements',
            'permissions' => ['view', 'create', 'edit', 'delete']
        ],
        'reservations' => [
            'label' => 'Reservations',
            'permissions' => ['view', 'show', 'create', 'edit', 'delete']
        ],
        'accounting' => [
            'label' => 'Accounting Reports',
            'permissions' => ['view']
        ],
        'journal_entries' => [
            'label' => 'Journal Entries',
            'permissions' => ['view', 'create', 'show']
        ],
        'payments' => [
            'label' => 'Payments / Collections',
            'permissions' => ['view', 'show', 'create']
        ],
        'chart_of_accounts' => [
            'label' => 'Chart of Accounts',
            'permissions' => ['view', 'create', 'edit']
        ],
        'collections' => [
            'label' => 'Collections',
            'permissions' => ['view']
        ],
    ];

    /**
     * Standard sort order for permissions
     */
    public static array $permissionOrder = ['view', 'show', 'create', 'edit', 'delete', 'export', 'approve', 'cancel'];

    /**
     * Get all roles with their permissions
     */
    public static function getAllRoles()
    {
        return Role::with('permissions')->get();
    }

    /**
     * Get all permissions
     */
    public static function getAllPermissions()
    {
        return Permission::all();
    }

    /**
     * Get permissions grouped by category with proper labels and sorting
     */
    public static function getPermissionsByCategory()
    {
        $permissions = Permission::all();
        $grouped = [];
        $processedIds = [];

        foreach (self::$modules as $key => $module) {
            $modulePermissions = $permissions->filter(function ($permission) use ($key) {
                return explode('.', $permission->name)[0] === $key;
            });

            if ($modulePermissions->isNotEmpty()) {
                foreach ($modulePermissions as $p) {
                    $processedIds[] = $p->id;
                }

                // Sort permissions based on self::$permissionOrder
                $sortedPermissions = $modulePermissions->sort(function ($a, $b) {
                    $actionA = explode('.', $a->name)[1] ?? '';
                    $actionB = explode('.', $b->name)[1] ?? '';
                    
                    $indexA = array_search($actionA, self::$permissionOrder);
                    $indexB = array_search($actionB, self::$permissionOrder);

                    // If not in order list, push to end
                    if ($indexA === false && $indexB === false) return strcmp($actionA, $actionB);
                    if ($indexA === false) return 1;
                    if ($indexB === false) return -1;
                    
                    return $indexA - $indexB;
                });

                $grouped[$module['label']] = $sortedPermissions->values()->toArray();
            }
        }

        // Handle permissions that don't belong to any defined module
        $remainingPermissions = $permissions->reject(function ($permission) use ($processedIds) {
            return in_array($permission->id, $processedIds);
        });

        if ($remainingPermissions->isNotEmpty()) {
            $grouped['Other'] = $remainingPermissions->values()->toArray();
        }

        return $grouped;
    }

    /**
     * Create a new role with permissions
     */
    public static function createRole($name, $permissions = [])
    {
        $role = Role::create(['name' => $name]);
        
        if (!empty($permissions)) {
            $role->givePermissionTo($permissions);
        }
        
        return $role;
    }

    /**
     * Update role permissions
     */
    public static function updateRolePermissions($roleId, $permissions)
    {
        $role = Role::findById($roleId);
        $role->syncPermissions($permissions);
        
        return $role;
    }

    /**
     * Delete a role
     */
    public static function deleteRole($roleId)
    {
        $role = Role::findById($roleId);
        return $role->delete();
    }

    /**
     * Check if user has permission
     */
    public static function userHasPermission($user, $permission)
    {
        return $user->can($permission);
    }

    /**
     * Get user roles
     */
    public static function getUserRoles($user)
    {
        return $user->roles;
    }

    /**
     * Assign role to user
     */
    public static function assignRoleToUser($user, $roleName)
    {
        return $user->assignRole($roleName);
    }

    /**
     * Remove role from user
     */
    public static function removeRoleFromUser($user, $roleName)
    {
        return $user->removeRole($roleName);
    }
}