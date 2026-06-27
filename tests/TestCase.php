<?php

namespace Tests;

use App\Enums\Role;
use App\Enums\Status;
use App\Models\Admin;
use App\Models\Article;
use App\Models\Category;
use App\Models\Contributor;
use App\Models\Reflection;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

abstract class TestCase extends BaseTestCase
{
    protected function seedRolesAndPermissions(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    /**
     * @param  array<string, mixed>  $adminAttrs
     * @return array{admin: Admin, user: \App\Models\User}
     */
    protected function createAdminWithRole(Role $role, array $adminAttrs = []): array
    {
        $admin = Admin::factory()->create($adminAttrs);

        $user = $admin->makeUser();

        $user->forceFill([
            'status' => Status::ACTIVE->value,
            'password' => 'password',
        ])->save();

        $user->assignRole($role->value);

        return [
            'admin' => $admin->fresh(),
            'user' => $user->fresh(),
        ];
    }

    /**
     * @param  array<string, mixed>  $adminAttrs
     */
    protected function actingAsAdmin(Role $role, array $adminAttrs = []): static
    {
        ['user' => $user] = $this->createAdminWithRole($role, $adminAttrs);

        Sanctum::actingAs($user);

        return $this;
    }

    /**
     * @param  array<string, mixed>  $contributorAttrs
     * @return array{contributor: Contributor, user: User}
     */
    protected function createContributorWithUser(array $contributorAttrs = []): array
    {
        $contributor = Contributor::factory()->create($contributorAttrs);

        $user = User::factory()->create([
            'name' => $contributor->name,
            'email' => $contributor->email,
            'profile_id' => $contributor->id,
            'profile_type' => $contributor->getMorphClass(),
            'status' => Status::ACTIVE,
            'password' => 'password',
        ]);

        return [
            'contributor' => $contributor->fresh(),
            'user' => $user->fresh(),
        ];
    }

    /**
     * @param  array<string, mixed>  $contributorAttrs
     */
    protected function actingAsContributor(array $contributorAttrs = []): static
    {
        ['user' => $user] = $this->createContributorWithUser($contributorAttrs);

        Sanctum::actingAs($user);

        return $this;
    }

    /**
     * @param  array<string, mixed>  $attrs
     */
    protected function createCategory(array $attrs = []): Category
    {
        return Category::factory()->create($attrs);
    }

    /**
     * @param  array<string, mixed>  $attrs
     */
    protected function createArticleForUser(User $user, ?Category $category = null, array $attrs = []): Article
    {
        $category ??= $this->createCategory();

        return Article::factory()->create(array_merge([
            'user_id' => $user->id,
            'category_id' => $category->id,
        ], $attrs));
    }

    /**
     * @return array<string, mixed>
     */
    protected function validArticlePayload(?Category $category = null): array
    {
        $category ??= $this->createCategory();

        return [
            'title' => 'Test Article',
            'content' => 'Article body content.',
            'category_id' => $category->id,
            'is_featured' => true,
        ];
    }

    /**
     * @param  list<string>  $paths
     * @return array<int, array{type: string, path: string}>
     */
    protected function fakeArticleMediaOnStorage(array $paths = ['articles/test.jpg']): array
    {
        Storage::fake('s3');

        $media = [];

        foreach ($paths as $path) {
            Storage::disk('s3')->put($path, 'content');
            $media[] = ['type' => 'image', 'path' => $path];
        }

        return $media;
    }

    protected function fakeReflectionFileOnStorage(string $path = 'reflections/test.mp3'): string
    {
        Storage::fake('s3');
        Storage::disk('s3')->put($path, 'content');

        return $path;
    }

    /**
     * @param  array<string, mixed>  $attrs
     */
    protected function createReflectionForUser(User $user, array $attrs = []): Reflection
    {
        return Reflection::factory()->create(array_merge([
            'author_id' => $user->id,
        ], $attrs));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function validReflectionPayload(array $overrides = []): array
    {
        return array_merge([
            'date' => '2026-06-17',
            'title' => 'Test Reflection',
            'content' => 'Reflection body content.',
        ], $overrides);
    }

    protected function fakeReadingsApis(
        string $date = '2026-06-17',
        bool $includeCelebration = true,
        bool $includeSecondReading = false,
    ): void {
        $parsed = Carbon::parse($date);
        $readingsBase = rtrim(config('services.readings.base_url'), '/');
        $bibleBase = rtrim(config('services.bible.base_url'), '/');
        $monthDay = $parsed->format('m-d');

        $readings = [
            'firstReading' => '2 Kings 2:1, 6-14',
            'psalm' => 'Psalm 31:20, 21, 24',
            'gospel' => 'Matthew 6:1-6, 16-18',
        ];

        if ($includeSecondReading) {
            $readings['secondReading'] = 'Philemon 9-10, 12-17';
        }

        $passageResponse = [
            'reference' => 'Test Reference',
            'text' => 'Test passage text.',
            'verses' => [
                [
                    'book_id' => 'MAT',
                    'book_name' => 'Matthew',
                    'chapter' => 6,
                    'verse' => 1,
                    'text' => 'Test verse.',
                ],
            ],
        ];

        $fakes = [
            "{$readingsBase}/readings/{$parsed->year}/{$monthDay}.json" => Http::response([
                'date' => $date,
                'monthDay' => $parsed->format('n/j'),
                'season' => 'Ordinary Time',
                'readings' => $readings,
            ]),
            "{$bibleBase}/*" => Http::response($passageResponse),
        ];

        $fakes["{$readingsBase}/liturgical-calendar/{$parsed->year}/{$monthDay}.json"] = $includeCelebration
            ? Http::response([
                'date' => $date,
                'celebration' => [
                    'name' => 'Test Celebration',
                    'type' => 'FERIA',
                    'quote' => '',
                    'description' => 'Test description.',
                ],
            ])
            : Http::response([], 404);

        Http::fake($fakes);
    }

    /**
     * @param  class-string<FormRequest>  $requestClass
     * @param  array<string, mixed>  $routeParameters
     */
    protected function createFormRequest(string $requestClass, string $routeName, string $method = 'PUT', array $routeParameters = []): FormRequest
    {
        $uri = $routeParameters === []
            ? route($routeName)
            : route($routeName, $routeParameters);

        /** @var FormRequest $request */
        $request = $requestClass::create($uri, $method);

        $request->setRouteResolver(function () use ($request, $routeName, $routeParameters) {
            $route = app('router')->getRoutes()->getByName($routeName);
            $route->bind($request);

            foreach ($routeParameters as $key => $value) {
                $route->setParameter($key, $value);
            }

            return $route;
        });

        return $request;
    }
}
