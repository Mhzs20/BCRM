<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'duration_minutes' => $this->duration_minutes,
            'pivot' => $this->whenPivotLoaded('appointment_service', function () {
                return [
                    'appointment_id' => $this->pivot->appointment_id,
                    'service_id' => $this->pivot->service_id,
                    'price_at_booking' => $this->pivot->price_at_booking,
                    // 'duration_at_booking' => $this->pivot->duration_at_booking, // Removed as total_duration is now on appointment
                    'created_at' => $this->pivot->created_at,
                    'updated_at' => $this->pivot->updated_at,
                ];
            }),
        ];
    }
}
