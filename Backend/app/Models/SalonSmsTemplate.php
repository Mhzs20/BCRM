<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalonSmsTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'salon_id',
        'event_type',
        'template',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function salon()
    {
        return $this->belongsTo(Salon::class);
    }

    /**

     */
    public static function getPlaceholdersForEvent(string $eventType): array
    {
        $commonPlaceholders = [
            '{{customer_name}}' => 'نام مشتری',
            '{{salon_name}}' => 'نام سالن',
        ];

        $eventSpecificPlaceholders = [];

        switch ($eventType) {
            case 'appointment_confirmation':
            case 'appointment_reminder':
            case 'appointment_cancellation':
            case 'appointment_modification':
                $eventSpecificPlaceholders = [
                    '{{appointment_date}}' => 'تاریخ نوبت (شمسی)',
                    '{{appointment_time}}' => 'ساعت نوبت',
                    '{{staff_name}}' => 'نام پرسنل',
                    '{{services_list}}' => 'لیست خدمات',
                    '{{appointment_cost}}' => 'هزینه نوبت',
                    '{{salon_phone}}' => 'شماره تماس سالن (باید از مدل Salon خوانده شود)',
                    '{{salon_address}}' => 'آدرس سالن (باید از مدل Salon خوانده شود)',
                ];
                break;
            case 'birthday_greeting':
                $eventSpecificPlaceholders = [
                    '{{customer_birth_date}}' => 'تاریخ تولد مشتری (شمسی)',
                ];
                break;
            case 'service_specific_notes':
                $eventSpecificPlaceholders = [
                    '{{service_name}}' => 'نام خدمت اصلی',
                    '{{service_specific_notes}}' => 'توضیحات ویژه خدمت',
                    '{{appointment_date}}' => 'تاریخ نوبت (شمسی)',
                    '{{appointment_time}}' => 'ساعت نوبت',
                ];
                break;
        }
        return array_merge($commonPlaceholders, $eventSpecificPlaceholders);
    }
}
