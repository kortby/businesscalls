<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Employee;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'employee_id' => Employee::factory(),
            'customer_phone' => $this->faker->phoneNumber(),
            'job_details' => $this->faker->sentence(),
            'status' => 'booked',
            'scheduled_start' => now()->addDays(1)->setTime(10, 0, 0),
        ];
    }
}
