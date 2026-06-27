<?php

namespace App\Models;

use App\Enums\Status;
use App\Traits\HasMedia;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Article extends Model
{
    use HasMedia, HasUuids, HasFactory, Sluggable;

    public const MEDIA_STORAGE_PREFIX = 'articles';

    protected $fillable = [
        'title',
        'content',
        'slug',
        'author_id',
        'author_type',
        'status',
        'cover_image',
        'media',
        'category_id',
        'is_featured',
        'published_at',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'published_at' => 'datetime',
        'media' => 'array',
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

    public function author(): MorphTo
    {
        return $this->morphTo('author');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
