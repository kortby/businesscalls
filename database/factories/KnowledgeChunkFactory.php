<?php

namespace Database\Factories;

use App\Models\KnowledgeBase;
use App\Models\KnowledgeChunk;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KnowledgeChunk>
 */
class KnowledgeChunkFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'knowledge_base_id' => KnowledgeBase::factory(),
            'chunk_content' => $this->faker->paragraph,
            'vector_embedding' => array_fill(0, 1536, 0.0),
        ];
    }
}
