<?php

namespace App\Services\Reports;

use Illuminate\Support\Facades\View;

class ReportPdfService
{
    /**
     * Generate PDF from report data.
     * 
     * Note: This requires barryvdh/laravel-dompdf package.
     * Install with: composer require barryvdh/laravel-dompdf
     */
    public function generatePdf($reportType, $reportData)
    {
        // Check if dompdf is installed
        if (!class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            // Fallback: return JSON for now
            return $this->generateJsonFallback($reportType, $reportData);
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.pdf.' . $reportType, [
            'data' => $reportData,
            'generated_at' => now()->format('Y-m-d H:i:s'),
        ]);

        return $pdf->download($reportType . '-report-' . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * Generate PDF from HTML content.
     */
    public function generatePdfFromHtml($html, $filename = 'report.pdf')
    {
        if (!class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            return response()->json([
                'success' => false,
                'message' => 'PDF generation library not installed. Please run: composer require barryvdh/laravel-dompdf',
            ], 500);
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
        return $pdf->download($filename);
    }

    /**
     * Fallback when dompdf is not installed.
     */
    protected function generateJsonFallback($reportType, $reportData)
    {
        return response()->json([
            'success' => false,
            'message' => 'PDF generation requires barryvdh/laravel-dompdf package. Install it with: composer require barryvdh/laravel-dompdf',
            'report_type' => $reportType,
            'data' => $reportData,
        ], 501);
    }

    /**
     * Generate simple HTML template for PDF.
     */
    public function generateSimpleHtmlTemplate($reportType, $reportData)
    {
        $html = '
        <!DOCTYPE html>
        <html dir="rtl" lang="fa">
        <head>
            <meta charset="UTF-8">
            <style>
                @font-face {
                    font-family: "IRANSans";
                    src: url("/fonts/IRANSans.ttf");
                }
                body {
                    font-family: "IRANSans", Tahoma, Arial, sans-serif;
                    direction: rtl;
                    text-align: right;
                }
                .header {
                    background-color: #4F46E5;
                    color: white;
                    padding: 20px;
                    text-align: center;
                }
                .content {
                    padding: 20px;
                }
                .kpi-box {
                    border: 1px solid #ddd;
                    padding: 15px;
                    margin: 10px 0;
                    border-radius: 5px;
                }
                .footer {
                    margin-top: 30px;
                    text-align: center;
                    font-size: 12px;
                    color: #666;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 20px 0;
                }
                table th, table td {
                    border: 1px solid #ddd;
                    padding: 8px;
                    text-align: right;
                }
                table th {
                    background-color: #f4f4f4;
                }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>گزارش ' . $this->getReportTitle($reportType) . '</h1>
                <p>تاریخ تولید: ' . now()->format('Y-m-d H:i:s') . '</p>
            </div>
            <div class="content">
        ';

        if (isset($reportData['kpis'])) {
            $html .= '<h2>شاخص‌های کلیدی</h2>';
            foreach ($reportData['kpis'] as $key => $value) {
                $html .= '<div class="kpi-box">';
                $html .= '<strong>' . $this->formatKpiLabel($key) . ':</strong> ';
                $html .= is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
                $html .= '</div>';
            }
        }

        if (isset($reportData['filters_applied'])) {
            $html .= '<h2>فیلترهای اعمال شده</h2>';
            $html .= '<table>';
            foreach ($reportData['filters_applied'] as $filter) {
                $html .= '<tr>';
                $html .= '<th>' . ($filter['label'] ?? 'فیلتر') . '</th>';
                $html .= '<td>' . ($filter['value'] ?? '-') . '</td>';
                $html .= '</tr>';
            }
            $html .= '</table>';
        }

        $html .= '
            </div>
            <div class="footer">
                <p>این گزارش توسط سیستم Beauty CRM تولید شده است</p>
            </div>
        </body>
        </html>
        ';

        return $html;
    }

    /**
     * Get report title in Persian.
     */
    protected function getReportTitle($reportType)
    {
        $titles = [
            'customers' => 'مشتریان',
            'reservations' => 'نوبت‌ها',
            'finance' => 'مالی',
            'personnel' => 'پرسنل',
            'satisfaction' => 'رضایت‌سنجی',
            'sms' => 'پیامک',
        ];

        return $titles[$reportType] ?? 'گزارش';
    }

    /**
     * Format KPI label for display.
     */
    protected function formatKpiLabel($key)
    {
        $labels = [
            'total_customers' => 'تعداد کل مشتریان',
            'active_customers' => 'مشتریان فعال',
            'inactive_customers' => 'مشتریان غیرفعال',
            'average_age' => 'میانگین سنی',
            'repeat_visits' => 'مراجعات مجدد',
            'total_appointments' => 'تعداد کل نوبت‌ها',
            'canceled_appointments' => 'نوبت‌های لغو شده',
            'total_income' => 'کل درآمد',
            'total_expense' => 'کل هزینه',
            'net_profit' => 'سود خالص',
            'total_staff' => 'تعداد پرسنل',
            'avg_satisfaction' => 'میانگین رضایت',
            'total_sms' => 'تعداد کل پیامک',
        ];

        return $labels[$key] ?? $key;
    }
}
