<?php

use App\Enums\Role;
use App\Enums\Status;
use App\Models\Category;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->seedRolesAndPermissions();
});

describe('Category', function () {
    describe('index', function () {
        it('returns paginated categories for admin', function () {
            $this->actingAsAdmin(Role::Admin);

            Category::factory()->count(2)->create();

            $this->getJson(route('admins.categories.index'))
                ->assertOk()
                ->assertJsonStructure(['data', 'links', 'meta']);
        });

        it('returns paginated categories for editor', function () {
            $this->actingAsAdmin(Role::Editor);

            $this->getJson(route('admins.categories.index'))->assertOk();
        });

        it('returns 401 when unauthenticated', function () {
            $this->getJson(route('admins.categories.index'))->assertUnauthorized();
        });
    });

    describe('show', function () {
        it('shows a category', function () {
            $this->actingAsAdmin(Role::Admin);

            $category = Category::factory()->create();

            $this->getJson(route('admins.categories.show', $category))
                ->assertOk()
                ->assertJsonPath('data.name', $category->name);
        });

        it('returns 404 for unknown category', function () {
            $this->actingAsAdmin(Role::SuperAdmin);

            $this->getJson(route('admins.categories.show', Str::uuid()))
                ->assertNotFound();
        });
    });

    describe('store', function () {
        it('creates a category for admin', function () {
            $this->actingAsAdmin(Role::Admin);

            $this->postJson(route('admins.categories.store'), [
                'name' => 'New Category',
                'description' => 'Category description',
                'icon' => 'book',
                'status' => Status::ACTIVE->value,
            ])->assertCreated()
                ->assertJsonPath('data.name', 'New Category');

            $this->assertDatabaseHas('categories', ['name' => 'New Category']);
        });

        it('returns 403 for editor', function () {
            $this->actingAsAdmin(Role::Editor);

            $this->postJson(route('admins.categories.store'), [
                'name' => 'New Category',
                'description' => 'Category description',
                'icon' => 'book',
                'status' => Status::ACTIVE->value,
            ])->assertForbidden();
        });

        it('returns 422 for invalid payload', function () {
            $this->actingAsAdmin(Role::SuperAdmin);

            $this->postJson(route('admins.categories.store'), [])
                ->assertUnprocessable();
        });
    });

    describe('update', function () {
        it('updates a category for admin', function () {
            $this->actingAsAdmin(Role::Admin);

            $category = Category::factory()->create();

            $this->putJson(route('admins.categories.update', $category), [
                'name' => 'Renamed Category',
                'description' => $category->description,
                'icon' => $category->icon,
                'status' => Status::ACTIVE->value,
            ])->assertOk()
                ->assertJsonPath('data.name', 'Renamed Category');
        });

        it('returns 403 for editor', function () {
            $this->actingAsAdmin(Role::Editor);

            $category = Category::factory()->create();

            $this->putJson(route('admins.categories.update', $category), [
                'name' => 'Renamed Category',
                'description' => $category->description,
                'icon' => $category->icon,
                'status' => Status::ACTIVE->value,
            ])->assertForbidden();
        });
    });

    describe('destroy', function () {
        it('deletes a category for admin', function () {
            $this->actingAsAdmin(Role::Admin);

            $category = Category::factory()->create();

            $this->deleteJson(route('admins.categories.destroy', $category))
                ->assertNoContent();

            expect(Category::find($category->id))->toBeNull();
        });

        it('returns 403 for editor', function () {
            $this->actingAsAdmin(Role::Editor);

            $category = Category::factory()->create();

            $this->deleteJson(route('admins.categories.destroy', $category))
                ->assertForbidden();
        });
    });
});
