<?php

namespace Database\Seeders;

use App\Models\CustomerFeedback;
use App\Models\Appointment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SatisfactionFeedbackSeeder extends Seeder
{
    /**
     * Seed satisfaction survey feedback for salon 1043.
     * Creates realistic feedback data for completed appointments.
     */
    public function run(): void
    {
        $salonId = 1043;

        // Get all completed appointments for this salon
        $appointments = Appointment::where('salon_id', $salonId)
            ->where('status', 'completed')
            ->with('services')
            ->get();

        if ($appointments->isEmpty()) {
            $this->command->warn("No completed appointments found for salon {$salonId}");
            return;
        }

        // Strength and weakness labels (same as SatisfactionController defaults)
        $strengths = [
            'خدمات بی نقص',
            'برخورد حرفه ای پرسنل',
            'محیط آرام و منظم',
            'پرسنل با دقت و با حوصله',
            'نوبت دهی آسان',
            'قیمت منصفانه',
            'مواد مصرفی با کیفیت',
            'موزیک مناسب و آرام بخش',
            'رعایت بهداشت و نظافت',
            'انرژی مثبت پرسنل و سالن',
        ];

        $weaknesses = [
            'معطلی زیاد',
            'رفتار نامناسب پرسنل',
            'محیط شلوغ و بی نظم',
            'کیفیت پایین خدمات',
            'عدم راهنمایی مناسب',
            'قیمت بالا',
            'موزیک نامناسب و آزاردهنده',
            'مواد مصرفی نامرغوب',
            'انرژی منفی پرسنل و سالن',
            'عدم رعایت بهداشت و نظافت',
        ];

        $textFeedbacks = [
            'خیلی عالی بود، ممنون از کارتون',
            'در مجموع خوب بود ولی جای بهبود داره',
            'عالی! حتما دوباره میام',
            'خدمات خوب بود ولی زمان انتظار زیاد بود',
            'از کیفیت کار راضی بودم',
            'پرسنل خیلی حرفه‌ای و مودب بودن',
            'محیط سالن خیلی تمیز و مرتب بود',
            'قیمت نسبت به کیفیت منصفانه بود',
            null, // Some customers don't leave text
            null,
            null,
            'بسیار عالی، به همه دوستام معرفی میکنم',
            'خوب بود',
            null,
            'کار حرفه‌ای، ممنون',
        ];

        $created = 0;

        foreach ($appointments as $appointment) {
            // Skip if feedback already exists
            if (CustomerFeedback::where('appointment_id', $appointment->id)->exists()) {
                continue;
            }

            // Make sure survey_sms_sent_at is set for these appointments
            if (!$appointment->survey_sms_sent_at) {
                $appointment->survey_sms_sent_at = Carbon::parse($appointment->appointment_date)->addHours(rand(2, 6));
                $appointment->save();
            }

            // Generate realistic rating (weighted toward 4-5 for a decent salon)
            $rating = $this->weightedRating();

            // Pick random strengths (1-4 items, more for higher ratings)
            $numStrengths = $rating >= 4 ? rand(2, 4) : rand(0, 2);
            $selectedStrengths = collect($strengths)->random(min($numStrengths, count($strengths)))->values()->toArray();

            // Pick random weaknesses (0-3 items, more for lower ratings)
            $numWeaknesses = $rating <= 3 ? rand(1, 3) : rand(0, 1);
            $selectedWeaknesses = $numWeaknesses > 0
                ? collect($weaknesses)->random(min($numWeaknesses, count($weaknesses)))->values()->toArray()
                : [];

            // Get staff_id and service_id from appointment
            $staffId = $appointment->staff_id;
            $serviceId = $appointment->services->first()?->id;

            // submittedAt: a few hours after the survey SMS was sent
            $submittedAt = Carbon::parse($appointment->survey_sms_sent_at)->addMinutes(rand(10, 720));

            CustomerFeedback::create([
                'appointment_id' => $appointment->id,
                'staff_id' => $staffId,
                'service_id' => $serviceId,
                'rating' => $rating,
                'text_feedback' => $textFeedbacks[array_rand($textFeedbacks)],
                'strengths_selected' => $selectedStrengths,
                'weaknesses_selected' => $selectedWeaknesses,
                'is_submitted' => true,
                'submitted_at' => $submittedAt,
                'created_at' => $submittedAt,
                'updated_at' => $submittedAt,
            ]);

            $created++;
        }

        $this->command->info("Created {$created} feedback records for salon {$salonId}");
    }

    /**
     * Generate a weighted random rating (biased toward 4-5).
     */
    private function weightedRating(): int
    {
        $weights = [
            1 => 3,   // 3% chance
            2 => 7,   // 7% chance
            3 => 15,  // 15% chance
            4 => 35,  // 35% chance
            5 => 40,  // 40% chance
        ];

        $total = array_sum($weights);
        $rand = rand(1, $total);
        $cumulative = 0;

        foreach ($weights as $rating => $weight) {
            $cumulative += $weight;
            if ($rand <= $cumulative) {
                return $rating;
            }
        }

        return 4;
    }
}
