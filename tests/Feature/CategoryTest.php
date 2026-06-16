<?php

use App\Models\Category;

describe('Category', function () {
    describe('index', function () {
        it('returns only active categories', function () {
            $active = Category::factory()->create(['name' => 'Active Category']);
            Category::factory()->inactive()->create(['name' => 'Inactive Category']);

            $this->getJson(route('categories.index'))
                ->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.id', $active->id);
        });
    });
});
