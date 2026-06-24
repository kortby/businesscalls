<?php

namespace Database\Factories;

use App\Models\CustomVoice;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomVoice>
 */
class CustomVoiceFactory extends Factory
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
            'voice_name' => $this->faker->name,
            'provider_voice_id' => 'custom-voice-id-'.$this->faker->uuid,
            'status' => 'active',
        ];
    }
}
