<?php

namespace App\Http\Controllers;

use App\Services\Reports\CustomerReportService;
use App\Services\Reports\ReservationReportService;
use App\Services\Reports\FinanceReportService;
use App\Services\Reports\PersonnelReportService;
use App\Services\Reports\SatisfactionReportService;
use App\Services\Reports\SmsReportService;
use App\Services\Reports\AppointmentReportService;
use App\Services\Reports\ReportPdfService;
use App\Models\SharedReport;
use App\Traits\ConvertsPersianDates;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class ReportController extends Controller
{
    use ConvertsPersianDates;
    protected $customerReportService;
    protected $reservationReportService;
    protected $financeReportService;
    protected $personnelReportService;
    protected $satisfactionReportService;
    protected $smsReportService;
    protected $appointmentReportService;
    protected $pdfService;

    public function __construct(
        CustomerReportService $customerReportService,
        ReservationReportService $reservationReportService,
        FinanceReportService $financeReportService,
        PersonnelReportService $personnelReportService,
        SatisfactionReportService $satisfactionReportService,
        SmsReportService $smsReportService,
        AppointmentReportService $appointmentReportService,
        ReportPdfService $pdfService
    ) {
        $this->customerReportService = $customerReportService;
        $this->reservationReportService = $reservationReportService;
        $this->financeReportService = $financeReportService;
        $this->personnelReportService = $personnelReportService;
        $this->satisfactionReportService = $satisfactionReportService;
        $this->smsReportService = $smsReportService;
        $this->appointmentReportService = $appointmentReportService;
        $this->pdfService = $pdfService;
    }

    /**
     * Get report overview (home page KPIs).
     */
    public function overview(Request $request)
    {
        $salonId = $request->user()->active_salon_id;
        $period = $request->input('period', 'weekly');

        // This could aggregate from multiple services or have its own logic
        $customerReport = $this->customerReportService->generatePresetReport($salonId, $period);
        $reservationReport = $this->reservationReportService->generatePresetReport($salonId, $period);
        $financeReport = $this->financeReportService->generatePresetReport($salonId, $period);
        $smsReport = $this->smsReportService->generatePresetReport($salonId, $period);

        return response()->json([
            'success' => true,
            'period' => $period,
            'kpis' => [
                'new_appointments' => $reservationReport['kpis']['total_appointments'] ?? 0,
                'new_customers' => $customerReport['kpis']['total_customers'] ?? 0,
                'salon_revenue' => $financeReport['kpis']['total_income'] ?? 0,
                'sms_credit' => $smsReport['kpis']['current_balance'] ?? 0,
            ],
        ]);
    }

    /**
     * Get preset customer report.
     */
    public function customersPreset(Request $request)
    {
        $salonId = $request->user()->active_salon_id;
        $period = $request->input('period', null);

        $report = $this->customerReportService->generatePresetReport($salonId, $period);

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    /**
     * Get custom customer report.
     */
    public function customersCustom(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'time_from' => 'nullable|date_format:H:i',
            'time_to' => 'nullable|date_format:H:i',
            'period' => 'nullable|in:daily,weekly,monthly,yearly',
            'min_paid_amount' => 'nullable|numeric|min:0',
            'max_paid_amount' => 'nullable|numeric|min:0',
            'service_ids' => 'nullable|array',
            'service_ids.*' => 'integer|min:0',
            'personnel_ids' => 'nullable|array',
            'personnel_ids.*' => 'integer|min:0',
            'acquisition_source_ids' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $salonId = $request->user()->active_salon_id;
        $filters = $request->only([
            'date_from',
            'date_to',
            'time_from',
            'time_to',
            'period',
            'min_paid_amount',
            'max_paid_amount',
            'service_ids',
            'personnel_ids',
            'acquisition_source_ids',
        ]);
        
        // Convert Persian dates to Gregorian if needed
        $filters = $this->convertPersianDates($filters);

        $report = $this->customerReportService->generateCustomReport($salonId, $filters);

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    /**
     * Get preset reservation report.
     */
    public function reservationsPreset(Request $request)
    {
        $salonId = $request->user()->active_salon_id;
        $period = $request->input('period', null);

        $report = $this->reservationReportService->generatePresetReport($salonId, $period);

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    /**
     * Get custom reservation report.
     */
    public function reservationsCustom(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'time_from' => 'nullable|date_format:H:i',
            'time_to' => 'nullable|date_format:H:i',
            'service_ids' => 'nullable|array',
            'service_ids.*' => 'integer|min:0',
            'personnel_ids' => 'nullable|array',
            'personnel_ids.*' => 'integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $salonId = $request->user()->active_salon_id;
        $filters = $request->only([
            'date_from',
            'date_to',
            'time_from',
            'time_to',
            'service_ids',
            'personnel_ids',
        ]);
        
        // Convert Persian dates to Gregorian if needed
        $filters = $this->convertPersianDates($filters);

        $report = $this->reservationReportService->generateCustomReport($salonId, $filters);

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    /**
     * Get preset appointment report.
     */
    public function appointmentsPreset(Request $request)
    {
        $salonId = $request->user()->active_salon_id;
        $period = $request->input('period', null);

        $report = $this->appointmentReportService->generatePresetReport($salonId, $period);

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    /**
     * Get custom appointment report.
     */
    public function appointmentsCustom(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'time_from' => 'nullable|date_format:H:i',
            'time_to' => 'nullable|date_format:H:i',
            'status' => 'nullable|array',
            'status.*' => 'string|in:pending,confirmed,completed,canceled,cancelled,no_show,all',
            'service_ids' => 'nullable|array',
            'service_ids.*' => 'integer|min:0',
            'personnel_ids' => 'nullable|array',
            'personnel_ids.*' => 'integer|min:0',
            'customer_ids' => 'nullable|array',
            'customer_ids.*' => 'integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $salonId = $request->user()->active_salon_id;
        $filters = $request->only([
            'date_from',
            'date_to',
            'time_from',
            'time_to',
            'status',
            'service_ids',
            'personnel_ids',
            'customer_ids',
        ]);
        
        // Convert Persian dates to Gregorian if needed
        $filters = $this->convertPersianDates($filters);

        $report = $this->appointmentReportService->generateCustomReport($salonId, $filters);

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    /**
     * Get preset finance report.
     */
    public function financePreset(Request $request)
    {
        $salonId = $request->user()->active_salon_id;
        $period = $request->input('period', null);

        $report = $this->financeReportService->generatePresetReport($salonId, $period);

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    /**
     * Get custom finance report.
     */
    public function financeCustom(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $salonId = $request->user()->active_salon_id;
        $filters = $request->only(['date_from', 'date_to']);
        
        // Convert Persian dates to Gregorian if needed
        $filters = $this->convertPersianDates($filters);

        $report = $this->financeReportService->generateCustomReport($salonId, $filters);

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    /**
     * Get preset personnel report.
     */
    public function personnelPreset(Request $request)
    {
        $salonId = $request->user()->active_salon_id;
        $period = $request->input('period', null);

        $report = $this->personnelReportService->generatePresetReport($salonId, $period);

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    /**
     * Get custom personnel report.
     */
    public function personnelCustom(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'time_from' => 'nullable|date_format:H:i',
            'time_to' => 'nullable|date_format:H:i',
            'min_income' => 'nullable|numeric|min:0',
            'max_income' => 'nullable|numeric|min:0',
            'personnel_ids' => 'nullable|array',
            'personnel_ids.*' => 'integer|min:0',
            'service_ids' => 'nullable|array',
            'service_ids.*' => 'integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $salonId = $request->user()->active_salon_id;
        $filters = $request->only([
            'date_from',
            'date_to',
            'time_from',
            'time_to',
            'min_income',
            'max_income',
            'personnel_ids',
            'service_ids',
        ]);
        
        // Convert Persian dates to Gregorian if needed
        $filters = $this->convertPersianDates($filters);

        $report = $this->personnelReportService->generateCustomReport($salonId, $filters);

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    /**
     * Get preset satisfaction report.
     */
    public function satisfactionPreset(Request $request)
    {
        $salonId = $request->user()->active_salon_id;
        $period = $request->input('period', null);

        $report = $this->satisfactionReportService->generatePresetReport($salonId, $period);

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    /**
     * Get custom satisfaction report.
     */
    public function satisfactionCustom(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'time_from' => 'nullable|date_format:H:i',
            'time_to' => 'nullable|date_format:H:i',
            'personnel_ids' => 'nullable|array',
            'personnel_ids.*' => 'integer|min:0',
            'service_ids' => 'nullable|array',
            'service_ids.*' => 'integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $salonId = $request->user()->active_salon_id;
        $filters = $request->only([
            'date_from',
            'date_to',
            'time_from',
            'time_to',
            'personnel_ids',
            'service_ids',
        ]);
        
        // Convert Persian dates to Gregorian if needed
        $filters = $this->convertPersianDates($filters);

        $report = $this->satisfactionReportService->generateCustomReport($salonId, $filters);

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    /**
     * Get preset SMS report.
     */
    public function smsPreset(Request $request)
    {
        $salonId = $request->user()->active_salon_id;
        $period = $request->input('period', null);

        $report = $this->smsReportService->generatePresetReport($salonId, $period);

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    /**
     * Get custom SMS report.
     */
    public function smsCustom(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'time_from' => 'nullable|date_format:H:i',
            'time_to' => 'nullable|date_format:H:i',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $salonId = $request->user()->active_salon_id;
        $filters = $request->only(['date_from', 'date_to', 'time_from', 'time_to']);
        
        // Convert Persian dates to Gregorian if needed
        $filters = $this->convertPersianDates($filters);

        $report = $this->smsReportService->generateCustomReport($salonId, $filters);

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    /**
     * Share a report.
     */
    public function shareReport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'report_type' => 'required|in:customers,reservations,finance,personnel,satisfaction,sms',
            'filters' => 'nullable|array',
            'data' => 'required|array',
            'expires_in_days' => 'nullable|integer|min:1|max:90',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $salonId = $request->user()->active_salon_id;
        
        if (!$salonId) {
            return response()->json([
                'success' => false,
                'message' => 'User is not associated with any salon',
            ], 400);
        }
        
        $expiresInDays = $request->input('expires_in_days', 7);

        $sharedReport = SharedReport::create([
            'salon_id' => $salonId,
            'created_by' => $request->user()->id,
            'report_type' => $request->input('report_type'),
            'token' => Str::random(32),
            'filters' => $request->input('filters', []),
            'data' => $request->input('data'),
            'expires_at' => Carbon::now()->addDays($expiresInDays),
        ]);

        $shareUrl = url("/api/reports/shared/{$sharedReport->token}");

        return response()->json([
            'success' => true,
            'message' => 'گزارش با موفقیت به اشتراک گذاشته شد',
            'data' => [
                'token' => $sharedReport->token,
                'share_url' => $shareUrl,
                'expires_at' => $sharedReport->expires_at->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    /**
     * Get shared report by token.
     */
    public function getSharedReport($token)
    {
        $sharedReport = SharedReport::where('token', $token)->first();

        if (!$sharedReport) {
            return response()->json([
                'success' => false,
                'message' => 'گزارش یافت نشد',
            ], 404);
        }

        if ($sharedReport->isExpired()) {
            return response()->json([
                'success' => false,
                'message' => 'این گزارش منقضی شده است',
            ], 410);
        }

        $sharedReport->incrementViewCount();

        return response()->json([
            'success' => true,
            'data' => [
                'report_type' => $sharedReport->report_type,
                'filters' => $sharedReport->filters,
                'data' => $sharedReport->data,
                'created_at' => $sharedReport->created_at->format('Y-m-d H:i:s'),
                'expires_at' => $sharedReport->expires_at->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    /**
     * Download report as PDF.
     */
    public function downloadPdf(Request $request, $reportType)
    {
        $validator = Validator::make(['report_type' => $reportType], [
            'report_type' => 'required|in:customers,reservations,finance,personnel,satisfaction,sms',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // Get report data from request body
        $reportData = $request->input('data', []);

        if (empty($reportData)) {
            return response()->json([
                'success' => false,
                'message' => 'داده‌های گزارش خالی است',
            ], 400);
        }

        // Generate HTML template
        $html = $this->pdfService->generateSimpleHtmlTemplate($reportType, $reportData);

        // Generate PDF
        return $this->pdfService->generatePdfFromHtml($html, $reportType . '-' . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * Download shared report as PDF.
     */
    public function downloadSharedReportPdf($token)
    {
        $sharedReport = SharedReport::where('token', $token)->first();

        if (!$sharedReport) {
            return response()->json([
                'success' => false,
                'message' => 'گزارش یافت نشد',
            ], 404);
        }

        if ($sharedReport->isExpired()) {
            return response()->json([
                'success' => false,
                'message' => 'این گزارش منقضی شده است',
            ], 410);
        }

        $html = $this->pdfService->generateSimpleHtmlTemplate($sharedReport->report_type, $sharedReport->data);

        return $this->pdfService->generatePdfFromHtml($html, $sharedReport->report_type . '-shared-' . now()->format('Y-m-d') . '.pdf');
    }
}

