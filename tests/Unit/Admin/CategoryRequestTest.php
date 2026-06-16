<?php

use App\Enums\Status;
use App\Http\Requests\CategoryRequest;
use Illuminate\Support\Facades\Validator;

beforeEach(function () {
    $this->seedRolesAndPermissions();
});

describe('CategoryRequest', function () {
    it('passes with valid data', function () {
        $validator = Validator::make([
            'name' => 'Theology',
            'description' => 'Category description',
            'icon' => 'book',
            'status' => Status::ACTIVE->value,
        ], (new CategoryRequest)->rules());

        expect($validator->passes())->toBeTrue();
    });

    it('fails when :dataset', function (array $payload) {
        $validator = Validator::make($payload, (new CategoryRequest)->rules());

        expect($validator->fails())->toBeTrue();
    })->with('invalid_category_request_payloads');
});
