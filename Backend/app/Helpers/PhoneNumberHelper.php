<?php

if (!function_exists('normalizePhoneNumber')) {
    /**
     * Normalize a phone number to a standard format.
     *
     * @param string $phoneNumber
     * @return string
     */
    function normalizePhoneNumber(string $phoneNumber): string
    {
    // Trim and convert Persian/Arabic digits to Latin digits
    $phoneNumber = (string) $phoneNumber;
    $phoneNumber = trim($phoneNumber);
    $persian = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
    $arabic  = ['٠','١','٢','٣','٤','٥','٦','٧','٨','٩'];
    $latin   = ['0','1','2','3','4','5','6','7','8','9'];
    $phoneNumber = str_replace($persian, $latin, $phoneNumber);
    $phoneNumber = str_replace($arabic, $latin, $phoneNumber);

    // Remove all non-digit characters except leading +
    $phoneNumber = preg_replace('/[^\d+]/', '', $phoneNumber);

        // Remove leading +
        if (strpos($phoneNumber, '+') === 0) {
            $phoneNumber = substr($phoneNumber, 1);
        }

        // Remove leading 00 (international format)
        if (strpos($phoneNumber, '00') === 0) {
            $phoneNumber = substr($phoneNumber, 2);
        }

        // If starts with 98, keep as is
        if (strpos($phoneNumber, '98') === 0) {
            $phoneNumber = $phoneNumber;
        }
        // If starts with 9 and is 10 digits, add 98
        elseif (preg_match('/^9\d{9}$/', $phoneNumber)) {
            $phoneNumber = '98' . $phoneNumber;
        }
        // If starts with 09 and is 11 digits, remove 0 and add 98
        elseif (preg_match('/^09\d{9}$/', $phoneNumber)) {
            $phoneNumber = '98' . substr($phoneNumber, 1);
        }
        // If starts with 989 and is 12 digits, keep as is
        elseif (preg_match('/^989\d{9}$/', $phoneNumber)) {
            $phoneNumber = $phoneNumber;
        }
        // Otherwise, return as is (may fail validation)

        return $phoneNumber;
    }
}

if (!function_exists('toPersianNumbers')) {
    /**
     * Convert English/Latin numbers to Persian numbers.
     *
     * @param string|int|null $value
     * @return string
     */
    function toPersianNumbers($value): string
    {
        if ($value === null) {
            return '';
        }
        
        $value = (string) $value;
        $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        
        return str_replace($english, $persian, $value);
    }
}
