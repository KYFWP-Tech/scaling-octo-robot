<?php

use App\Enums\Role;
use App\Models\Reflection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->seedRolesAndPermissions();
    Storage::fake('s3');
    Cache::flush();
});

describe('Reflection', function () {
    describe('index', function () {
        it('returns paginated reflections for admin', function () {
            $this->actingAsAdmin(Role::Admin);

            Reflection::factory()->count(2)->create();

            $this->getJson(route('admins.reflections.index'))
                ->assertOk()
                ->assertJsonStructure(['data', 'links', 'meta']);
        });

        it('returns paginated reflections for editor', function () {
            $this->actingAsAdmin(Role::Editor);

            $this->getJson(route('admins.reflections.index'))->assertOk();
        });

        it('filters reflections by search', function () {
            $this->actingAsAdmin(Role::SuperAdmin);

            $reflection = Reflection::factory()->create(['title' => 'Unique Search Title']);
            Reflection::factory()->create(['title' => 'Other Reflection']);

            $this->getJson(route('admins.reflections.index', ['search' => 'Unique Search']))
                ->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.id', $reflection->id);
        });

        it('filters reflections by date', function () {
            $this->actingAsAdmin(Role::SuperAdmin);

            $reflection = Reflection::factory()->create(['date' => '2026-06-17']);
            Reflection::factory()->create(['date' => '2026-06-18']);

            $this->getJson(route('admins.reflections.index', ['date' => '2026-06-17']))
                ->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.id', $reflection->id);
        });

        it('returns 401 when unauthenticated', function () {
            $this->getJson(route('admins.reflections.index'))->assertUnauthorized();
        });
    });

    describe('show', function () {
        it('shows a reflection with author', function () {
            $this->actingAsAdmin(Role::Admin);

            $reflection = Reflection::factory()->create();

            $this->getJson(route('admins.reflections.show', $reflection))
                ->assertOk()
                ->assertJsonPath('data.id', $reflection->id)
                ->assertJsonStructure(['data' => ['author']]);
        });

        it('returns 404 for unknown reflection', function () {
            $this->actingAsAdmin(Role::SuperAdmin);

            $this->getJson(route('admins.reflections.show', Str::uuid()))
                ->assertNotFound();
        });
    });

    describe('update', function () {
        it('allows admin to update any reflection', function () {
            $this->actingAsAdmin(Role::Admin);

            $reflection = Reflection::factory()->create(['date' => '2026-06-17']);

            $this->putJson(route('admins.reflections.update', $reflection), array_merge(
                $this->validReflectionPayload(['date' => '2026-06-17']),
                ['title' => 'Admin Updated Title'],
            ))->assertOk()
                ->assertJsonPath('data.title', 'Admin Updated Title');
        });

        it('allows editor to update own reflection', function () {
            ['user' => $user] = $this->createAdminWithRole(Role::Editor);
            Sanctum::actingAs($user);

            $reflection = $this->createReflectionForUser($user, ['date' => '2026-06-17']);

            $this->putJson(route('admins.reflections.update', $reflection), array_merge(
                $this->validReflectionPayload(['date' => '2026-06-17']),
                ['title' => 'Editor Updated Title'],
            ))->assertOk()
                ->assertJsonPath('data.title', 'Editor Updated Title');
        });

        it('allows editor to update another authors reflection', function () {
            $this->actingAsAdmin(Role::Editor);

            ['user' => $otherUser] = $this->createContributorWithUser();
            $reflection = $this->createReflectionForUser($otherUser, ['date' => '2026-06-17']);

            $this->putJson(route('admins.reflections.update', $reflection), array_merge(
                $this->validReflectionPayload(['date' => '2026-06-17']),
                ['title' => 'Editor Moderated Title'],
            ))->assertOk()
                ->assertJsonPath('data.title', 'Editor Moderated Title');
        });

        it('returns 422 for invalid payload', function () {
            $this->actingAsAdmin(Role::SuperAdmin);

            $reflection = Reflection::factory()->create();

            $this->putJson(route('admins.reflections.update', $reflection), [])
                ->assertUnprocessable();
        });
    });

    describe('destroy', function () {
        it('allows admin to delete any reflection', function () {
            $this->actingAsAdmin(Role::Admin);

            $reflection = Reflection::factory()->create();

            $this->deleteJson(route('admins.reflections.destroy', $reflection))
                ->assertNoContent();

            expect(Reflection::find($reflection->id))->toBeNull();
        });

        it('allows editor to delete own reflection', function () {
            ['user' => $user] = $this->createAdminWithRole(Role::Editor);
            Sanctum::actingAs($user);

            $reflection = $this->createReflectionForUser($user);

            $this->deleteJson(route('admins.reflections.destroy', $reflection))
                ->assertNoContent();

            expect(Reflection::find($reflection->id))->toBeNull();
        });

        it('allows editor to delete another authors reflection', function () {
            $this->actingAsAdmin(Role::Editor);

            ['user' => $otherUser] = $this->createContributorWithUser();
            $reflection = $this->createReflectionForUser($otherUser);

            $this->deleteJson(route('admins.reflections.destroy', $reflection))
                ->assertNoContent();

            expect(Reflection::find($reflection->id))->toBeNull();
        });
    });

    describe('store', function () {
        it('creates a reflection for authorized admin', function () {
            $this->actingAsAdmin(Role::SuperAdmin);

            $payload = $this->validReflectionPayload();

            $this->postJson(route('admins.reflections.store'), $payload)
                ->assertCreated()
                ->assertJsonPath('data.title', $payload['title']);

            $this->assertDatabaseHas('reflections', [
                'title' => $payload['title'],
                'date' => $payload['date'],
            ]);
        });

        it('returns 401 when unauthenticated', function () {
            $this->postJson(route('admins.reflections.store'), $this->validReflectionPayload())
                ->assertUnauthorized();
        });

        it('creates a reflection with a valid audio file path', function () {
            $filePath = $this->fakeReflectionFileOnStorage();
            $this->actingAsAdmin(Role::SuperAdmin);
            $payload = $this->validReflectionPayload(['file' => $filePath]);

            $this->postJson(route('admins.reflections.store'), $payload)
                ->assertCreated()
                ->assertJsonPath('data.file', fn ($url) => is_string($url) && $url !== '');

            $this->assertDatabaseHas('reflections', [
                'title' => $payload['title'],
                'file' => $filePath,
            ]);
        });

        it('returns 422 when audio file path does not exist on storage', function () {
            Storage::fake('s3');
            $this->actingAsAdmin(Role::SuperAdmin);

            $this->postJson(route('admins.reflections.store'), $this->validReflectionPayload([
                'file' => 'reflections/missing.mp3',
            ]))
                ->assertUnprocessable()
                ->assertJsonPath('errors.file.0', 'The selected audio file does not exist.');
        });
    });
});
