<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReflectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array 
    {
        return [
            'date' => [
                'required',
                'date',
                'date_format:Y-m-d',
                Rule::unique('reflections', 'date')->ignore($this->route('reflection')),
            ],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'file' => [
                'sometimes',
                'required',
                'string',
                'regex:/\.(mp3|wav|m4a|ogg|aac|webm)$/i',
            ],
        ];
    }
}
