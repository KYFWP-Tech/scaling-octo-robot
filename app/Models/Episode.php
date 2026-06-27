<?php

namespace App\Models;

use App\Enums\Status;
use App\Traits\HasMedia;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Episode extends Model
{
    use HasFactory, HasMedia, HasUuids, Sluggable;

    public const MEDIA_STORAGE_PREFIX = 'podcasts';

    protected $fillable = [
        'podcast_id',
        'title',
        'content',
        'file',
        'episode_number',
        'status',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'status' => Status::class,
    ];

    /**
     * Return the sluggable configuration array for this model.
     *
     * @return array
     */
    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'title'
            ]
        ];
    }

    public function podcast(): BelongsTo
    {
        return $this->belongsTo(Podcast::class);
    }
}
