<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tenant>
 */
class TenantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'plan' => $this->faker->randomElement(['Basic', 'Premium', 'Enterprise']),
            'settings' => [
                'prompt' => 'You are a professional voice dispatcher.',
                'phone_mappings' => ['+1234567890' => 'primary'],
                'emergency_parameters' => ['after_hours' => true],
            ],
            'secret_key' => Str::random(32),
        ];
    }
}
