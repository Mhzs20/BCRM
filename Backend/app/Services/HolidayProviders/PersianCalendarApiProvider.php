<?php

namespace App\Services\HolidayProviders;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PersianCalendarApiProvider extends BaseHolidayProvider
{
    private const API_URL = 'https://persian-calendar-api.sajjadth.workers.dev/';
    private const TIMEOUT = 10;

    public function getHolidays(int $year): Collection
    {
        try {
            $response = Http::timeout(self::TIMEOUT)
                ->get(self::API_URL, ['year' => $year]);

            if (! $response->successful()) {
                Log::warning('Persian Calendar API HTTP error', [
                    'year' => $year,
                    'status' => $response->status(),
                ]);

                return collect();
            }

            $data = $response->json();

            if (! is_array($data)) {
                Log::warning('Persian Calendar API returned non-array payload', [
                    'year' => $year,
                    'type' => gettype($data),
                ]);

                return collect();
            }

            $holidays = $this->extractHolidays($data);

            return $this->normalize($holidays, $this->getName());
        } catch (\Throwable $e) {
            Log::warning('Persian Calendar API request failed', [
                'year' => $year,
                'error' => $e->getMessage(),
            ]);

            return collect();
        }
    }

    private function extractHolidays(array $months): array
    {
        $holidays = [];

        foreach ($months as $monthData) {
            if (! isset($monthData['days']) || ! is_array($monthData['days'])) {
                continue;
            }

            $monthIndex = null;
            
            foreach ($monthData['days'] as $dayData) {
                if (! isset($dayData['events']) || ! is_array($dayData['events'])) {
                    continue;
                }

                $events = $dayData['events'];
                
                // Check if this day is a holiday
                if (! isset($events['isHoliday']) || ! $events['isHoliday']) {
                    continue;
                }

                // Extract day number from Persian digits
                $jalaliDay = $dayData['day']['jalali'] ?? null;
                if (! $jalaliDay) {
                    continue;
                }

                $day = $this->convertPersianToEnglishNumber($jalaliDay);

                // Extract month from header if we haven't yet
                if ($monthIndex === null && isset($monthData['header']['jalali'])) {
                    $monthIndex = $this->extractMonthFromHeader($monthData['header']['jalali']);
                }

                if ($monthIndex === null || $day === null) {
                    continue;
                }

                // Extract event names
                $eventList = $events['list'] ?? [];
                foreach ($eventList as $event) {
                    if (isset($event['isHoliday']) && $event['isHoliday'] && isset($event['event'])) {
                        $isReligious = $events['holidayType'] === 'hijri';
                        
                        // Use the date directly from API without any adjustment
                        $holidays[] = [
                            'name' => trim($event['event']),
                            'month' => $monthIndex,
                            'day' => $day,
                            'type' => $isReligious ? 'religious' : 'national',
                            'isHoliday' => true,
                        ];
                    }
                }
            }
        }

        return $holidays;
    }

    private function convertPersianToEnglishNumber(string $persianNumber): ?int
    {
        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

        $converted = str_replace($persian, $english, $persianNumber);

        return is_numeric($converted) ? (int) $converted : null;
    }

    private function extractMonthFromHeader(string $header): ?int
    {
        $months = [
            'فروردین' => 1, 'اردیبهشت' => 2, 'خرداد' => 3,
            'تیر' => 4, 'مرداد' => 5, 'شهریور' => 6,
            'مهر' => 7, 'آبان' => 8, 'آذر' => 9,
            'دی' => 10, 'بهمن' => 11, 'اسفند' => 12,
        ];

        foreach ($months as $monthName => $monthNumber) {
            if (str_contains($header, $monthName)) {
                return $monthNumber;
            }
        }

        return null;
    }

    private function getDaysInMonth(int $month): int
    {
        // Persian calendar: months 1-6 have 31 days, months 7-11 have 30 days, month 12 has 29/30 days
        if ($month <= 6) {
            return 31;
        } elseif ($month <= 11) {
            return 30;
        } else {
            return 29; // Simplified - not checking leap year
        }
    }

    public function getName(): string
    {
        return 'persian-calendar-api';
    }
}
