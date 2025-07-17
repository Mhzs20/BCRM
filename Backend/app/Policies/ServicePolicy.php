<?php

namespace App\Policies;

use App\Models\Service;
use App\Models\User;
use App\Models\Salon;

class ServicePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user, Salon $salon): bool
    {
        return $user->id === $salon->user_id;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Service $service, ?Salon $salon = null): bool
    {
        $salonToCheck = $salon ?? $service->salon;

        return $user->id === $salonToCheck->user_id && $service->salon_id === $salonToCheck->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, Salon $salon): bool
    {
        return $user->id === $salon->user_id;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Service $service, ?Salon $salon = null): bool
    {
        $salonToCheck = $salon ?? $service->salon;
        return $user->id === $salonToCheck->user_id && $service->salon_id === $salonToCheck->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Service $service, ?Salon $salon = null): bool
    {
        $salonToCheck = $salon ?? $service->salon;
        return $user->id === $salonToCheck->user_id && $service->salon_id === $salonToCheck->id;
    }
}
