<?php

namespace Database\Factories;

use App\Enums\Status;
use App\Models\Contributor;
use App\Models\Podcast;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Podcast>
 */
class PodcastFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(),
            'content' => fake()->paragraphs(3, true),
            'cover_image' => null,
            'status' => Status::INACTIVE,
            'published_at' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Podcast $podcast) {
            if ($podcast->user_id) {
                return;
            }

            $contributor = Contributor::factory()->create();

            $user = User::factory()->create([
                'name' => $contributor->name,
                'email' => $contributor->email,
                'profile_id' => $contributor->id,
                'profile_type' => $contributor->getMorphClass(),
            ]);

            $podcast->user_id = $user->id;
        });
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Status::ACTIVE,
            'published_at' => now(),
        ]);
    }
}
