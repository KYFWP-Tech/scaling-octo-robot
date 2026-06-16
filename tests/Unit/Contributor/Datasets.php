<?php

dataset('invalid_article_request_payloads', [
    'missing title' => [['content' => 'body', 'category_id' => '00000000-0000-4000-8000-000000000001', 'is_featured' => true]],
    'missing content' => [['title' => 'Title', 'category_id' => '00000000-0000-4000-8000-000000000001', 'is_featured' => true]],
    'missing category_id' => [['title' => 'Title', 'content' => 'body', 'is_featured' => true]],
    'missing is_featured' => [['title' => 'Title', 'content' => 'body', 'category_id' => '00000000-0000-4000-8000-000000000001']],
    'invalid category_id' => [['title' => 'Title', 'content' => 'body', 'category_id' => 'not-a-uuid', 'is_featured' => true]],
    'invalid is_featured' => [['title' => 'Title', 'content' => 'body', 'category_id' => '00000000-0000-4000-8000-000000000001', 'is_featured' => 'yes']],
]);
