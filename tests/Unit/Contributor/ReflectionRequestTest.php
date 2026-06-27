<?php

use App\Http\Requests\ReflectionRequest;
use App\Models\Reflection;
use Illuminate\Support\Facades\Validator;

describe('ReflectionRequest', function () {
    it('passes with valid data', function () {
        $request = $this->createFormRequest(
            ReflectionRequest::class,
            'contributors.reflections.store',
            'POST',
        );

        $validator = Validator::make($this->validReflectionPayload(), $request->rules());

        expect($validator->passes())->toBeTrue();
    });

    it('passes with valid file path', function () {
        $request = $this->createFormRequest(
            ReflectionRequest::class,
            'contributors.reflections.store',
            'POST',
        );

        $validator = Validator::make($this->validReflectionPayload([
            'file' => 'reflections/test.mp3',
        ]), $request->rules());

        expect($validator->passes())->toBeTrue();
    });

    it('fails when file has invalid extension', function () {
        $request = $this->createFormRequest(
            ReflectionRequest::class,
            'contributors.reflections.store',
            'POST',
        );

        $validator = Validator::make($this->validReflectionPayload([
            'file' => 'reflections/test.jpg',
        ]), $request->rules());

        expect($validator->fails())->toBeTrue();
    });

    it('fails when file key is present but empty', function () {
        $request = $this->createFormRequest(
            ReflectionRequest::class,
            'contributors.reflections.store',
            'POST',
        );

        $validator = Validator::make($this->validReflectionPayload([
            'file' => '',
        ]), $request->rules());

        expect($validator->fails())->toBeTrue();
    });

    it('fails when :dataset', function (array $payload) {
        $request = $this->createFormRequest(
            ReflectionRequest::class,
            'contributors.reflections.store',
            'POST',
        );

        $validator = Validator::make($payload, $request->rules());

        expect($validator->fails())->toBeTrue();
    })->with('invalid_reflection_request_payloads');

    it('fails when date is already taken', function () {
        Reflection::factory()->create(['date' => '2026-06-17']);

        $request = $this->createFormRequest(
            ReflectionRequest::class,
            'contributors.reflections.store',
            'POST',
        );

        $validator = Validator::make($this->validReflectionPayload(), $request->rules());

        expect($validator->fails())->toBeTrue();
    });

    it('passes when updating with the same date', function () {
        $reflection = Reflection::factory()->create(['date' => '2026-06-17']);

        $request = $this->createFormRequest(
            ReflectionRequest::class,
            'contributors.reflections.update',
            'PUT',
            ['reflection' => $reflection],
        );

        $validator = Validator::make($this->validReflectionPayload([
            'date' => '2026-06-17',
        ]), $request->rules());

        expect($validator->passes())->toBeTrue();
    });
});
