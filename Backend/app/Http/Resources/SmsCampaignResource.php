<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SmsCampaignResource extends JsonResource
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
            'salon_id' => $this->salon_id,
            'user_id' => $this->user_id,
            'salon' => [
                'id' => $this->salon->id,
                'name' => $this->salon->name,
                'address' => $this->salon->address,
                'mobile' => $this->salon->mobile,
            ],
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'mobile' => $this->user->mobile,
                'email' => $this->user->email,
            ],
            'filters' => $this->filters,
            'message' => $this->message,
            'customer_count' => $this->customer_count,
            'total_cost' => $this->total_cost,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
