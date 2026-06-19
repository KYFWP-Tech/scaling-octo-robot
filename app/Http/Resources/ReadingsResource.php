<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReadingsResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'date' => $this->resource['date'],
            'season' => $this->resource['season'],
            'celebration' => $this->resource['celebration'],
            'readings' => $this->resource['readings'],
        ];
    }
}
