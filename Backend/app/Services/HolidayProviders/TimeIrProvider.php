<?php

namespace App\Services\HolidayProviders;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use function collect;

class TimeIrProvider extends BaseHolidayProvider
{
    public function getName(): string
    {
        return 'time.ir';
    }

    public function getHolidays(int $year): Collection
    {
        $response = Http::timeout(10)
            ->acceptJson()
            ->withHeaders(['User-Agent' => 'BCRM/1.0 holiday fetcher'])
            ->get("https://api.time.ir/v1/holidays/{$year}");

        if (! $response->successful()) {
            return collect();
        }

        $payload = $response->json();
        $records = $this->collectRecords($payload);

        return $this->normalize($records, $this->getName());
    }
}
