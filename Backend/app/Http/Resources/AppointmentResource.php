<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Morilog\Jalali\Jalalian;
use Carbon\Carbon;
use App\Http\Resources\ServiceResource;

class AppointmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'hash' => $this->hash,
            'salon_id' => $this->salon_id,
            'customer_id' => $this->customer_id,
            'staff_id' => $this->staff_id,
            'appointment_date' => $this->appointment_date->format('Y-m-d'),
            'start_time' => Carbon::parse($this->start_time)->format('H:i:s'),
            'end_time' => Carbon::parse($this->end_time)->format('H:i:s'),
            'total_price' => $this->total_price,
            'deposit_amount' => $this->deposit_amount,
            'deposit_payment_method' => $this->deposit_payment_method,
            'total_duration' => $this->total_duration,
            'status' => $this->status,
            'notes' => $this->notes,
            'internal_note' => $this->internal_note,
            'internal_notes' => $this->internal_note,
            'deposit_required' => $this->deposit_required,
            'deposit_paid' => $this->deposit_paid,
            'reminder_sms_sent_at' => $this->reminder_sms_sent_at,
            'survey_sms_sent_at' => $this->survey_sms_sent_at,
            'feedback_id' => $this->feedback_id,
            'deleted_at' => $this->deleted_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'repair_date' => $this->repair_date,
            'reminder_time' => $this->reminder_time,
            'send_reminder_sms' => $this->send_reminder_sms,
            'reminder_sms_status' => $this->reminder_sms_status,
            'reminder_sms_message_id' => $this->reminder_sms_message_id,
            'send_satisfaction_sms' => $this->send_satisfaction_sms,
            'satisfaction_sms_status' => $this->satisfaction_sms_status,
            'satisfaction_sms_message_id' => $this->satisfaction_sms_message_id,
            'jalalidate' => Jalalian::fromCarbon($this->appointment_date)->format('Y/m/d'),
            'customer' => [
                'id' => optional($this->customer)->id,
                'name' => optional($this->customer)->name,
                'phone_number' => optional($this->customer)->phone_number,
            ],
            'staff' => [
                'id' => optional($this->staff)->id,
                'full_name' => optional($this->staff)->full_name,
            ],
            'services' => ServiceResource::collection($this->whenLoaded('services')),
        ];
    }

    /**
     * 
     */
    private function getStatusInFarsi(string $status): string
    {
        return match ($status) {
            'pending_confirmation' => 'در انتظار تایید',
            'confirmed' => 'تایید شده',
            'done' => 'انجام شده',
            'cancelled' => 'لغو شده',
            'no_show' => 'عدم حضور',
            default => 'نامشخص',
        };
    }
}
