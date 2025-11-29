<?php

namespace App\Services\HolidayProviders;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use function collect;

abstract class BaseHolidayProvider implements HolidayProviderInterface
{
    /**
     * Normalize raw holiday records into the standard payload.
     */
    protected function normalize(iterable $records, string $source): Collection
    {
        $normalized = collect($records)
            ->map(function ($record) use ($source) {
                if (! is_array($record)) {
                    return null;
                }

                $name = Arr::get($record, 'name')
                    ?? Arr::get($record, 'title')
                    ?? Arr::get($record, 'description')
                    ?? Arr::get($record, 'summary');

                $month = $this->extractNumber($record, ['month', 'Month', 'monthNumber', 'jalali.month', 'date.month']);
                $day = $this->extractNumber($record, ['day', 'Day', 'dayNumber', 'jalali.day', 'date.day']);

                if (empty($name) || $month === null || $day === null) {
                    return null;
                }

                return [
                    'name' => trim($name),
                    'month' => $month,
                    'day' => $day,
                    'type' => Arr::get($record, 'type')
                        ?? (Arr::get($record, 'isHoliday') ? 'public' : 'event'),
                    'is_official' => (bool) ($record['is_official'] ?? $record['isHoliday'] ?? true),
                    'source' => $source,
                ];
            })
            ->filter();

        return $normalized->unique(function (array $holiday) {
            return sprintf('%02d-%02d-%s', $holiday['month'], $holiday['day'], $holiday['name']);
        })->values();
    }

    /**
     * Recursively collect candidate records from the provider payload.
     */
    protected function collectRecords(mixed $payload): array
    {
        $records = [];

        $walker = function ($node) use (&$walker, &$records) {
            if (! is_array($node)) {
                return;
            }

            $hasDateKeys = array_key_exists('month', $node)
                || array_key_exists('Month', $node)
                || array_key_exists('day', $node)
                || array_key_exists('Day', $node)
                || Arr::has($node, 'jalali.month');

            if ($hasDateKeys) {
                $records[] = $node;
            }

            foreach ($node as $value) {
                if (is_array($value)) {
                    $walker($value);
                }
            }
        };

        $walker($payload);

        return $records;
    }

    private function extractNumber(array $record, array $possibleKeys): ?int
    {
        foreach ($possibleKeys as $key) {
            if (Arr::has($record, $key)) {
                $value = Arr::get($record, $key);

                if (is_numeric($value)) {
                    return (int) $value;
                }
            }
        }

        return null;
    }
}
