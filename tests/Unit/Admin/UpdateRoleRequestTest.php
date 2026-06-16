<?php

use App\Http\Requests\Admin\UpdateRoleRequest;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seedRolesAndPermissions();
});

describe('UpdateRoleRequest', function () {
    it('passes with valid permissions', function () {
        $validator = Validator::make([
            'permissions' => ['admins.index', 'admins.show'],
        ], (new UpdateRoleRequest)->rules());

        expect($validator->passes())->toBeTrue();
    });

    it('fails when permissions is empty', function () {
        $validator = Validator::make([
            'permissions' => [],
        ], (new UpdateRoleRequest)->rules());

        expect($validator->fails())->toBeTrue();
    });

    it('fails when permissions is not an array', function () {
        $validator = Validator::make([
            'permissions' => 'admins.index',
        ], (new UpdateRoleRequest)->rules());

        expect($validator->fails())->toBeTrue();
    });

    it('fails when permission name does not exist', function () {
        $validator = Validator::make([
            'permissions' => ['nonexistent.permission'],
        ], (new UpdateRoleRequest)->rules());

        expect($validator->fails())->toBeTrue();
    });
});
