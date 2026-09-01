<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TechnologyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'category' => $this->category,
            'icon_url' => $this->icon_url,
            'description' => $this->description,
            'version' => $this->whenPivotLoaded('domain_technologies', function () {
                return $this->pivot->version;
            }),
            'confidence_score' => $this->whenPivotLoaded('domain_technologies', function () {
                return (float) $this->pivot->confidence_score;
            }),
        ];
    }
}
