<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class EpisodeRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'episode_number' => ['sometimes', 'integer', 'min:1'],
            'file' => [
                'sometimes',
                'required',
                'string',
                'regex:/\.(mp3|wav|m4a|ogg|aac|webm)$/i',
            ],
        ];
    }
}
