<?php

namespace App\Services\HolidayProviders;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use function collect;

class HolidayApiIrProvider extends BaseHolidayProvider
{
    public function getName(): string
    {
        return 'holidayapi.ir';
    }

    public function getHolidays(int $year): Collection
    {
        $response = Http::timeout(10)
            ->acceptJson()
            ->get("https://holidayapi.ir/jalali/{$year}");

        if (! $response->successful()) {
            return collect();
        }

        $payload = $response->json();
        $records = [];

        if (is_array($payload)) {
            if (isset($payload[$year]) && is_array($payload[$year])) {
                $records = $payload[$year];
            } else {
                $records = Arr::flatten($payload, 1);
            }
        }

        return $this->normalize($records, $this->getName());
    }
}
