<?php

namespace Tests;

use App\Enums\Role;
use App\Enums\Status;
use App\Models\Admin;
use App\Models\Article;
use App\Models\Category;
use App\Models\Contributor;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
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
            'author_id' => $user->id,
            'author_type' => $user->getMorphClass(),
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
            'cover_image' => 'https://example.com/cover.jpg',
            'media' => null,
            'category_id' => $category->id,
            'is_featured' => true,
        ];
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
