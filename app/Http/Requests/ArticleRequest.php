<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ArticleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'cover_image' => ['sometimes', 'required', 'string'],
            'media' => ['sometimes', 'required', 'array'],
            'media.*.type' => ['required', 'string', 'max:255', Rule::in(['image', 'video'])],
            'media.*.path' => ['required', 'string'],
            'category_id' => ['required', 'uuid', 'exists:categories,id'],
            'is_featured' => ['required', 'boolean'],
        ];
    }
}
