<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\CustomerFeedback;
use App\Jobs\SendSatisfactionSurveySms;
use Illuminate\Http\Request;
use Carbon\Carbon;

class SatisfactionController extends Controller
{
    public function showByHash($hash)
    {
        $appointment = Appointment::where('hash', $hash)->firstOrFail();
        $feedback = $appointment->feedback; // Check if feedback already exists

        // Full default tags (as in design)
        $defaultStrengths = [
            ['key' => md5('خدمات بی نقص'), 'label' => 'خدمات بی نقص'],
            ['key' => md5('برخورد حرفه ای پرسنل'), 'label' => 'برخورد حرفه ای پرسنل'],
            ['key' => md5('محیط آرام و منظم'), 'label' => 'محیط آرام و منظم'],
            ['key' => md5('پرسنل با دقت و با حوصله'), 'label' => 'پرسنل با دقت و با حوصله'],
            ['key' => md5('نوبت دهی آسان'), 'label' => 'نوبت دهی آسان'],
            ['key' => md5('قیمت منصفانه'), 'label' => 'قیمت منصفانه'],
            ['key' => md5('مواد مصرفی با کیفیت'), 'label' => 'مواد مصرفی با کیفیت'],
            ['key' => md5('موزیک مناسب و آرام بخش'), 'label' => 'موزیک مناسب و آرام بخش'],
            ['key' => md5('رعایت بهداشت و نظافت'), 'label' => 'رعایت بهداشت و نظافت'],
            ['key' => md5('انرژی مثبت پرسنل و سالن'), 'label' => 'انرژی مثبت پرسنل و سالن'],
        ];

        $defaultWeaknesses = [
            ['key' => md5('معطلی زیاد'), 'label' => 'معطلی زیاد'],
            ['key' => md5('رفتار نامناسب پرسنل'), 'label' => 'رفتار نامناسب پرسنل'],
            ['key' => md5('محیط شلوغ و بی نظم'), 'label' => 'محیط شلوغ و بی نظم'],
            ['key' => md5('کیفیت پایین خدمات'), 'label' => 'کیفیت پایین خدمات'],
            ['key' => md5('عدم راهنمایی مناسب'), 'label' => 'عدم راهنمایی مناسب'],
            ['key' => md5('قیمت بالا'), 'label' => 'قیمت بالا'],
            ['key' => md5('موزیک نامناسب و آزاردهنده'), 'label' => 'موزیک نامناسب و آزاردهنده'],
            ['key' => md5('مواد مصرفی نامرغوب'), 'label' => 'مواد مصرفی نامرغوب'],
            ['key' => md5('انرژی منفی پرسنل و سالن'), 'label' => 'انرژی منفی پرسنل و سالن'],
            ['key' => md5('عدم رعایت بهداشت و نظافت'), 'label' => 'عدم رعایت بهداشت و نظافت'],
        ];

        // Load salon settings (if any) and parse JSON; settings may be stored as array of strings or array of objects
        $salonSettings = \App\Models\Setting::where('salon_id', $appointment->salon_id)->pluck('value', 'key');

        $rawStrengths = json_decode($salonSettings->get('satisfaction_strengths') ?? json_encode(array_column($defaultStrengths, 'label')), true);
        $rawWeaknesses = json_decode($salonSettings->get('satisfaction_weaknesses') ?? json_encode(array_column($defaultWeaknesses, 'label')), true);

        // normalize helper
        $normalizeTags = function ($arr) {
            $out = [];
            foreach ($arr as $item) {
                if (is_array($item) && isset($item['label'])) {
                    $label = $item['label'];
                    $key = $item['key'] ?? md5($label);
                } else {
                    $label = (string)$item;
                    $key = md5($label);
                }
                $out[] = ['key' => $key, 'label' => $label];
            }
            return $out;
        };

        $strengthTags = $normalizeTags($rawStrengths);
        $weaknessTags = $normalizeTags($rawWeaknesses);

        // prepare initial selected tags (if feedback exists)
        $initialStrengths = [];
        $initialWeaknesses = [];
        if ($feedback) {
            if (is_array($feedback->strengths_selected)) {
                foreach ($feedback->strengths_selected as $s) {
                    if (is_array($s) && isset($s['label'])) {
                        $initialStrengths[] = ['key' => $s['key'] ?? md5($s['label']), 'label' => $s['label']];
                    } else {
                        $label = (string)$s;
                        $initialStrengths[] = ['key' => md5($label), 'label' => $label];
                    }
                }
            }

            if (is_array($feedback->weaknesses_selected)) {
                foreach ($feedback->weaknesses_selected as $s) {
                    if (is_array($s) && isset($s['label'])) {
                        $initialWeaknesses[] = ['key' => $s['key'] ?? md5($s['label']), 'label' => $s['label']];
                    } else {
                        $label = (string)$s;
                        $initialWeaknesses[] = ['key' => md5($label), 'label' => $label];
                    }
                }
            }

            return view('appointments.satisfaction', [
                'appointment' => $appointment,
                'success_message' => 'از بازخورد شما سپاسگزاریم! شما قبلاً نظر خود را ثبت کرده‌اید.',
                'strengthTags' => $strengthTags,
                'weaknessTags' => $weaknessTags,
                'initialStrengths' => $initialStrengths,
                'initialWeaknesses' => $initialWeaknesses,
            ]);
        }

        return view('appointments.satisfaction', compact('appointment', 'strengthTags', 'weaknessTags'));
    }

    public function storeByHash(Request $request, $hash)
    {
        $appointment = Appointment::where('hash', $hash)->firstOrFail();

        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'text_feedback' => 'nullable|string',
            'strengths_selected' => 'nullable|string',
            'weaknesses_selected' => 'nullable|string',
        ]);

        $appointment->feedback()->create([
            'rating' => $request->rating,
            'text_feedback' => $request->text_feedback,
            'strengths_selected' => json_decode($request->strengths_selected, true) ?? [],
            'weaknesses_selected' => json_decode($request->weaknesses_selected, true) ?? [],
        ]);

        // Reload the appointment to ensure the feedback relationship is available to the view
        $appointment->load('feedback');

        // Redirect to the show route so showByHash loads tags and initial selections properly
        return redirect()->route('satisfaction.show.hash', ['hash' => $appointment->hash]);
    }

    public function sendSurvey(Appointment $appointment)
    {
        // Ensure appointment_date is treated as a date only, and combine with end_time
        $appointment_end_datetime = Carbon::parse($appointment->appointment_date->format('Y-m-d') . ' ' . $appointment->end_time);

        if (Carbon::now()->lessThan($appointment_end_datetime)) {
            return response()->json(['message' => 'هنوز زمان نوبت به پایان نرسیده است.'], 422);
        }

    SendSatisfactionSurveySms::dispatch($appointment, $appointment->salon);

        // If satisfaction SMS was disabled, enable it for this manual send request
        if (!$appointment->send_satisfaction_sms) {
            $appointment->send_satisfaction_sms = true;
        }
        
        // Update status to processing immediately
        $appointment->satisfaction_sms_status = 'processing';
        $appointment->save();

        return response()->json(['message' => 'پیامک نظرسنجی با موفقیت ارسال شد.']);
    }
}
