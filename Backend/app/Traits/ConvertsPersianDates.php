<?php

namespace App\Traits;

use Carbon\Carbon;

trait ConvertsPersianDates
{
    /**
     * Convert Persian dates to Gregorian in the given filters array.
     * 
     * Supports formats:
     * - 1404/10/12 or 1404-10-12 (Persian)
     * - 2025-12-31 (Gregorian)
     * 
     * @param array $filters
     * @param array $dateFields Fields to convert (default: ['date_from', 'date_to'])
     * @return array
     */
    protected function convertPersianDates(array $filters, array $dateFields = ['date_from', 'date_to']): array
    {
        foreach ($dateFields as $field) {
            if (isset($filters[$field]) && !empty($filters[$field])) {
                $filters[$field] = $this->convertToGregorian($filters[$field]);
            }
        }
        
        return $filters;
    }
    
    /**
     * Convert a date string to Gregorian format.
     * Detects if it's Persian (Jalali) or already Gregorian.
     * 
     * @param string $date
     * @return string Gregorian date in Y-m-d format
     */
    protected function convertToGregorian(string $date): string
    {
        // Remove extra spaces
        $date = trim($date);
        
        // Check if it's already a valid Gregorian date (2000-2099)
        if (preg_match('/^(20\d{2})[-\/](0?[1-9]|1[0-2])[-\/](0?[1-9]|[12]\d|3[01])$/', $date, $matches)) {
            // Already Gregorian, normalize format
            return sprintf('%04d-%02d-%02d', $matches[1], $matches[2], $matches[3]);
        }
        
        // Check if it's Persian date (1300-1499)
        if (preg_match('/^(1[34]\d{2})[-\/](0?[1-9]|1[0-2])[-\/](0?[1-9]|[12]\d|3[01])$/', $date, $matches)) {
            try {
                $year = (int) $matches[1];
                $month = (int) $matches[2];
                $day = (int) $matches[3];
                
                // Use Verta to convert Persian to Gregorian
                $verta = \Hekmatinasser\Verta\Verta::jalaliToGregorian($year, $month, $day);
                return sprintf('%04d-%02d-%02d', $verta[0], $verta[1], $verta[2]);
            } catch (\Exception $e) {
                // If conversion fails, return original date
                return $date;
            }
        }
        
        // If no pattern matches, return as-is
        return $date;
    }
    
    /**
     * Check if a date string is in Persian (Jalali) format.
     * 
     * @param string $date
     * @return bool
     */
    protected function isPersianDate(string $date): bool
    {
        return preg_match('/^(1[34]\d{2})[-\/](0?[1-9]|1[0-2])[-\/](0?[1-9]|[12]\d|3[01])$/', trim($date)) === 1;
    }
    
    /**
     * Convert Gregorian date to Persian (Jalali) format.
     * 
     * @param string|\Carbon\Carbon $date
     * @param string $format Default: 'Y/m/d'
     * @return string
     */
    protected function convertToPersian($date, string $format = 'Y/m/d'): string
    {
        if (is_string($date)) {
            $date = Carbon::parse($date);
        }
        
        return \Hekmatinasser\Verta\Verta::instance($date)->format($format);
    }
}
