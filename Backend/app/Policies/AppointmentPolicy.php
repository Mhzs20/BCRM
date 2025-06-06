<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;
use App\Models\Salon;

class AppointmentPolicy
{
    public function viewAny(User $user, Salon $salon): bool
    {
        return $user->salon_id === $salon->id;
    }

    public function view(User $user, Appointment $appointment): bool
    {
        return $user->salon_id === $appointment->salon_id;
    }

    public function create(User $user, Salon $salon): bool
    {
        return $user->salon_id === $salon->id;
    }

    public function update(User $user, Appointment $appointment): bool
    {
        return $user->salon_id === $appointment->salon_id;
    }

    public function delete(User $user, Appointment $appointment): bool
    {
        return $user->salon_id === $appointment->salon_id;
    }
}
