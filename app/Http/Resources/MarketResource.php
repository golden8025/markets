<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MarketResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            
            'id' => $this->id,
            'group_id' => $this->group_id,
            'name' => $this->name,
            'key' => $this->key,
            'type' => $this->type,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,

        ];
    }
}
