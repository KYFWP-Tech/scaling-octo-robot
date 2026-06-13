<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TokenResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $accessToken = $this->accessToken ?? null;

        return $this->accessToken ? [
            'id' => $this->accessToken->tokenable->id,
            'name' => $this->accessToken->name,
            'token' => $this->plainTextToken,
            'token_type' => 'Bearer',
            'expiresAt' => $this->accessToken->expires_at,
            'lastUsed' => $this->accessToken->last_used_at?->format('Y-m-d H:i:s'),
        ] : [
            'id' => $this->tokenable_id,
            'name' => $this->name,
            'token' => $this->token,
            'token_type' => 'Bearer',
            'expiresAt' => $this->expires_at,
            'lastUsed' => $this->last_used_at?->format('Y-m-d H:i:s'),
        ];
    }
}
