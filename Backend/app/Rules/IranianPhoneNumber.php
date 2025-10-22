<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class IranianPhoneNumber implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Clean and normalize the phone number
        $cleanedValue = \normalizePhoneNumber($value);
        // Accept only Iranian mobile numbers in 98XXXXXXXXXX format
        if (!preg_match('/^98[0-9]{10}$/', $cleanedValue)) {
            $fail('فرمت شماره معتبر نیست');
        }
    }
}
