<?php

use App\Enums\Role;
use App\Enums\Status;
use App\Http\Requests\UpdateAuthenticatedUserRequest;
use Illuminate\Support\Facades\Validator;

beforeEach(function () {
    $this->seedRolesAndPermissions();
});

describe('UpdateAuthenticatedUserRequest', function () {
    it('passes with an empty payload', function () {
        ['admin' => $admin, 'user' => $user] = $this->createAdminWithRole(Role::Editor);

        $request = $this->createFormRequest(
            UpdateAuthenticatedUserRequest::class,
            'admins.update',
            'PUT',
            ['admin' => $admin],
        );
        $request->setUserResolver(fn () => $user);

        $validator = Validator::make([], $request->rules());

        expect($validator->passes())->toBeTrue();
    });

    it('passes with partial valid updates', function () {
        ['admin' => $admin, 'user' => $user] = $this->createAdminWithRole(Role::Editor);

        $request = $this->createFormRequest(
            UpdateAuthenticatedUserRequest::class,
            'admins.update',
            'PUT',
            ['admin' => $admin],
        );
        $request->setUserResolver(fn () => $user);

        $validator = Validator::make([
            'name' => 'Updated Name',
            'status' => Status::INACTIVE->value,
        ], $request->rules());

        expect($validator->passes())->toBeTrue();
    });

    it('fails when email is taken by another user', function () {
        $this->createAdminWithRole(Role::Editor, ['email' => 'taken@example.com']);
        ['admin' => $admin, 'user' => $user] = $this->createAdminWithRole(Role::Editor, ['email' => 'actor@example.com']);

        $request = $this->createFormRequest(
            UpdateAuthenticatedUserRequest::class,
            'admins.update',
            'PUT',
            ['admin' => $admin],
        );
        $request->setUserResolver(fn () => $user);

        $validator = Validator::make([
            'email' => 'taken@example.com',
        ], $request->rules());

        expect($validator->fails())->toBeTrue();
    });

    it('allows keeping the same email', function () {
        ['admin' => $admin, 'user' => $user] = $this->createAdminWithRole(Role::Editor, ['email' => 'same@example.com']);

        $request = $this->createFormRequest(
            UpdateAuthenticatedUserRequest::class,
            'admins.update',
            'PUT',
            ['admin' => $admin],
        );
        $request->setUserResolver(fn () => $user);

        $validator = Validator::make([
            'email' => 'same@example.com',
        ], $request->rules());

        expect($validator->passes())->toBeTrue();
    });
});
