<?php

namespace App\Policies;

use App\Models\Salon;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class SalonPolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Salon $salon): bool
    {
        return $user->id === $salon->user_id;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Salon $salon): bool
    {
        return $user->id === $salon->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Salon $salon): bool
    {
        return $user->id === $salon->user_id;
    }

    /**
     * Determine whether the user can select the salon as active.
     */
    public function selectActive(User $user, Salon $salon): bool
    {
        return $user->id === $salon->user_id;
    }

    /**
     * Determine whether the user can manage resources for the salon.
     */
    public function manageResources(User $user, Salon $salon): bool
    {
        return $user->id === $salon->user_id;
    }

    /**
     * Determine whether the user can view dashboard for the salon.
     */
    public function viewDashboard(User $user, Salon $salon): bool
    {
        return $this->manageResources($user, $salon);
    }

    public function viewAny(User $user): bool
    {
        return $user->exists();
    }

    public function create(User $user): bool
    {
        return $user->exists();
    }
}
