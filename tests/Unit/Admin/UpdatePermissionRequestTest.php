<?php

use App\Http\Requests\UpdatePermissionRequest;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->seedRolesAndPermissions();
});

describe('UpdatePermissionRequest', function () {
    it('passes with a valid unique name', function () {
        $permission = Permission::where('name', 'admins.index')->first();

        $request = $this->createFormRequest(
            UpdatePermissionRequest::class,
            'permissions.update',
            'PUT',
            ['permission' => $permission],
        );

        $validator = Validator::make([
            'name' => 'admins.list',
        ], $request->rules());

        expect($validator->passes())->toBeTrue();
    });

    it('fails when name is missing', function () {
        $permission = Permission::where('name', 'admins.index')->first();

        $request = $this->createFormRequest(
            UpdatePermissionRequest::class,
            'permissions.update',
            'PUT',
            ['permission' => $permission],
        );

        $validator = Validator::make([], $request->rules());

        expect($validator->fails())->toBeTrue();
    });

    it('fails when name is already used by another permission', function () {
        $permission = Permission::where('name', 'admins.index')->first();

        $request = $this->createFormRequest(
            UpdatePermissionRequest::class,
            'permissions.update',
            'PUT',
            ['permission' => $permission],
        );

        $validator = Validator::make([
            'name' => 'admins.show',
        ], $request->rules());

        expect($validator->fails())->toBeTrue();
    });
});
