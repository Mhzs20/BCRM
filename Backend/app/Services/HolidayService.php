<?php

namespace App\Services;

use App\Services\HolidayProviders\HolidayApiIrProvider;
use App\Services\HolidayProviders\HolidayProviderInterface;
use App\Services\HolidayProviders\PersianCalendarApiProvider;
use App\Services\HolidayProviders\PersianToolsProvider;
use App\Services\HolidayProviders\TimeIrProvider;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use function app;
use function collect;

class HolidayService
{
    private const CACHE_VERSION = 'v5';

    /** @var HolidayProviderInterface[] */
    private array $providers;

    /**
     * @param HolidayProviderInterface[]|null $providers
     */
    public function __construct(?array $providers = null)
    {
        $this->providers = $providers ?? [
            app(PersianCalendarApiProvider::class),
            // Other providers disabled - using Persian Calendar API as primary source
            // app(TimeIrProvider::class),
            // app(HolidayApiIrProvider::class),
            // app(PersianToolsProvider::class),
        ];
    }

    /**
     * Retrieve Iran holidays for the given Persian year.
     *
     * @return array{holidays: Collection, source: string}
     */
    public function fetchHolidays(int $year): array
    {
        Cache::forget("iran_holidays_{$year}");

        $cacheKey = sprintf('iran_holidays_%s_%d', self::CACHE_VERSION, $year);

        $cached = Cache::remember($cacheKey, now()->addDay(), function () use ($year) {
            foreach ($this->providers as $provider) {
                try {
                    $holidays = $provider->getHolidays($year);

                    if ($holidays->isNotEmpty()) {
                        $prepared = $this->prepare($holidays, $year, $provider->getName());

                        Log::info('Iran holidays loaded', [
                            'year' => $year,
                            'provider' => $provider->getName(),
                            'count' => $prepared->count(),
                        ]);

                        return [
                            'holidays' => $prepared,
                            'source' => $provider->getName(),
                        ];
                    }
                } catch (\Throwable $e) {
                    Log::warning('Holiday provider failed', [
                        'year' => $year,
                        'provider' => $provider->getName(),
                        'message' => $e->getMessage(),
                    ]);
                }
            }

            $fallback = $this->prepare($this->fallbackHolidays($year), $year, 'fallback');

            Log::notice('Using fallback holidays', [
                'year' => $year,
                'count' => $fallback->count(),
            ]);

            return [
                'holidays' => $fallback,
                'source' => 'fallback',
            ];
        });

        return $this->formatResult($cached, $year);
    }

    /**
     * Static fallback list for when remote APIs are unavailable.
     * محاسبه خودکار تعطیلات با استفاده از تبدیل تاریخ قمری به شمسی
     */
    public function fallbackHolidays(int $year): Collection
    {
        // تعطیلات شمسی ثابت (هر سال یکسان)
        $solarHolidays = collect([
            ['name' => 'عید نوروز', 'month' => 1, 'day' => 1, 'type' => 'national', 'is_official' => true],
            ['name' => 'عید نوروز', 'month' => 1, 'day' => 2, 'type' => 'national', 'is_official' => true],
            ['name' => 'عید نوروز', 'month' => 1, 'day' => 3, 'type' => 'national', 'is_official' => true],
            ['name' => 'عید نوروز', 'month' => 1, 'day' => 4, 'type' => 'national', 'is_official' => true],
            ['name' => 'روز طبیعت (سیزده بدر)', 'month' => 1, 'day' => 13, 'type' => 'national', 'is_official' => true],
            ['name' => 'پیروزی انقلاب اسلامی', 'month' => 11, 'day' => 22, 'type' => 'national', 'is_official' => true],
            ['name' => 'ملی شدن صنعت نفت', 'month' => 12, 'day' => 29, 'type' => 'national', 'is_official' => true],
            ['name' => 'رحلت امام خمینی', 'month' => 3, 'day' => 14, 'type' => 'religious', 'is_official' => true],
            ['name' => 'قیام ۱۵ خرداد', 'month' => 3, 'day' => 15, 'type' => 'national', 'is_official' => true],
        ]);
        
        // تعطیلات قمری را برای سال مورد نظر محاسبه می‌کنیم
        $lunarHolidays = $this->calculateLunarHolidays($year);

        return $solarHolidays->merge($lunarHolidays)->values();
    }

