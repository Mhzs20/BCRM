<?php

namespace App\Services\Reports;

use App\Models\CustomerFeedback;
use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SatisfactionReportService extends BaseReportService
{
    /**
     * Generate preset report.
     */
    public function generatePresetReport($salonId, $period = 'weekly')
    {
        $this->salonId = $salonId;
        $dateRange = $this->getPresetDateRange($period);
        $this->dateFrom = $dateRange['from'];
        $this->dateTo = $dateRange['to'];

        return [
            'period' => $period,
            'date_range' => $this->getPersianDateRange($this->dateFrom, $this->dateTo),
            'kpis' => $this->calculateKPIs(),
            'charts' => $this->generateCharts(),
            'sections' => $this->generateSections(),
        ];
    }

    /**
     * Generate custom report.
     */
    public function generateCustomReport($salonId, array $filters)
    {
        $this->salonId = $salonId;
        $this->applyFilters($filters);

        return [
            'filters_applied' => $this->buildFiltersSummary($filters),
            'kpis' => $this->calculateKPIs($filters),
            'charts' => $this->generateCharts($filters),
            'sections' => $this->generateSections($filters),
        ];
    }

    /**
     * Apply custom filters.
     */
    protected function applyFilters(array $filters)
    {
        $this->dateFrom = $filters['date_from'] ?? null;
        $this->dateTo = $filters['date_to'] ?? null;
        $this->timeFrom = $filters['time_from'] ?? null;
        $this->timeTo = $filters['time_to'] ?? null;
    }

    /**
     * Calculate KPIs.
     */
    protected function calculateKPIs(array $filters = [])
    {
        // Average satisfaction
        $avgSatisfaction = CustomerFeedback::whereHas('appointment', function ($q) {
                $q->where('salon_id', $this->salonId);
            })
            ->where('is_submitted', true)
            ->when($this->dateFrom || $this->dateTo, function ($q) {
                $q->whereHas('appointment', function ($query) {
                    if ($this->dateFrom) {
                        $query->whereDate('appointment_date', '>=', $this->dateFrom);
                    }
                    if ($this->dateTo) {
                        $query->whereDate('appointment_date', '<=', $this->dateTo);
                    }
                });
            })
            ->avg('rating');

        // Total respondents
        $respondents = CustomerFeedback::whereHas('appointment', function ($q) {
                $q->where('salon_id', $this->salonId);
            })
            ->where('is_submitted', true)
            ->when($this->dateFrom || $this->dateTo, function ($q) {
                $q->whereHas('appointment', function ($query) {
                    if ($this->dateFrom) {
                        $query->whereDate('appointment_date', '>=', $this->dateFrom);
                    }
                    if ($this->dateTo) {
                        $query->whereDate('appointment_date', '<=', $this->dateTo);
                    }
                });
            })
            ->count();

        // Total survey links sent (appointments with survey_sms_sent_at)
        $surveyLinksSent = Appointment::where('salon_id', $this->salonId)
            ->whereNotNull('survey_sms_sent_at')
            ->when($this->dateFrom, function ($q) {
                $q->whereDate('appointment_date', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($q) {
                $q->whereDate('appointment_date', '<=', $this->dateTo);
            })
            ->count();

        // Participation rate
        $participationRate = $surveyLinksSent > 0 ? ($respondents / $surveyLinksSent) * 100 : 0;

        // Top strength
        $topStrength = $this->getTopStrength();

        // Top weakness
        $topWeakness = $this->getTopWeakness();

        // Best personnel
        $bestPersonnel = CustomerFeedback::whereHas('appointment', function ($q) {
                $q->where('salon_id', $this->salonId);
            })
            ->whereNotNull('staff_id')
            ->where('is_submitted', true)
            ->when($this->dateFrom || $this->dateTo, function ($q) {
                $q->whereHas('appointment', function ($query) {
                    if ($this->dateFrom) {
                        $query->whereDate('appointment_date', '>=', $this->dateFrom);
                    }
                    if ($this->dateTo) {
                        $query->whereDate('appointment_date', '<=', $this->dateTo);
                    }
                });
            })
            ->select('staff_id', DB::raw('AVG(rating) as avg_rating'))
            ->groupBy('staff_id')
            ->orderByDesc('avg_rating')
            ->with('staff')
            ->first();

        // Best service
        $bestService = CustomerFeedback::whereHas('appointment', function ($q) {
                $q->where('salon_id', $this->salonId);
            })
            ->whereNotNull('service_id')
            ->where('is_submitted', true)
            ->when($this->dateFrom || $this->dateTo, function ($q) {
                $q->whereHas('appointment', function ($query) {
                    if ($this->dateFrom) {
                        $query->whereDate('appointment_date', '>=', $this->dateFrom);
                    }
                    if ($this->dateTo) {
                        $query->whereDate('appointment_date', '<=', $this->dateTo);
                    }
                });
            })
            ->select('service_id', DB::raw('AVG(rating) as avg_rating'))
            ->groupBy('service_id')
            ->orderByDesc('avg_rating')
            ->with('service')
            ->first();

        return [
            'avg_satisfaction' => round($avgSatisfaction ?? 0, 1),
            'total_respondents' => $respondents,
            'survey_links_sent' => $surveyLinksSent,
            'participation_rate' => round($participationRate, 1),
            'top_strength' => $topStrength,
            'top_weakness' => $topWeakness,
            'best_personnel' => [
                'name' => $bestPersonnel->staff->full_name ?? 'نامشخص',
                'rating' => round($bestPersonnel->avg_rating ?? 0, 1),
            ],
            'best_service' => [
                'name' => $bestService->service->name ?? 'نامشخص',
                'rating' => round($bestService->avg_rating ?? 0, 1),
            ],
        ];
    }

    /**
     * Get top strength.
     */
    protected function getTopStrength()
    {
        $allStrengths = CustomerFeedback::whereHas('appointment', function ($q) {
                $q->where('salon_id', $this->salonId);
            })
            ->where('is_submitted', true)
            ->whereNotNull('strengths_selected')
            ->when($this->dateFrom || $this->dateTo, function ($q) {
                $q->whereHas('appointment', function ($query) {
                    if ($this->dateFrom) {
                        $query->whereDate('appointment_date', '>=', $this->dateFrom);
                    }
                    if ($this->dateTo) {
                        $query->whereDate('appointment_date', '<=', $this->dateTo);
                    }
                });
            })
            ->pluck('strengths_selected')
            ->flatten()
            ->toArray();

        if (empty($allStrengths)) {
            return 'نامشخص';
        }

        $strengthCounts = array_count_values($allStrengths);
        arsort($strengthCounts);

        return array_key_first($strengthCounts);
    }

    /**
     * Get top weakness.
     */
    protected function getTopWeakness()
    {
        $allWeaknesses = CustomerFeedback::whereHas('appointment', function ($q) {
                $q->where('salon_id', $this->salonId);
            })
            ->where('is_submitted', true)
            ->whereNotNull('weaknesses_selected')
            ->when($this->dateFrom || $this->dateTo, function ($q) {
                $q->whereHas('appointment', function ($query) {
                    if ($this->dateFrom) {
                        $query->whereDate('appointment_date', '>=', $this->dateFrom);
                    }
                    if ($this->dateTo) {
                        $query->whereDate('appointment_date', '<=', $this->dateTo);
                    }
                });
            })
            ->pluck('weaknesses_selected')
            ->flatten()
            ->toArray();

        if (empty($allWeaknesses)) {
            return 'نامشخص';
        }

        $weaknessCounts = array_count_values($allWeaknesses);
        arsort($weaknessCounts);

        return array_key_first($weaknessCounts);
    }

    /**
     * Generate charts data.
     */
    protected function generateCharts(array $filters = [])
    {
        // Satisfaction by service
        $satisfactionByService = CustomerFeedback::whereHas('appointment', function ($q) {
                $q->where('salon_id', $this->salonId);
            })
            ->whereNotNull('service_id')
            ->where('is_submitted', true)
            ->when($this->dateFrom || $this->dateTo, function ($q) {
                $q->whereHas('appointment', function ($query) {
                    if ($this->dateFrom) {
                        $query->whereDate('appointment_date', '>=', $this->dateFrom);
                    }
                    if ($this->dateTo) {
                        $query->whereDate('appointment_date', '<=', $this->dateTo);
                    }
                });
            })
            ->select('service_id', DB::raw('AVG(rating) as avg_rating'))
            ->groupBy('service_id')
            ->with('service')
            ->get();

        $serviceNames = [];
        $ratings = [];

        foreach ($satisfactionByService as $item) {
            $serviceNames[] = $item->service->name ?? 'نامشخص';
            $ratings[] = round($item->avg_rating, 1);
        }

        return [
            'satisfaction_by_service' => [
                'labels' => $serviceNames,
                'data' => $ratings,
            ],
        ];
    }

    /**
     * Generate sections.
     */
    protected function generateSections(array $filters = [])
    {
        return [
            'satisfaction_by_staff' => $this->getSatisfactionByStaff(),
            'satisfaction_by_service' => $this->getSatisfactionByService(),
            'strengths_breakdown' => $this->getStrengthsBreakdown(),
            'weaknesses_breakdown' => $this->getWeaknessesBreakdown(),
            'rating_distribution' => $this->getRatingDistribution(),
        ];
    }

    /**
     * Get satisfaction by staff.
     */
    protected function getSatisfactionByStaff()
    {
        return CustomerFeedback::whereHas('appointment', function ($q) {
                $q->where('salon_id', $this->salonId);
            })
            ->whereNotNull('staff_id')
            ->where('is_submitted', true)
            ->when($this->dateFrom || $this->dateTo, function ($q) {
                $q->whereHas('appointment', function ($query) {
                    if ($this->dateFrom) {
                        $query->whereDate('appointment_date', '>=', $this->dateFrom);
                    }
                    if ($this->dateTo) {
                        $query->whereDate('appointment_date', '<=', $this->dateTo);
                    }
                });
            })
            ->select('staff_id', DB::raw('AVG(rating) as avg_rating'), DB::raw('COUNT(*) as total_ratings'))
            ->groupBy('staff_id')
            ->orderByDesc('avg_rating')
            ->with('staff')
            ->get()
            ->map(function ($item) {
                return [
                    'staff_name' => $item->staff->full_name ?? 'نامشخص',
                    'avg_rating' => round($item->avg_rating, 1),
                    'total_ratings' => $item->total_ratings,
                ];
            });
    }

    /**
     * Get satisfaction by service.
     */
    protected function getSatisfactionByService()
    {
        return CustomerFeedback::whereHas('appointment', function ($q) {
                $q->where('salon_id', $this->salonId);
            })
            ->whereNotNull('service_id')
            ->where('is_submitted', true)
            ->when($this->dateFrom || $this->dateTo, function ($q) {
                $q->whereHas('appointment', function ($query) {
                    if ($this->dateFrom) {
                        $query->whereDate('appointment_date', '>=', $this->dateFrom);
                    }
                    if ($this->dateTo) {
                        $query->whereDate('appointment_date', '<=', $this->dateTo);
                    }
                });
            })
            ->select('service_id', DB::raw('AVG(rating) as avg_rating'), DB::raw('COUNT(*) as total_ratings'))
            ->groupBy('service_id')
            ->orderByDesc('avg_rating')
            ->with('service')
            ->get()
            ->map(function ($item) {
                return [
                    'service_name' => $item->service->name ?? 'نامشخص',
                    'avg_rating' => round($item->avg_rating, 1),
                    'total_ratings' => $item->total_ratings,
                ];
            });
    }

    /**
     * Get strengths breakdown.
     */
    protected function getStrengthsBreakdown()
    {
        $allStrengths = CustomerFeedback::whereHas('appointment', function ($q) {
                $q->where('salon_id', $this->salonId);
            })
            ->where('is_submitted', true)
            ->whereNotNull('strengths_selected')
            ->when($this->dateFrom || $this->dateTo, function ($q) {
                $q->whereHas('appointment', function ($query) {
                    if ($this->dateFrom) {
                        $query->whereDate('appointment_date', '>=', $this->dateFrom);
                    }
                    if ($this->dateTo) {
                        $query->whereDate('appointment_date', '<=', $this->dateTo);
                    }
                });
            })
            ->pluck('strengths_selected')
            ->flatten()
            ->toArray();

        $strengthCounts = array_count_values($allStrengths);
        arsort($strengthCounts);

        return collect($strengthCounts)->map(function ($count, $strength) {
            return [
                'strength' => $strength,
                'count' => $count,
            ];
        })->values();
    }

    /**
     * Get weaknesses breakdown.
     */
    protected function getWeaknessesBreakdown()
    {
        $allWeaknesses = CustomerFeedback::whereHas('appointment', function ($q) {
                $q->where('salon_id', $this->salonId);
            })
            ->where('is_submitted', true)
            ->whereNotNull('weaknesses_selected')
            ->when($this->dateFrom || $this->dateTo, function ($q) {
                $q->whereHas('appointment', function ($query) {
                    if ($this->dateFrom) {
                        $query->whereDate('appointment_date', '>=', $this->dateFrom);
                    }
                    if ($this->dateTo) {
                        $query->whereDate('appointment_date', '<=', $this->dateTo);
                    }
                });
            })
            ->pluck('weaknesses_selected')
            ->flatten()
            ->toArray();

        $weaknessCounts = array_count_values($allWeaknesses);
        arsort($weaknessCounts);

        return collect($weaknessCounts)->map(function ($count, $weakness) {
            return [
                'weakness' => $weakness,
                'count' => $count,
            ];
        })->values();
    }

    /**
     * Get rating distribution.
     */
    protected function getRatingDistribution()
    {
        return CustomerFeedback::whereHas('appointment', function ($q) {
                $q->where('salon_id', $this->salonId);
            })
            ->where('is_submitted', true)
            ->when($this->dateFrom || $this->dateTo, function ($q) {
                $q->whereHas('appointment', function ($query) {
                    if ($this->dateFrom) {
                        $query->whereDate('appointment_date', '>=', $this->dateFrom);
                    }
                    if ($this->dateTo) {
                        $query->whereDate('appointment_date', '<=', $this->dateTo);
                    }
                });
            })
            ->select('rating', DB::raw('COUNT(*) as count'))
            ->groupBy('rating')
            ->orderBy('rating')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->rating . ' ستاره' => $item->count];
            });
    }
}
