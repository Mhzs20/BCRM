<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\SalonSmsTemplate;

class SmsTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            // Template for Appointment Confirmation
            [
                'salon_id' => null, // Global template (will be created for each salon)
                'event_type' => 'appointment_confirmation',
                'template' => '{{customer_name}} عزیز، نوبت شما برای {{service_names}} در تاریخ {{appointment_date}} ساعت {{start_time}} در {{salon_name}} تایید شد.',
                'is_active' => true,
            ],
            
            // Template for Appointment Reminder
            [
                'salon_id' => null, // Global template (will be created for each salon)
                'event_type' => 'appointment_reminder',
                'template' => '{{customer_name}} عزیز، یادآوری می‌کنیم که نوبت شما برای {{service_names}} فردا {{appointment_date}} ساعت {{start_time}} در {{salon_name}} می‌باشد.',
                'is_active' => true,
            ],
            
            // Template for Birthday Greeting
            [
                'salon_id' => null,
                'event_type' => 'birthday_greeting',
                'template' => 'زادروزتان مبارک {{customer_name}} عزیز! � از طرف تیم {{salon_name}} بهترین آرزوها را برایتان داریم. 🎂',
                'is_active' => true,
            ],
            
            // Template for Appointment Modification
            [
                'salon_id' => null,
                'event_type' => 'appointment_modification',
                'template' => '{{customer_name}} عزیز، نوبت شما در {{salon_name}} به تاریخ {{appointment_date}} ساعت {{start_time}} تغییر یافت.',
                'is_active' => true,
            ],
            
            // Template for Appointment Cancellation
            [
                'salon_id' => null,
                'event_type' => 'appointment_cancellation',
                'template' => '{{customer_name}} عزیز، نوبت شما در {{salon_name}} برای تاریخ {{appointment_date}} ساعت {{start_time}} لغو گردید.',
                'is_active' => true,
            ],
        ];

        // Get all active salons
        $salons = \App\Models\Salon::where('is_active', true)->get();
        
        if ($salons->isEmpty()) {
            $this->command->warn("⚠️  No active salons found. Templates will not be created.");
            return;
        }

        foreach ($salons as $salon) {
            foreach ($templates as $template) {
                // Set salon_id for this template
                $template['salon_id'] = $salon->id;
                
                // Check if template already exists for this salon
                $exists = SalonSmsTemplate::where('salon_id', $salon->id)
                    ->where('event_type', $template['event_type'])
                    ->first();
                    
                if (!$exists) {
                    $newTemplate = SalonSmsTemplate::create($template);
                    // محاسبه و به‌روزرسانی estimated_parts و estimated_cost
                    $newTemplate->updateEstimatedValues();
                    $this->command->info("✅ Template created for salon {$salon->name}: {$template['event_type']}");
                } else {
                    $this->command->warn("⚠️  Template already exists for salon {$salon->name}: {$template['event_type']}");
                }
            }
        }
        
        $totalTemplates = count($templates);
        $totalSalons = $salons->count();
        $expectedTotal = $totalTemplates * $totalSalons;
        
        $this->command->info("🎉 SMS Templates seeding completed!");
        $this->command->info("📊 Created templates for {$totalSalons} salons × {$totalTemplates} event types = {$expectedTotal} total templates");
    }
}
