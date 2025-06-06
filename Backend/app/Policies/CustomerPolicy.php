<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\Salon;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CustomerPolicy
{
    /**
     * Determine whether the user can view any models for the given salon.
     */
    public function viewAny(User $user, Salon $salon): bool
    {
        return $user->id === $salon->user_id;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Customer $customer): bool
    {
        return $user->id === $customer->salon->user_id;
    }

    /**
     * Determine whether the user can create models in the given salon.
     */
    public function create(User $user, Salon $salon): bool
    {
        return $user->id === $salon->user_id;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Customer $customer): bool
    {
        return $user->id === $customer->salon->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Customer $customer): bool
    {
        return $user->id === $customer->salon->user_id;
    }

    /**
     * Determine whether the user can bulk delete customers from the given salon.
     */
    public function deleteAny(User $user, Salon $salon): bool
    {
        return $user->id === $salon->user_id;
    }

    /**
     * Determine whether the user can import customers into the given salon.
     */
    public function import(User $user, Salon $salon): bool
    {
        return $user->id === $salon->user_id;
    }
}
