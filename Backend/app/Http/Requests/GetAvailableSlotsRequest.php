<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GetAvailableSlotsRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check();
    }

    public function rules()
    {
        $salonId = $this->route('salon_id');
        if (!$salonId && auth()->user() && method_exists(auth()->user(), 'currentSalon') && auth()->user()->currentSalon) {
            $salonId = auth()->user()->currentSalon->id;
        }

        return [
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'staff_id' => ['required', Rule::exists('salon_staff', 'id')->where(function ($query) use ($salonId) {
                if ($salonId) { $query->where('salon_id', $salonId); }
            })],
            'service_ids' => ['required', 'array', 'min:1'],
            'service_ids.*' => ['required', Rule::exists('services', 'id')->where(function ($query) use ($salonId) {
                if ($salonId) { $query->where('salon_id', $salonId); }
            })->where('is_active', true)],
        ];
    }
}
