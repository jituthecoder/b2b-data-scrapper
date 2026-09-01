<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'normalized_name' => $this->normalized_name,
            'description' => $this->description,
            'industry' => $this->industry,
            'employee_count_range' => $this->employee_count_range,
            'founded_year' => $this->founded_year,
            'country' => $this->country,
            'state_region' => $this->state_region,
            'city' => $this->city,
            'address' => $this->address,
            'postal_code' => $this->postal_code,
            'confidence_score' => (float) $this->confidence_score,
            'social_profiles' => $this->whenLoaded('socialProfiles', function () {
                return $this->socialProfiles->map(fn($s) => [
                    'platform' => $s->platform,
                    'url' => $s->profile_url,
                    'username' => $s->username_handle,
                ]);
            }),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
