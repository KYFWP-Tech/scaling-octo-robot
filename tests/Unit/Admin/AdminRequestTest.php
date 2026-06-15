<?php

use App\Enums\Role;
use App\Enums\Status;
use App\Http\Requests\AdminRequest;
use Illuminate\Support\Facades\Validator;

beforeEach(function () {
    $this->seedRolesAndPermissions();
});

describe('AdminRequest', function () {
    it('passes with valid data', function () {
        $validator = Validator::make([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'status' => Status::ACTIVE->value,
        ], (new AdminRequest)->rules());

        expect($validator->passes())->toBeTrue();
    });

    it('fails when :dataset', function (array $payload) {
        $validator = Validator::make($payload, (new AdminRequest)->rules());

        expect($validator->fails())->toBeTrue();
    })->with('invalid_admin_request_payloads');

    it('fails when email is already taken', function () {
        $this->createAdminWithRole(Role::Editor, ['email' => 'taken@example.com']);

        $validator = Validator::make([
            'name' => 'Jane Doe',
            'email' => 'taken@example.com',
            'status' => Status::ACTIVE->value,
        ], (new AdminRequest)->rules());

        expect($validator->fails())->toBeTrue();
    });
});
