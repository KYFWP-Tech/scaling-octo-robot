<?php

namespace Database\Factories;

use App\Enums\Status;
use App\Models\Episode;
use App\Models\Podcast;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Episode>
 */
class EpisodeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'podcast_id' => Podcast::factory(),
            'title' => fake()->sentence(),
            'content' => fake()->paragraphs(3, true),
            'file' => null,
            'episode_number' => fake()->numberBetween(1, 100),
            'status' => Status::INACTIVE,
            'published_at' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Status::ACTIVE,
            'published_at' => now(),
        ]);
    }
}
