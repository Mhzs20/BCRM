<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // Appointment Management
            [
                'name' => 'view_appointments',
                'display_name' => 'مشاهده نوبت‌ها',
                'description' => 'امکان مشاهده لیست و جزئیات نوبت‌ها',
                'category' => 'appointments',
            ],
            [
                'name' => 'create_appointments',
                'display_name' => 'ایجاد نوبت',
                'description' => 'امکان ایجاد نوبت جدید',
                'category' => 'appointments',
            ],
            [
                'name' => 'edit_appointments',
                'display_name' => 'ویرایش نوبت',
                'description' => 'امکان ویرایش نوبت‌های موجود',
                'category' => 'appointments',
            ],
            [
                'name' => 'delete_appointments',
                'display_name' => 'حذف نوبت',
                'description' => 'امکان حذف نوبت‌ها',
                'category' => 'appointments',
            ],
            [
                'name' => 'manage_appointment_status',
                'display_name' => 'مدیریت وضعیت نوبت',
                'description' => 'امکان تغییر وضعیت نوبت (تایید، لغو، انجام شده و ...)',
                'category' => 'appointments',
            ],

            // Customer Management
            [
                'name' => 'view_customers',
                'display_name' => 'مشاهده مشتریان',
                'description' => 'امکان مشاهده لیست و پروفایل مشتریان',
                'category' => 'customers',
            ],
            [
                'name' => 'create_customers',
                'display_name' => 'ایجاد مشتری',
                'description' => 'امکان ثبت مشتری جدید',
                'category' => 'customers',
            ],
            [
                'name' => 'edit_customers',
                'display_name' => 'ویرایش مشتری',
                'description' => 'امکان ویرایش اطلاعات مشتریان',
                'category' => 'customers',
            ],
            [
                'name' => 'delete_customers',
                'display_name' => 'حذف مشتری',
                'description' => 'امکان حذف مشتریان',
                'category' => 'customers',
            ],
            [
                'name' => 'view_customer_history',
                'display_name' => 'مشاهده تاریخچه مشتری',
                'description' => 'امکان مشاهده تاریخچه نوبت‌ها و خدمات مشتری',
                'category' => 'customers',
            ],

            // Staff Management
            [
                'name' => 'view_staff',
                'display_name' => 'مشاهده پرسنل',
                'description' => 'امکان مشاهده لیست پرسنل',
                'category' => 'staff',
            ],
            [
                'name' => 'create_staff',
                'display_name' => 'ایجاد پرسنل',
                'description' => 'امکان اضافه کردن پرسنل جدید',
                'category' => 'staff',
            ],
            [
                'name' => 'edit_staff',
                'display_name' => 'ویرایش پرسنل',
                'description' => 'امکان ویرایش اطلاعات پرسنل',
                'category' => 'staff',
            ],
            [
                'name' => 'delete_staff',
                'display_name' => 'حذف پرسنل',
                'description' => 'امکان حذف پرسنل',
                'category' => 'staff',
            ],

            // Service Management
            [
                'name' => 'view_services',
                'display_name' => 'مشاهده خدمات',
                'description' => 'امکان مشاهده لیست خدمات',
                'category' => 'services',
            ],
            [
                'name' => 'create_services',
                'display_name' => 'ایجاد خدمت',
                'description' => 'امکان افزودن خدمت جدید',
                'category' => 'services',
            ],
            [
                'name' => 'edit_services',
                'display_name' => 'ویرایش خدمت',
                'description' => 'امکان ویرایش خدمات موجود',
                'category' => 'services',
            ],
            [
                'name' => 'delete_services',
                'display_name' => 'حذف خدمت',
                'description' => 'امکان حذف خدمات',
                'category' => 'services',
            ],

            // Financial Management
            [
                'name' => 'view_financial_reports',
                'display_name' => 'مشاهده گزارشات مالی',
                'description' => 'امکان مشاهده گزارش‌های مالی و درآمدها',
                'category' => 'financial',
            ],
            [
                'name' => 'view_payments',
                'display_name' => 'مشاهده پرداخت‌ها',
                'description' => 'امکان مشاهده لیست پرداخت‌ها',
                'category' => 'financial',
            ],
            [
                'name' => 'create_payments',
                'display_name' => 'ثبت پرداخت',
                'description' => 'امکان ثبت پرداخت جدید',
                'category' => 'financial',
            ],
            [
                'name' => 'edit_payments',
                'display_name' => 'ویرایش پرداخت',
                'description' => 'امکان ویرایش پرداخت‌ها',
                'category' => 'financial',
            ],
            [
                'name' => 'delete_payments',
                'display_name' => 'حذف پرداخت',
                'description' => 'امکان حذف پرداخت‌ها',
                'category' => 'financial',
            ],
            [
                'name' => 'view_cashbox',
                'display_name' => 'مشاهده صندوق',
                'description' => 'امکان مشاهده صندوق و تراکنش‌های مالی',
                'category' => 'financial',
            ],
            [
                'name' => 'manage_cashbox',
                'display_name' => 'مدیریت صندوق',
                'description' => 'امکان انجام عملیات در صندوق',
                'category' => 'financial',
            ],

            // SMS Management
            [
                'name' => 'view_sms_reports',
                'display_name' => 'مشاهده گزارشات پیامک',
                'description' => 'امکان مشاهده گزارش پیامک‌های ارسالی',
                'category' => 'sms',
            ],
            [
                'name' => 'send_sms',
                'display_name' => 'ارسال پیامک',
                'description' => 'امکان ارسال پیامک به مشتریان',
                'category' => 'sms',
            ],
            [
                'name' => 'manage_sms_templates',
                'display_name' => 'مدیریت قالب پیامک',
                'description' => 'امکان ایجاد و ویرایش قالب‌های پیامکی',
                'category' => 'sms',
            ],
            [
                'name' => 'manage_sms_campaigns',
                'display_name' => 'مدیریت کمپین پیامکی',
                'description' => 'امکان ایجاد و مدیریت کمپین‌های پیامکی',
                'category' => 'sms',
            ],

            // Reports
            [
                'name' => 'view_appointment_reports',
                'display_name' => 'مشاهده گزارشات نوبت',
                'description' => 'امکان مشاهده گزارش‌های مربوط به نوبت‌ها',
                'category' => 'reports',
            ],
            [
                'name' => 'view_customer_reports',
                'display_name' => 'مشاهده گزارشات مشتری',
                'description' => 'امکان مشاهده گزارش‌های مربوط به مشتریان',
                'category' => 'reports',
            ],
            [
                'name' => 'view_staff_reports',
                'display_name' => 'مشاهده گزارشات پرسنل',
                'description' => 'امکان مشاهده گزارش‌های عملکرد پرسنل',
                'category' => 'reports',
            ],
            [
                'name' => 'export_reports',
                'display_name' => 'خروجی گزارشات',
                'description' => 'امکان دانلود و خروجی گرفتن از گزارش‌ها',
                'category' => 'reports',
            ],

            // Salon Settings
            [
                'name' => 'view_salon_settings',
                'display_name' => 'مشاهده تنظیمات سالن',
                'description' => 'امکان مشاهده تنظیمات سالن',
                'category' => 'settings',
            ],
            [
                'name' => 'edit_salon_settings',
                'display_name' => 'ویرایش تنظیمات سالن',
                'description' => 'امکان تغییر تنظیمات سالن',
                'category' => 'settings',
            ],
            [
                'name' => 'manage_holidays',
                'display_name' => 'مدیریت تعطیلات',
                'description' => 'امکان تعیین روزها و ساعات تعطیلی سالن',
                'category' => 'settings',
            ],
            [
                'name' => 'manage_working_hours',
                'display_name' => 'مدیریت ساعات کاری',
                'description' => 'امکان تنظیم ساعات کاری سالن',
                'category' => 'settings',
            ],

            // Dashboard
            [
                'name' => 'view_dashboard',
                'display_name' => 'مشاهده داشبورد',
                'description' => 'امکان مشاهده داشبورد و آمار کلی',
                'category' => 'dashboard',
            ],
            [
                'name' => 'view_analytics',
                'display_name' => 'مشاهده تحلیل‌ها',
                'description' => 'امکان مشاهده تحلیل‌ها و نمودارها',
                'category' => 'dashboard',
            ],

            // Customer Follow-up
            [
                'name' => 'view_followups',
                'display_name' => 'مشاهده پیگیری‌ها',
                'description' => 'امکان مشاهده لیست پیگیری مشتریان',
                'category' => 'followup',
            ],
            [
                'name' => 'create_followups',
                'display_name' => 'ایجاد پیگیری',
                'description' => 'امکان ثبت پیگیری جدید برای مشتریان',
                'category' => 'followup',
            ],
            [
                'name' => 'edit_followups',
                'display_name' => 'ویرایش پیگیری',
                'description' => 'امکان ویرایش پیگیری‌های موجود',
                'category' => 'followup',
            ],
            [
                'name' => 'delete_followups',
                'display_name' => 'حذف پیگیری',
                'description' => 'امکان حذف پیگیری‌ها',
                'category' => 'followup',
            ],

            // Satisfaction Survey
            [
                'name' => 'view_surveys',
                'display_name' => 'مشاهده نظرسنجی‌ها',
                'description' => 'امکان مشاهده نتایج نظرسنجی‌های رضایت',
                'category' => 'survey',
            ],
            [
                'name' => 'manage_surveys',
                'display_name' => 'مدیریت نظرسنجی',
                'description' => 'امکان ایجاد و مدیریت نظرسنجی‌ها',
                'category' => 'survey',
            ],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission['name']],
                $permission
            );
        }

        $this->command->info('✅ Permissions seeded successfully!');
        $this->command->info('Total permissions: ' . count($permissions));
    }
}
