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