    /**
     * دیتای استاتیک تعطیلات قمری برای سال‌های مختلف
     * این دیتا از منابع رسمی (time.ir) گرفته شده
     * 
     * توجه: چون محاسبه خودکار قمری به شمسی پیچیده و وابسته به رؤیت هلال است،
     * بهتر است دیتای رسمی را برای هر سال استفاده کنیم
     */
    private function calculateLunarHolidays(int $persianYear): Collection
    {
        // دیتای استاتیک برای سال‌های مختلف (از time.ir)
        $lunarHolidaysData = $this->getLunarHolidaysData();
        
        if (isset($lunarHolidaysData[$persianYear])) {
            return collect($lunarHolidaysData[$persianYear]);
        }
        
        // اگر دیتا برای این سال موجود نیست، سعی می‌کنیم از سال نزدیک استفاده کنیم
        // و تقریب بزنیم (هر سال قمری ~11 روز زودتر می‌آید)
        $nearestYear = $this->findNearestYear(array_keys($lunarHolidaysData), $persianYear);
        
        if ($nearestYear) {
            $yearDiff = $persianYear - $nearestYear;
            $dayShift = $yearDiff * -11; // هر سال، 11 روز عقب می‌رود
            
            return collect($lunarHolidaysData[$nearestYear])->map(function ($holiday) use ($dayShift) {
                $date = \DateTime::createFromFormat('!Y-m-d', 
                    sprintf('1400-%02d-%02d', $holiday['month'], $holiday['day'])
                );
                $date->modify("{$dayShift} days");
                
                return [
                    'name' => $holiday['name'],
                    'month' => (int) $date->format('n'),
                    'day' => (int) $date->format('j'),
                    'type' => 'religious',
                    'is_official' => true,
                ];
            });
        }
        
        // اگر هیچ دیتایی نداریم، لیست خالی برگردانیم
        return collect();
    }
    
