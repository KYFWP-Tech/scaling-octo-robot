<?php

use App\Enums\Role;
use App\Http\Requests\AssignRoleRequest;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role as SpatieRole;

beforeEach(function () {
    $this->seedRolesAndPermissions();
});

describe('AssignRoleRequest', function () {
    it('passes with a valid role', function () {
        $validator = Validator::make([
            'role' => Role::Admin->value,
        ], (new AssignRoleRequest)->rules());

        expect($validator->passes())->toBeTrue();
    });

    it('fails when :dataset', function (array $payload) {
        $validator = Validator::make($payload, (new AssignRoleRequest)->rules());

        expect($validator->fails())->toBeTrue();
    })->with('invalid_assign_role_payloads');

    it('fails when role is valid enum but not in database', function () {
        SpatieRole::where('name', Role::Editor->value)->delete();

        $validator = Validator::make([
            'role' => Role::Editor->value,
        ], (new AssignRoleRequest)->rules());

        expect($validator->fails())->toBeTrue();
    });
});
