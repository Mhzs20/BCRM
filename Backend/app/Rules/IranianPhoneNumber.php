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
        // Clean the phone number by removing spaces, dashes, and other non-essential characters
        // Keep only +, digits, and ensure proper format
        $cleanedValue = preg_replace('/[\s\-\(\)]/', '', $value);
        
        // Basic Iranian phone number regex
        // This regex allows for optional +98 or 0 prefix, followed by 9 and 9 digits.
        if (!preg_match('/^(?:[+]98|0)?9[0-9]{9}$/', $cleanedValue)) {
            $fail('فرمت شماره معتبر نیست');
        }
    }
}
