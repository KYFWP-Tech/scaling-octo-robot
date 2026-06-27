<?php

namespace App\Models;

use App\Enums\Status;
use App\Traits\HasMedia;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Podcast extends Model
{
    use HasFactory, HasMedia, HasUuids, Sluggable;

    public const MEDIA_STORAGE_PREFIX = 'podcasts';

    protected $fillable = [
        'title',
        'slug',
        'content',
        'cover_image',
        'user_id',
        'status',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'status' => Status::class,
    ];

    /**
     * @return array<string, array<string, string>>
     */
    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'title',
            ],
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function episodes(): HasMany
    {
        return $this->hasMany(Episode::class)->orderBy('episode_number');
    }
}
