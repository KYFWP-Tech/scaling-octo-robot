<?php

use App\Enums\Status;
use App\Http\Requests\ChangeStatusRequest;
use Illuminate\Support\Facades\Validator;

describe('ChangeStatusRequest', function () {
    it('passes with active status', function () {
        $validator = Validator::make([
            'status' => Status::ACTIVE->value,
        ], (new ChangeStatusRequest)->rules());

        expect($validator->passes())->toBeTrue();
    });

    it('passes with inactive status', function () {
        $validator = Validator::make([
            'status' => Status::INACTIVE->value,
        ], (new ChangeStatusRequest)->rules());

        expect($validator->passes())->toBeTrue();
    });

    it('fails when :dataset', function (array $payload) {
        $validator = Validator::make($payload, (new ChangeStatusRequest)->rules());

        expect($validator->fails())->toBeTrue();
    })->with('invalid_change_status_payloads');
});
