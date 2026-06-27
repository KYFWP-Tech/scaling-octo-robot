<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EpisodeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'podcast_id' => $this->podcast_id,
            'title' => $this->title,
            'slug' => $this->slug,
            'content' => $this->content,
            'file' => $this->file ? $this->temporaryMediaUrl($this->file) : null,
            'episode_number' => $this->episode_number,
            'podcast' => new PodcastResource($this->whenLoaded('podcast')),
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
