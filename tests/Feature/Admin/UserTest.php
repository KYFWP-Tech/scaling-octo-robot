<?php

use App\Enums\Role;
use App\Enums\Status;
use App\Models\Contributor;
use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->seedRolesAndPermissions();
});

describe('User', function () {
    describe('index', function () {
        it('returns paginated users for admin', function () {
            $this->actingAsAdmin(Role::Admin);

            $this->createContributorWithUser();

            $this->getJson(route('admins.users.index'))
                ->assertOk()
                ->assertJsonStructure(['data', 'links', 'meta']);
        });

        it('returns paginated users for editor', function () {
            $this->actingAsAdmin(Role::Editor);

            $this->getJson(route('admins.users.index'))->assertOk();
        });

        it('returns 401 when unauthenticated', function () {
            $this->getJson(route('admins.users.index'))->assertUnauthorized();
        });
    });

    describe('show', function () {
        it('shows a user', function () {
            $this->actingAsAdmin(Role::Admin);

            ['user' => $user] = $this->createContributorWithUser();

            $this->getJson(route('admins.users.show', $user))
                ->assertOk()
                ->assertJsonPath('data.id', $user->id);
        });

        it('returns 404 for unknown user', function () {
            $this->actingAsAdmin(Role::SuperAdmin);

            $this->getJson(route('admins.users.show', Str::uuid()))
                ->assertNotFound();
        });
    });

    describe('update', function () {
        it('updates user status for admin', function () {
            $this->actingAsAdmin(Role::Admin);

            ['user' => $user] = $this->createContributorWithUser();

            $this->putJson(route('admins.users.update', $user), [
                'status' => Status::BANNED->value,
            ])->assertOk()
                ->assertJsonPath('data.status.value', Status::BANNED->value);
        });

        it('returns 403 for editor', function () {
            $this->actingAsAdmin(Role::Editor);

            ['user' => $user] = $this->createContributorWithUser();

            $this->putJson(route('admins.users.update', $user), [
                'status' => Status::BANNED->value,
            ])->assertForbidden();
        });

        it('returns 422 for invalid status', function () {
            $this->actingAsAdmin(Role::SuperAdmin);

            ['user' => $user] = $this->createContributorWithUser();

            $this->putJson(route('admins.users.update', $user), [
                'status' => 99,
            ])->assertUnprocessable();
        });
    });

    describe('destroy', function () {
        it('deletes a user and their profile', function () {
            $this->actingAsAdmin(Role::Admin);

            ['contributor' => $contributor, 'user' => $user] = $this->createContributorWithUser();

            $this->deleteJson(route('admins.users.destroy', $user))
                ->assertNoContent();

            expect(User::find($user->id))->toBeNull();
            expect(Contributor::find($contributor->id))->toBeNull();
        });

        it('returns 403 when deleting own user account', function () {
            ['user' => $user] = $this->createAdminWithRole(Role::Admin);
            Sanctum::actingAs($user);

            $this->deleteJson(route('admins.users.destroy', $user))
                ->assertForbidden();
        });

        it('returns 403 for editor', function () {
            $this->actingAsAdmin(Role::Editor);

            ['user' => $user] = $this->createContributorWithUser();

            $this->deleteJson(route('admins.users.destroy', $user))
                ->assertForbidden();
        });
    });

    it('does not allow creating users via POST', function () {
        $this->actingAsAdmin(Role::SuperAdmin);

        $this->postJson(route('admins.users.index'), [
            'name' => 'New User',
        ])->assertMethodNotAllowed();
    });
});
