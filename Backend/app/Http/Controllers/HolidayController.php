<?php

namespace App\Http\Controllers;

use App\Services\HolidayService;
use Illuminate\Http\JsonResponse;

class HolidayController extends Controller
{
    public function __construct(private readonly HolidayService $holidayService)
    {
    }

    /**
     * Return Iranian holidays for the requested Persian year.
     */
    public function getHolidays(int $year): JsonResponse
    {
        $result = $this->holidayService->fetchHolidays($year);

        return response()->json([
            'success' => true,
            'year' => $year,
            'source' => $result['source'],
            'holidays' => $result['holidays']->values(),
        ]);
    }
}