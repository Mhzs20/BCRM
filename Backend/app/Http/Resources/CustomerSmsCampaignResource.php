<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Morilog\Jalali\Jalalian;

class CustomerSmsCampaignResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // آخرین نوبت
        $lastAppointment = $this->appointments()->latest('appointment_date')->first();
        // آخرین بازخورد
        $lastFeedback = $lastAppointment ? $lastAppointment->feedback : null;
        // آخرین سرویس دریافت شده
        $lastService = $lastAppointment && $lastAppointment->services->count() ? $lastAppointment->services->first() : null;
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone_number' => $this->phone_number,
            'gender' => $this->gender,
            'service' => $lastService ? $lastService->name : null,
            'created_at' => Jalalian::fromCarbon($this->created_at)->format('Y/m/d'),
            'last_visit' => $lastAppointment ? Jalalian::fromCarbon($lastAppointment->appointment_date)->format('Y/m/d') : null,
            'satisfaction' => $lastFeedback ? $lastFeedback->rating : null,
        ];
    }
}
