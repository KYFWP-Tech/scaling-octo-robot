<?php

use App\Enums\Status;

dataset('invalid_admin_request_payloads', [
    'missing name' => [['email' => 'a@b.com', 'status' => Status::ACTIVE->value]],
    'missing email' => [['name' => 'Test', 'status' => Status::ACTIVE->value]],
    'missing status' => [['name' => 'Test', 'email' => 'a@b.com']],
    'invalid email' => [['name' => 'Test', 'email' => 'not-email', 'status' => Status::ACTIVE->value]],
    'invalid status' => [['name' => 'Test', 'email' => 'a@b.com', 'status' => 99]],
]);

dataset('invalid_assign_role_payloads', [
    'missing role' => [[]],
    'invalid enum' => [['role' => 'invalid-role']],
]);

dataset('invalid_category_request_payloads', [
    'missing name' => [['description' => 'desc', 'status' => Status::ACTIVE->value]],
    'missing status' => [['name' => 'Category', 'description' => 'desc']],
    'invalid status' => [['name' => 'Category', 'description' => 'desc', 'status' => 99]],
]);

dataset('invalid_change_status_payloads', [
    'missing status' => [[]],
    'invalid status' => [['status' => 99]],
]);
