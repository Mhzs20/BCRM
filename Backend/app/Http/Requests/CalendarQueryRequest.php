<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CalendarQueryRequest extends FormRequest
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
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'staff_id' => ['nullable', 'integer', Rule::exists('salon_staff', 'id')->where(function ($query) use ($salonId) {
                if ($salonId) { $query->where('salon_id', $salonId); }
            })],
        ];
    }
}
