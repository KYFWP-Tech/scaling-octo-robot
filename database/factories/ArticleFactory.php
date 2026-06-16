<?php

namespace Database\Factories;

use App\Enums\Status;
use App\Models\Article;
use App\Models\Category;
use App\Models\Contributor;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Article>
 */
class ArticleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(),
            'content' => fake()->paragraphs(3, true),
            'cover_image' => fake()->optional()->imageUrl(),
            'media' => null,
            'category_id' => Category::factory(),
            'status' => Status::INACTIVE,
            'is_featured' => false,
            'published_at' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Article $article) {
            if ($article->author_id) {
                return;
            }

            $contributor = Contributor::factory()->create();

            $user = User::factory()->create([
                'name' => $contributor->name,
                'email' => $contributor->email,
                'profile_id' => $contributor->id,
                'profile_type' => $contributor->getMorphClass(),
            ]);

            $article->author_id = $user->id;
            $article->author_type = $user->getMorphClass();
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
