<?php

namespace Database\Factories;

use App\Models\Availability;
use App\Models\Employee;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Availability>
 */
class AvailabilityFactory extends Factory
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
            'day_of_week' => $this->faker->numberBetween(0, 6),
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'is_active' => true,
        ];
    }
}
