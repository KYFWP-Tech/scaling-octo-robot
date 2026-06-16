<?php

use App\Enums\Role;
use App\Enums\Status;
use App\Models\Admin;
use App\Models\User;
use App\Models\Verification;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->seedRolesAndPermissions();
});

describe('Admin', function () {
    describe('index', function () {
        it('returns paginated admins for super-admin', function () {
            $this->actingAsAdmin(Role::SuperAdmin);

            Admin::factory()->count(2)->create();

            $this->getJson(route('admins.index'))
                ->assertOk()
                ->assertJsonStructure(['data', 'links', 'meta']);
        });

        it('returns paginated admins for editor', function () {
            $this->actingAsAdmin(Role::Editor);

            $this->getJson(route('admins.index'))->assertOk();
        });

        it('returns 401 when unauthenticated', function () {
            $this->getJson(route('admins.index'))->assertUnauthorized();
        });
    });

    describe('show', function () {
        it('returns admin with role resource', function () {
            $this->actingAsAdmin(Role::SuperAdmin);
            ['admin' => $admin] = $this->createAdminWithRole(Role::Editor);

            $this->getJson(route('admins.show', $admin))
                ->assertOk()
                ->assertJsonPath('data.role.name', Role::Editor->value);
        });

        it('returns 404 for unknown admin', function () {
            $this->actingAsAdmin(Role::SuperAdmin);

            $this->getJson(route('admins.show', fake()->uuid()))
                ->assertNotFound();
        });

        it('returns 401 when unauthenticated', function () {
            ['admin' => $admin] = $this->createAdminWithRole(Role::Admin);

            $this->getJson(route('admins.show', $admin))->assertUnauthorized();
        });
    });

    describe('store', function () {
        it('creates admin and user for super-admin', function () {
            $this->actingAsAdmin(Role::SuperAdmin);

            $this->postJson(route('admins.store'), [
                'name' => 'New Admin',
                'email' => 'newadmin@example.com',
                'status' => Status::INACTIVE->value,
            ])->assertCreated()
                ->assertJsonPath('data.email', 'newadmin@example.com');

            expect(User::where('email', 'newadmin@example.com')->exists())->toBeTrue();
            expect(Admin::where('email', 'newadmin@example.com')->exists())->toBeTrue();
        });

        it('returns 403 for admin role without store permission', function () {
            $this->actingAsAdmin(Role::Admin);

            $this->postJson(route('admins.store'), [
                'name' => 'New Admin',
                'email' => 'newadmin@example.com',
                'status' => Status::INACTIVE->value,
            ])->assertForbidden();
        });

        it('returns 403 for editor', function () {
            $this->actingAsAdmin(Role::Editor);

            $this->postJson(route('admins.store'), [
                'name' => 'New Admin',
                'email' => 'newadmin@example.com',
                'status' => Status::INACTIVE->value,
            ])->assertForbidden();
        });

        it('returns 422 for invalid payload', function () {
            $this->actingAsAdmin(Role::SuperAdmin);

            $this->postJson(route('admins.store'), [])->assertUnprocessable();
        });
    });

    describe('update', function () {
        it('updates admin profile for super-admin', function () {
            $this->actingAsAdmin(Role::SuperAdmin);
            ['admin' => $target] = $this->createAdminWithRole(Role::Editor);

            $this->putJson(route('admins.update', $target), [
                'name' => 'Updated Name',
            ])->assertOk()
                ->assertJsonPath('data.name', 'Updated Name');
        });

        it('re-verifies email when email changes', function () {
            $this->actingAsAdmin(Role::SuperAdmin);
            ['admin' => $target] = $this->createAdminWithRole(Role::Editor);

            $this->putJson(route('admins.update', $target), [
                'email' => 'changed@example.com',
            ])->assertOk();

            $target->refresh()->load('user');
            expect($target->user->email_verified_at)->toBeNull();
            expect(Verification::where('email', 'changed@example.com')->exists())->toBeTrue();
        });

        it('returns 403 when updating own profile', function () {
            ['admin' => $admin, 'user' => $user] = $this->createAdminWithRole(Role::Admin);
            Sanctum::actingAs($user);

            $this->putJson(route('admins.update', $admin), [
                'name' => 'Self Update',
            ])->assertForbidden();
        });

        it('returns 403 for admin without update permission', function () {
            $this->actingAsAdmin(Role::Admin);
            ['admin' => $target] = $this->createAdminWithRole(Role::Editor);

            $this->putJson(route('admins.update', $target), [
                'name' => 'Nope',
            ])->assertForbidden();
        });
    });

    describe('assignRole', function () {
        it('assigns role to admin', function () {
            $this->actingAsAdmin(Role::SuperAdmin);
            ['admin' => $target] = $this->createAdminWithRole(Role::Editor);

            $this->putJson(route('admins.assign-role', $target), [
                'role' => Role::Admin->value,
            ])->assertOk()
                ->assertJsonPath('data.role.name', Role::Admin->value);
        });

        it('returns 403 for editor without roles.update', function () {
            $this->actingAsAdmin(Role::Editor);
            ['admin' => $target] = $this->createAdminWithRole(Role::Editor);

            $this->putJson(route('admins.assign-role', $target), [
                'role' => Role::Admin->value,
            ])->assertForbidden();
        });

        it('allows super-admin to demote the only super-admin', function () {
            ['admin' => $onlySuperAdmin, 'user' => $superUser] = $this->createAdminWithRole(Role::SuperAdmin);
            Sanctum::actingAs($superUser);

            $this->putJson(route('admins.assign-role', $onlySuperAdmin), [
                'role' => Role::Admin->value,
            ])->assertOk();
        });

        it('returns 422 for invalid role', function () {
            $this->actingAsAdmin(Role::SuperAdmin);
            ['admin' => $target] = $this->createAdminWithRole(Role::Editor);

            $this->putJson(route('admins.assign-role', $target), [
                'role' => 'not-a-role',
            ])->assertUnprocessable();
        });
    });

    describe('destroy', function () {
        it('deletes admin and user for super-admin', function () {
            $this->actingAsAdmin(Role::SuperAdmin);
            ['admin' => $target, 'user' => $targetUser] = $this->createAdminWithRole(Role::Editor);

            $this->deleteJson(route('admins.destroy', $target))->assertNoContent();

            expect(Admin::find($target->id))->toBeNull();
            expect(User::find($targetUser->id))->toBeNull();
        });

        it('returns 403 when deleting own profile', function () {
            ['admin' => $admin, 'user' => $user] = $this->createAdminWithRole(Role::Admin);
            Sanctum::actingAs($user);

            $this->deleteJson(route('admins.destroy', $admin))->assertForbidden();
        });

        it('allows super-admin to delete their own profile', function () {
            ['admin' => $admin, 'user' => $user] = $this->createAdminWithRole(Role::SuperAdmin);
            Sanctum::actingAs($user);

            $this->deleteJson(route('admins.destroy', $admin))->assertNoContent();
        });

        it('allows deleting a super-admin when another super-admin exists', function () {
            ['admin' => $admin] = $this->createAdminWithRole(Role::SuperAdmin);
            $this->actingAsAdmin(Role::SuperAdmin, ['email' => 'other@example.com']);

            $this->deleteJson(route('admins.destroy', $admin))->assertNoContent();
        });

        it('returns 403 for admin without destroy permission', function () {
            $this->actingAsAdmin(Role::Admin);
            ['admin' => $target] = $this->createAdminWithRole(Role::Editor);

            $this->deleteJson(route('admins.destroy', $target))->assertForbidden();
        });
    });

    describe('acceptInvitation', function () {
        it('returns 401 when unauthenticated', function () {
            $this->postJson(route('admins.accept-invitation'), [
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'token' => 'missing',
            ])->assertUnauthorized();
        });

        it('completes invitation for authenticated user', function () {
            ['admin' => $admin, 'user' => $user] = $this->createAdminWithRole(Role::Editor);
            $user->forceFill(['email_verified_at' => null, 'password' => Hash::make('temp')])->save();

            $verification = Verification::create([
                'email' => $user->email,
                'code' => 'invite-code-123',
                'expires_at' => now()->addDay(),
            ]);

            Sanctum::actingAs($user);

            $this->postJson(route('admins.accept-invitation'), [
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
                'token' => $verification->code,
            ])->assertOk();

            $user->refresh();
            expect($user->email_verified_at)->not->toBeNull();
            expect(Verification::find($verification->id))->toBeNull();
        });

        it('returns 422 when invitation expired', function () {
            ['user' => $user] = $this->createAdminWithRole(Role::Editor);

            $verification = Verification::create([
                'email' => $user->email,
                'code' => 'expired-code',
                'expires_at' => now()->subDay(),
            ]);

            Sanctum::actingAs($user);

            $this->postJson(route('admins.accept-invitation'), [
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
                'token' => $verification->code,
            ])->assertStatus(422);
        });

        it('returns 422 for invalid token', function () {
            $this->actingAsAdmin(Role::Editor);

            $this->postJson(route('admins.accept-invitation'), [
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
                'token' => 'does-not-exist',
            ])->assertUnprocessable();
        });
    });
});
