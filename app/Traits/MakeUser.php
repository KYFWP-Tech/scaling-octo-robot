<?php

namespace App\Traits;

use App\Enums\Status;
use App\Models\User;

trait MakeUser
{
    public function makeUser($payload = []): User
    {
        $user = $this->user ?? new User;
        $user['name'] = $this->name;
        $user['email'] = $this->email;
        $user['profile_id'] = $this->id;
        $user['profile_type'] = $this::class;
        $user['status'] = Status::INACTIVE->value;
        $user->save();

        return $user;
    }
}