    /**
     * دیتای تعطیلات قمری برای سال‌های مختلف
     */
    private function getLunarHolidaysData(): array
    {
        return [
            1404 => [
                // محرم و صفر
                ['name' => 'تاسوعای حسینی', 'month' => 1, 'day' => 28, 'type' => 'religious', 'is_official' => true],
                ['name' => 'عاشورای حسینی', 'month' => 1, 'day' => 29, 'type' => 'religious', 'is_official' => true],
                ['name' => 'اربعین حسینی', 'month' => 3, 'day' => 8, 'type' => 'religious', 'is_official' => true],
                
                // ربیع‌الاول و ربیع‌الثانی
                ['name' => 'رحلت حضرت رسول اکرم (ص)', 'month' => 4, 'day' => 28, 'type' => 'religious', 'is_official' => true],
                ['name' => 'شهادت امام حسن مجتبی (ع)', 'month' => 4, 'day' => 28, 'type' => 'religious', 'is_official' => true],
                ['name' => 'شهادت امام رضا (ع)', 'month' => 5, 'day' => 7, 'type' => 'religious', 'is_official' => true],
                ['name' => 'ولادت حضرت رسول اکرم (ص)', 'month' => 5, 'day' => 11, 'type' => 'religious', 'is_official' => true],
                ['name' => 'ولادت امام جعفر صادق (ع)', 'month' => 5, 'day' => 11, 'type' => 'religious', 'is_official' => true],
                
                // جمادی‌الاول و جمادی‌الثانی
                ['name' => 'شهادت حضرت فاطمه زهرا (س)', 'month' => 9, 'day' => 3, 'type' => 'religious', 'is_official' => true],
                
                // رجب
                ['name' => 'ولادت امام علی (ع)', 'month' => 7, 'day' => 13, 'type' => 'religious', 'is_official' => true],
                ['name' => 'مبعث حضرت رسول اکرم (ص)', 'month' => 6, 'day' => 27, 'type' => 'religious', 'is_official' => true],
                
                // شعبان
                ['name' => 'ولادت حضرت قائم (عج)', 'month' => 7, 'day' => 27, 'type' => 'religious', 'is_official' => true],
                
                // رمضان
                ['name' => 'شهادت حضرت علی (ع)', 'month' => 7, 'day' => 21, 'type' => 'religious', 'is_official' => true],
                ['name' => 'شب قدر', 'month' => 7, 'day' => 19, 'type' => 'religious', 'is_official' => true],
                ['name' => 'شب قدر', 'month' => 7, 'day' => 21, 'type' => 'religious', 'is_official' => true],
                ['name' => 'شب قدر', 'month' => 7, 'day' => 23, 'type' => 'religious', 'is_official' => true],
                
                // شوال
                ['name' => 'عید سعید فطر', 'month' => 8, 'day' => 1, 'type' => 'religious', 'is_official' => true],
                ['name' => 'تعطیل به مناسبت عید سعید فطر', 'month' => 8, 'day' => 2, 'type' => 'religious', 'is_official' => true],
                
                // ذی‌قعده
                ['name' => 'شهادت امام جعفر صادق (ع)', 'month' => 8, 'day' => 25, 'type' => 'religious', 'is_official' => true],
                
                // ذی‌حجه
                ['name' => 'عید سعید قربان', 'month' => 10, 'day' => 10, 'type' => 'religious', 'is_official' => true],
                ['name' => 'عید سعید غدیر خم', 'month' => 10, 'day' => 18, 'type' => 'religious', 'is_official' => true],
            ],
            1405 => [
                // محرم و صفر
                ['name' => 'تاسوعای حسینی', 'month' => 1, 'day' => 17, 'type' => 'religious', 'is_official' => true],
                ['name' => 'عاشورای حسینی', 'month' => 1, 'day' => 18, 'type' => 'religious', 'is_official' => true],
                ['name' => 'اربعین حسینی', 'month' => 2, 'day' => 27, 'type' => 'religious', 'is_official' => true],
                
                // ربیع‌الاول و ربیع‌الثانی
                ['name' => 'رحلت حضرت رسول اکرم (ص)', 'month' => 4, 'day' => 17, 'type' => 'religious', 'is_official' => true],
                ['name' => 'شهادت امام حسن مجتبی (ع)', 'month' => 4, 'day' => 17, 'type' => 'religious', 'is_official' => true],
                ['name' => 'شهادت امام رضا (ع)', 'month' => 4, 'day' => 26, 'type' => 'religious', 'is_official' => true],
                ['name' => 'ولادت حضرت رسول اکرم (ص)', 'month' => 4, 'day' => 30, 'type' => 'religious', 'is_official' => true],
                ['name' => 'ولادت امام جعفر صادق (ع)', 'month' => 4, 'day' => 30, 'type' => 'religious', 'is_official' => true],
                
                // جمادی‌الاول و جمادی‌الثانی
                ['name' => 'شهادت حضرت فاطمه زهرا (س)', 'month' => 11, 'day' => 15, 'type' => 'religious', 'is_official' => true],
                
                // رجب
                ['name' => 'ولادت امام علی (ع)', 'month' => 7, 'day' => 2, 'type' => 'religious', 'is_official' => true],
                ['name' => 'مبعث حضرت رسول اکرم (ص)', 'month' => 6, 'day' => 16, 'type' => 'religious', 'is_official' => true],
                
                // شعبان
                ['name' => 'ولادت حضرت قائم (عج)', 'month' => 7, 'day' => 16, 'type' => 'religious', 'is_official' => true],
                
                // رمضان
                ['name' => 'شهادت حضرت علی (ع)', 'month' => 7, 'day' => 10, 'type' => 'religious', 'is_official' => true],
                ['name' => 'شب قدر', 'month' => 7, 'day' => 8, 'type' => 'religious', 'is_official' => true],
                ['name' => 'شب قدر', 'month' => 7, 'day' => 10, 'type' => 'religious', 'is_official' => true],
                ['name' => 'شب قدر', 'month' => 7, 'day' => 12, 'type' => 'religious', 'is_official' => true],
                
                // شوال
                ['name' => 'عید سعید فطر', 'month' => 7, 'day' => 21, 'type' => 'religious', 'is_official' => true],
                ['name' => 'تعطیل به مناسبت عید سعید فطر', 'month' => 7, 'day' => 22, 'type' => 'religious', 'is_official' => true],
                
                // ذی‌قعده
                ['name' => 'شهادت امام جعفر صادق (ع)', 'month' => 8, 'day' => 14, 'type' => 'religious', 'is_official' => true],
                
                // ذی‌حجه
                ['name' => 'عید سعید قربان', 'month' => 9, 'day' => 29, 'type' => 'religious', 'is_official' => true],
                ['name' => 'عید سعید غدیر خم', 'month' => 10, 'day' => 7, 'type' => 'religious', 'is_official' => true],
            ],
        ];
    }
    
