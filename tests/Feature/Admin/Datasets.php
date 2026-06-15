<?php

use App\Enums\Role;

dataset('admin_roles', [
    'super-admin' => Role::SuperAdmin,
    'admin' => Role::Admin,
    'editor' => Role::Editor,
]);
