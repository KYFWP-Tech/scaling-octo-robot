<?php

use App\Enums\Status;
use App\Http\Requests\Admin\VerifyAdminRequest;
use App\Models\Verification;
use Illuminate\Support\Facades\Validator;

beforeEach(function () {
    $this->seedRolesAndPermissions();
});

describe('VerifyAdminRequest', function () {
    it('passes with valid password and token', function () {
        Verification::create([
            'email' => 'invite@example.com',
            'code' => 'valid-token-code',
            'expires_at' => now()->addDay(),
        ]);

        $validator = Validator::make([
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'token' => 'valid-token-code',
        ], (new VerifyAdminRequest)->rules());

        expect($validator->passes())->toBeTrue();
    });

    it('fails when password is missing', function () {
        $validator = Validator::make([
            'token' => 'some-token',
        ], (new VerifyAdminRequest)->rules());

        expect($validator->fails())->toBeTrue();
    });

    it('fails when password confirmation does not match', function () {
        Verification::create([
            'email' => 'invite@example.com',
            'code' => 'token-abc',
            'expires_at' => now()->addDay(),
        ]);

        $validator = Validator::make([
            'password' => 'password123',
            'password_confirmation' => 'different',
            'token' => 'token-abc',
        ], (new VerifyAdminRequest)->rules());

        expect($validator->fails())->toBeTrue();
    });

    it('fails when token does not exist', function () {
        $validator = Validator::make([
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'token' => 'missing-token',
        ], (new VerifyAdminRequest)->rules());

        expect($validator->fails())->toBeTrue();
    });
});
