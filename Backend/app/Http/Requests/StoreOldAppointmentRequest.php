<?php

namespace App\Http\Requests;

class StoreOldAppointmentRequest extends StoreAppointmentRequest
{
    public function rules()
    {
        $rules = parent::rules();
        
        // Allow past dates for old appointments
        $rules['appointment_date'] = ['required', 'jdate_format:Y-m-d'];
        
        return $rules;
    }
}