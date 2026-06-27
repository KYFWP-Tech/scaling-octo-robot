<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PodcastResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'content' => $this->content,
            'cover_image' => $this->cover_image ? $this->temporaryMediaUrl($this->cover_image) : null,
            'author' => new UserResource($this->whenLoaded('author')),
            'episodes' => EpisodeResource::collection($this->whenLoaded('episodes')),
            'published_at' => $this->published_at,
            'status' => [
                'value' => $this->status->value,
                'label' => $this->status->label(),
            ],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
