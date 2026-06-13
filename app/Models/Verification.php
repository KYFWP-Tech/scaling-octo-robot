<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Verification extends Model
{
    protected $table = 'verifications';

    protected $primaryKey = 'id';

    protected $fillable = [
        'email',
        'code',
        'expires_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'email', 'email');
    }

    /**
     * Check if the invitation has expired.
     */
    public function hasExpired(): bool
    {
        return Carbon::parse($this->expires_at)->isPast();
    }
}