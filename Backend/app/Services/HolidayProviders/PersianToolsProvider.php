<?php

namespace App\Services\HolidayProviders;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use function collect;

class PersianToolsProvider extends BaseHolidayProvider
{
    public function getName(): string
    {
        return 'persian-tools';
    }

    public function getHolidays(int $year): Collection
    {
        $response = Http::timeout(10)
            ->acceptJson()
            ->get('https://cdn.jsdelivr.net/gh/persian-tools/persian-holidays@main/src/data/holidays.json');

        if (! $response->successful()) {
            return collect();
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            return collect();
        }

        $records = collect($payload)
            ->filter(function ($holiday) use ($year) {
                if (! is_array($holiday)) {
                    return false;
                }

                $singleYear = Arr::get($holiday, 'year');
                if (is_numeric($singleYear) && (int) $singleYear !== $year) {
                    return false;
                }

                $yearList = Arr::get($holiday, 'years');
                if (is_array($yearList) && ! in_array($year, $yearList, true)) {
                    return false;
                }

                $range = Arr::get($holiday, 'yearRange');
                if (is_array($range) && count($range) === 2) {
                    [$from, $to] = $range;
                    if ($year < (int) $from || $year > (int) $to) {
                        return false;
                    }
                }

                return true;
            })
            ->all();

        return $this->normalize($records, $this->getName());
    }
}
