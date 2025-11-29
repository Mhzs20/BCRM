<?php

namespace App\Services\HolidayProviders;

use Illuminate\Support\Collection;

interface HolidayProviderInterface
{
    /**
     * Retrieve holidays for the given Persian year.
     */
    public function getHolidays(int $year): Collection;

    /**
     * Name of the underlying provider, used for logging and responses.
     */
    public function getName(): string;
}