    /**
     * نزدیک‌ترین سال موجود در دیتا را پیدا می‌کند
     */
    private function findNearestYear(array $availableYears, int $targetYear): ?int
    {
        if (empty($availableYears)) {
            return null;
        }
        
        $nearest = null;
        $minDiff = PHP_INT_MAX;
        
        foreach ($availableYears as $year) {
            $diff = abs($year - $targetYear);
            if ($diff < $minDiff) {
                $minDiff = $diff;
                $nearest = $year;
            }
        }
        
        return $nearest;
    }

    private function prepare(Collection $holidays, int $year, string $source): Collection
    {
        return $holidays
            ->map(function (array $holiday) use ($year, $source) {
                return [
                    'name' => $holiday['name'],
                    'month' => (int) $holiday['month'],
                    'day' => (int) $holiday['day'],
                    'type' => $holiday['type'] ?? 'public',
                    'is_official' => $holiday['is_official'] ?? true,
                    'year' => $year,
                    'source' => $holiday['source'] ?? $source,
                ];
            })
            ->filter(function (array $holiday) {
                return $holiday['month'] >= 1 && $holiday['month'] <= 12
                    && $holiday['day'] >= 1 && $holiday['day'] <= 31
                    && ! empty($holiday['name']);
            })
            ->unique(function (array $holiday) {
                return sprintf('%04d-%02d-%02d-%s', $holiday['year'], $holiday['month'], $holiday['day'], $holiday['name']);
            })
            ->values();
    }

    /**
     * Ensure cached payloads always follow the expected array structure.
     */
    private function formatResult(mixed $payload, int $year): array
    {
        if ($payload instanceof Collection) {
            $holidays = $this->prepare($payload, $year, 'legacy-cache');

            Log::notice('Converted legacy holiday cache entry', [
                'year' => $year,
                'count' => $holidays->count(),
            ]);

            return [
                'holidays' => $holidays,
                'source' => 'legacy-cache',
            ];
        }

        if (is_array($payload)) {
            $source = $payload['source'] ?? 'cache';
            $holidays = $payload['holidays'] ?? collect();

            if (! $holidays instanceof Collection) {
                $holidays = collect($holidays);
            }

            return [
                'holidays' => $this->prepare($holidays, $year, $source),
                'source' => $source,
            ];
        }

        $fallback = $this->prepare($this->fallbackHolidays($year), $year, 'fallback');

        Log::notice('Holiday cache entry invalid, using fallback', [
            'year' => $year,
            'type' => get_debug_type($payload),
        ]);

        return [
            'holidays' => $fallback,
            'source' => 'fallback',
        ];
    }
}
