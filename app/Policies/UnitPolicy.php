<?php

namespace App\Policies;

use App\Models\Unit;
use App\Models\User;

class UnitPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('units.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Unit $unit): bool
    {
        return $user->hasPermissionTo('units.view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('units.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Unit $unit): bool
    {
        return $user->hasPermissionTo('units.edit');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Unit $unit): bool
    {
        return $user->hasPermissionTo('units.delete');
    }
}