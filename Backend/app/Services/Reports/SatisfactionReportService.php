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
        $this->filters = $filters;
    }

    /**
     * Calculate KPIs.
     */
    protected function calculateKPIs(array $filters = [])
    {
        // Average satisfaction
        $avgSatisfactionQuery = CustomerFeedback::whereHas('appointment', function ($q) {
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
            });
            
        // Apply personnel filter
        if (!empty($filters['personnel_ids']) && !in_array(0, $filters['personnel_ids'])) {
            $avgSatisfactionQuery->whereIn('staff_id', $filters['personnel_ids']);
        }
        
        // Apply service filter
        if (!empty($filters['service_ids']) && !in_array(0, $filters['service_ids'])) {
            $avgSatisfactionQuery->whereIn('service_id', $filters['service_ids']);
        }
        
        $avgSatisfaction = $avgSatisfactionQuery->avg('rating');

        // Total respondents
        $respondentsQuery = CustomerFeedback::whereHas('appointment', function ($q) {
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
            });
            
        // Apply personnel filter
        if (!empty($filters['personnel_ids']) && !in_array(0, $filters['personnel_ids'])) {
            $respondentsQuery->whereIn('staff_id', $filters['personnel_ids']);
        }
        
        // Apply service filter
        if (!empty($filters['service_ids']) && !in_array(0, $filters['service_ids'])) {
            $respondentsQuery->whereIn('service_id', $filters['service_ids']);
        }
        
        $respondents = $respondentsQuery->count();

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
        $bestPersonnelQuery = CustomerFeedback::whereHas('appointment', function ($q) {
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
            });
            
        // Apply personnel filter
        if (!empty($filters['personnel_ids']) && !in_array(0, $filters['personnel_ids'])) {
            $bestPersonnelQuery->whereIn('staff_id', $filters['personnel_ids']);
        }
        
        // Apply service filter
        if (!empty($filters['service_ids']) && !in_array(0, $filters['service_ids'])) {
            $bestPersonnelQuery->whereIn('service_id', $filters['service_ids']);
        }
        
        $bestPersonnel = $bestPersonnelQuery
            ->select('staff_id', DB::raw('AVG(rating) as avg_rating'))
            ->groupBy('staff_id')
            ->orderByDesc('avg_rating')
            ->with('staff')
            ->first();

        // Best service
        $bestServiceQuery = CustomerFeedback::whereHas('appointment', function ($q) {
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
            });
            
        // Apply personnel filter
        if (!empty($filters['personnel_ids']) && !in_array(0, $filters['personnel_ids'])) {
            $bestServiceQuery->whereIn('staff_id', $filters['personnel_ids']);
        }
        
        // Apply service filter
        if (!empty($filters['service_ids']) && !in_array(0, $filters['service_ids'])) {
            $bestServiceQuery->whereIn('service_id', $filters['service_ids']);
        }
        
        $bestService = $bestServiceQuery
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

        // Participation chart data
        $participationChart = $this->getParticipationChartData($filters);

        return [
            'satisfaction_by_service' => [
                'labels' => $serviceNames,
                'data' => $ratings,
            ],
            'participation' => $participationChart,
        ];
    }

    /**
     * Get participation chart data for pie chart.
     */
    protected function getParticipationChartData(array $filters = [])
    {
        // Total survey links sent
        $surveyLinksSentQuery = Appointment::where('salon_id', $this->salonId)
            ->whereNotNull('survey_sms_sent_at')
            ->when($this->dateFrom, function ($q) {
                $q->whereDate('appointment_date', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($q) {
                $q->whereDate('appointment_date', '<=', $this->dateTo);
            });

        // Apply personnel filter for appointments
        if (!empty($filters['personnel_ids']) && !in_array(0, $filters['personnel_ids'])) {
            $surveyLinksSentQuery->whereIn('staff_id', $filters['personnel_ids']);
        }

        // Apply service filter for appointments
        if (!empty($filters['service_ids']) && !in_array(0, $filters['service_ids'])) {
            $surveyLinksSentQuery->whereHas('services', function ($q) use ($filters) {
                $q->whereIn('services.id', $filters['service_ids']);
            });
        }

        $totalSent = $surveyLinksSentQuery->count();

        // Total respondents (submitted feedbacks)
        $respondentsQuery = CustomerFeedback::whereHas('appointment', function ($q) {
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
            });

        // Apply personnel filter
        if (!empty($filters['personnel_ids']) && !in_array(0, $filters['personnel_ids'])) {
            $respondentsQuery->whereIn('staff_id', $filters['personnel_ids']);
        }

        // Apply service filter
        if (!empty($filters['service_ids']) && !in_array(0, $filters['service_ids'])) {
            $respondentsQuery->whereIn('service_id', $filters['service_ids']);
        }

        $participated = $respondentsQuery->count();
        $notParticipated = max(0, $totalSent - $participated);

        $participationPercentage = $totalSent > 0 ? round(($participated / $totalSent) * 100, 1) : 0;
        $nonParticipationPercentage = $totalSent > 0 ? round(($notParticipated / $totalSent) * 100, 1) : 0;

        return [
            'total_sent' => $totalSent,
            'participated' => $participated,
            'not_participated' => $notParticipated,
            'participation_percentage' => $participationPercentage,
            'non_participation_percentage' => $nonParticipationPercentage,
            'labels' => ['شرکت کنندگان', 'عدم مشارکت'],
            'data' => [$participated, $notParticipated],
            'percentages' => [$participationPercentage, $nonParticipationPercentage],
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
    
    /**
     * Build filters summary for display (override).
     */
    protected function buildFiltersSummary($filters)
    {
        $summary = parent::buildFiltersSummary($filters);

        // Add personnel filter
        if (!empty($filters['personnel_ids']) && !in_array(0, $filters['personnel_ids'])) {
            $personnelNames = \App\Models\Staff::whereIn('id', $filters['personnel_ids'])
                ->pluck('full_name')
                ->implode('، ');
            $summary[] = ['label' => 'پرسنل', 'value' => $personnelNames ?: 'نامشخص'];
        } elseif (isset($filters['personnel_ids']) && in_array(0, $filters['personnel_ids'])) {
            $summary[] = ['label' => 'پرسنل', 'value' => 'همه موارد'];
        }

        // Add service filter
        if (!empty($filters['service_ids']) && !in_array(0, $filters['service_ids'])) {
            $serviceNames = \App\Models\Service::whereIn('id', $filters['service_ids'])
                ->pluck('name')
                ->implode('، ');
            $summary[] = ['label' => 'خدمات', 'value' => $serviceNames ?: 'نامشخص'];
        } elseif (isset($filters['service_ids']) && in_array(0, $filters['service_ids'])) {
            $summary[] = ['label' => 'خدمات', 'value' => 'همه موارد'];
        }

        return $summary;
    }
}
