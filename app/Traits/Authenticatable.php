<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Casts\Attribute;

trait Authenticatable
{
    /**
     * Get the Admin user's name.
     */
    protected function status(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->user->status
        );
    }

    public function user(): MorphOne
    {
        return $this->morphOne(User::class, 'profile');
    }

}
