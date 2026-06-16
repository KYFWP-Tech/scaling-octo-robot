<?php

use App\Enums\Role;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->seedRolesAndPermissions();
});

describe('Permission', function () {
    describe('index', function () {
        it('lists permissions for editor', function () {
            $this->actingAsAdmin(Role::Editor);

            $this->getJson(route('admins.permissions.index'))
                ->assertOk()
                ->assertJsonStructure(['data' => [['id', 'name']]]);
        });

        it('returns 401 when unauthenticated', function () {
            $this->getJson(route('admins.permissions.index'))->assertUnauthorized();
        });
    });

    describe('show', function () {
        it('shows a permission', function () {
            $this->actingAsAdmin(Role::Admin);

            $permission = Permission::where('name', 'admins.index')->first();

            $this->getJson(route('admins.permissions.show', $permission))
                ->assertOk()
                ->assertJsonPath('data.name', 'admins.index');
        });
    });

    describe('update', function () {
        it('updates permission name for admin', function () {
            $this->actingAsAdmin(Role::Admin);

            $permission = Permission::create([
                'name' => 'temp.permission',
                'guard_name' => 'web',
            ]);

            $this->putJson(route('admins.permissions.update', $permission), [
                'name' => 'temp.permission.renamed',
            ])->assertOk()
                ->assertJsonPath('data.name', 'temp.permission.renamed');
        });

        it('returns 403 for editor', function () {
            $this->actingAsAdmin(Role::Editor);

            $permission = Permission::where('name', 'admins.index')->first();

            $this->putJson(route('admins.permissions.update', $permission), [
                'name' => 'admins.list',
            ])->assertForbidden();
        });

        it('returns 422 for duplicate name', function () {
            $this->actingAsAdmin(Role::SuperAdmin);

            $permission = Permission::create([
                'name' => 'another.temp',
                'guard_name' => 'web',
            ]);

            $this->putJson(route('admins.permissions.update', $permission), [
                'name' => 'admins.index',
            ])->assertUnprocessable();
        });
    });

    it('does not allow creating permissions via POST', function () {
        $this->actingAsAdmin(Role::SuperAdmin);

        $this->postJson(route('admins.permissions.index'), [
            'name' => 'new.permission',
        ])->assertMethodNotAllowed();
    });
});
