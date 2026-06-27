<?php

namespace App\Models;

use App\Traits\HasMedia;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reflection extends Model
{
    use HasFactory, HasMedia, HasUuids;

    public const MEDIA_STORAGE_PREFIX = 'reflections';

    protected $fillable = [
        'date',
        'title',
        'content',
        'file',
        'author_id',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function author(): BelongsTo
    { 
        return $this->belongsTo(User::class, 'author_id');
    }
}
