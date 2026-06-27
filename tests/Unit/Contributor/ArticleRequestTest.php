<?php

use App\Http\Requests\ArticleRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

describe('ArticleRequest', function () {
    it('passes with valid data', function () {
        $category = $this->createCategory();

        $validator = Validator::make([
            'title' => 'Test Article',
            'content' => 'Article body content.',
            'category_id' => $category->id,
            'is_featured' => true,
        ], (new ArticleRequest)->rules());

        expect($validator->passes())->toBeTrue();
    });

    it('passes with valid media paths', function () {
        Storage::fake('s3');
        Storage::disk('s3')->put('articles/cover-extra.jpg', 'content');

        $category = $this->createCategory();

        $validator = Validator::make([
            'title' => 'Test Article',
            'content' => 'Article body content.',
            'cover_image' => 'https://example.com/cover.jpg',
            'media' => [
                ['type' => 'image', 'path' => 'articles/cover-extra.jpg'],
            ],
            'category_id' => $category->id,
            'is_featured' => true,
        ], (new ArticleRequest)->rules());

        expect($validator->passes())->toBeTrue();
    });

    it('fails when :dataset', function (array $payload) {
        $category = $this->createCategory();

        if (isset($payload['category_id']) && $payload['category_id'] === '00000000-0000-4000-8000-000000000001') {
            $payload['category_id'] = $category->id;
        }

        $validator = Validator::make($payload, (new ArticleRequest)->rules());

        expect($validator->fails())->toBeTrue();
    })->with('invalid_article_request_payloads');

    it('fails when category does not exist', function () {
        $validator = Validator::make([
            'title' => 'Test Article',
            'content' => 'Article body content.',
            'category_id' => '00000000-0000-4000-8000-000000000099',
            'is_featured' => true,
        ], (new ArticleRequest)->rules());

        expect($validator->fails())->toBeTrue();
    });
});
