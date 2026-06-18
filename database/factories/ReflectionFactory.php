<?php

namespace Database\Factories;

use App\Models\Contributor;
use App\Models\Reflection;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reflection>
 */
class ReflectionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'date' => fake()->unique()->dateTimeBetween('-1 year', '+1 year')->format('Y-m-d'),
            'title' => fake()->sentence(),
            'content' => fake()->paragraphs(3, true),
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Reflection $reflection) {
            if ($reflection->author_id) {
                return;
            }

            $contributor = Contributor::factory()->create();

            $user = User::factory()->create([
                'name' => $contributor->name,
                'email' => $contributor->email,
                'profile_id' => $contributor->id,
                'profile_type' => $contributor->getMorphClass(),
            ]);

            $reflection->author_id = $user->id;
        });
    }
}
