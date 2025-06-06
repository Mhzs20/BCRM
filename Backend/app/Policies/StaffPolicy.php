<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Staff;
use App\Models\Salon;

class StaffPolicy
{
    public function viewAny(User $user, Salon $salon): bool
    {
        return $user->id === $salon->user_id;
    }

    public function view(User $user, Staff $staff): bool
    {
        return $user->id === $staff->salon->user_id;
    }

    public function create(User $user, Salon $salon): bool
    {
        return $user->id === $salon->user_id;
    }

    public function update(User $user, Staff $staff): bool
    {
        return $user->id === $staff->salon->user_id;
    }

    public function delete(User $user, Staff $staff): bool
    {
        return $user->id === $staff->salon->user_id;
    }
}
