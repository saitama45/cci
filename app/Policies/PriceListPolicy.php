<?php

namespace App\Policies;

use App\Models\PriceList;
use App\Models\User;

class PriceListPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('price_lists.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PriceList $priceList): bool
    {
        return $user->hasPermissionTo('price_lists.view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('price_lists.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PriceList $priceList): bool
    {
        return $user->hasPermissionTo('price_lists.edit');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PriceList $priceList): bool
    {
        return $user->hasPermissionTo('price_lists.delete');
    }
}