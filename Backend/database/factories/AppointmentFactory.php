<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Appointment>
 */
class AppointmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'salon_id' => \App\Models\Salon::factory(),
            'customer_id' => \App\Models\Customer::factory(),
            'appointment_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'status' => 'completed',
        ];
    }
}
