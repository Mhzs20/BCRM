<?php

namespace App\Observers;

use App\Models\Salon;
use App\Models\Profession;
use App\Models\HowIntroduced;
use App\Models\CustomerGroup;

class SalonObserver
{
    /**
     * Handle the Salon "created" event.
     */
    public function created(Salon $salon): void
    {
        // Copy Professions
        $professions = Profession::whereNull('salon_id')->get();
        foreach ($professions as $profession) {
            $salon->professions()->firstOrCreate(
                ['name' => $profession->name],
                ['name' => $profession->name]
            );
        }

        // Copy HowIntroduced
        $howIntroduceds = HowIntroduced::whereNull('salon_id')->get();
        foreach ($howIntroduceds as $howIntroduced) {
            $salon->howIntroduceds()->firstOrCreate(
                ['name' => $howIntroduced->name],
                ['name' => $howIntroduced->name]
            );
        }

        // Copy CustomerGroups
        $customerGroups = CustomerGroup::whereNull('salon_id')->get();
        foreach ($customerGroups as $customerGroup) {
            $salon->customerGroups()->firstOrCreate(
                ['name' => $customerGroup->name],
                ['name' => $customerGroup->name]
            );
        }
    }
}
