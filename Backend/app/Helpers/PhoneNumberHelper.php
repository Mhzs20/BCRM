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
        // Remove any non-numeric characters
        $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);

        // Remove leading zero if the number starts with '09' and has 11 digits
        if (substr($phoneNumber, 0, 2) === '09' && strlen($phoneNumber) === 11) {
            $phoneNumber = substr($phoneNumber, 1);
        }

        // Add the country code if it's missing
        if (substr($phoneNumber, 0, 2) !== '98') {
            $phoneNumber = '98' . $phoneNumber;
        }

        return $phoneNumber;
    }
}
