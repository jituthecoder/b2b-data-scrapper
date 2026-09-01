<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContactResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'job_title' => $this->job_title,
            'department' => $this->department,
            'seniority' => $this->seniority,
            'company' => new CompanyResource($this->whenLoaded('company')),
            'emails' => $this->whenLoaded('emails', function () {
                return $this->emails->map(fn($e) => [
                    'email' => $e->email,
                    'type' => $e->type,
                    'is_primary' => (bool) $e->pivot->is_primary,
                ]);
            }),
            'phones' => $this->whenLoaded('phones', function () {
                return $this->phones->map(fn($p) => [
                    'phone_number' => $p->phone_number,
                    'type' => $p->type,
                ]);
            }),
            'confidence_score' => (float) $this->confidence_score,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
