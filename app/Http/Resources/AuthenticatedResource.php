<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthenticatedResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => $this->profile_type,
            'profile' => match ($this->profile_type) {
                'admin' => new AdminResource($this->profile),
                'contributor' => new ContributorResource($this->profile),
                'reader' => new ReaderResource($this->profile),
            },
        ];
    }
}
