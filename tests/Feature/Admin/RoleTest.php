<?php

use App\Enums\Role;
use Spatie\Permission\Models\Role as SpatieRole;

beforeEach(function () {
    $this->seedRolesAndPermissions();
});

describe('Role', function () {
    describe('index', function () {
        it('lists roles for editor', function () {
            $this->actingAsAdmin(Role::Editor);

            $this->getJson(route('roles.index'))
                ->assertOk()
                ->assertJsonStructure(['data' => [['id', 'name', 'permissions']]]);
        });

        it('returns 401 when unauthenticated', function () {
            $this->getJson(route('roles.index'))->assertUnauthorized();
        });
    });

    describe('show', function () {
        it('shows a role with permissions', function () {
            $this->actingAsAdmin(Role::Admin);

            $role = SpatieRole::where('name', Role::Editor->value)->first();

            $this->getJson(route('roles.show', $role))
                ->assertOk()
                ->assertJsonPath('data.name', Role::Editor->value);
        });
    });

    describe('update', function () {
        it('syncs permissions for admin', function () {
            $this->actingAsAdmin(Role::Admin);

            $role = SpatieRole::where('name', Role::Editor->value)->first();

            $this->putJson(route('roles.update', $role), [
                'permissions' => ['admins.index'],
            ])->assertOk()
                ->assertJsonPath('data.permissions.0.name', 'admins.index');
        });

        it('returns 403 for editor', function () {
            $this->actingAsAdmin(Role::Editor);

            $role = SpatieRole::where('name', Role::Editor->value)->first();

            $this->putJson(route('roles.update', $role), [
                'permissions' => ['admins.index'],
            ])->assertForbidden();
        });

        it('returns 422 for invalid permissions', function () {
            $this->actingAsAdmin(Role::SuperAdmin);

            $role = SpatieRole::where('name', Role::Editor->value)->first();

            $this->putJson(route('roles.update', $role), [
                'permissions' => ['invalid.permission'],
            ])->assertUnprocessable();
        });
    });

    it('does not allow creating roles via POST', function () {
        $this->actingAsAdmin(Role::SuperAdmin);

        $this->postJson(route('roles.index'), [
            'name' => 'new-role',
        ])->assertMethodNotAllowed();
    });
});
