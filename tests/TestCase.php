<?php

namespace Tests;

use App\Enums\Role;
use App\Enums\Status;
use App\Models\Admin;
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
