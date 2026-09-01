<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DomainResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'domain' => $this->domain,
            'normalized_domain' => $this->normalized_domain,
            'scheme' => $this->scheme,
            'www_variant' => $this->www_variant,
            'tld' => $this->tld,
            'status' => $this->status,
            'is_accessible' => $this->is_accessible,
            'http_status' => $this->http_status,
            'final_url' => $this->final_url,
            'canonical_url' => $this->canonical_url,
            'first_discovered_at' => $this->first_discovered_at?->toIso8601String(),
            'last_crawled_at' => $this->last_crawled_at?->toIso8601String(),
            'next_crawl_at' => $this->next_crawl_at?->toIso8601String(),
            'companies' => CompanyResource::collection($this->whenLoaded('companies')),
            'technologies' => TechnologyResource::collection($this->whenLoaded('technologies')),
            'emails' => $this->whenLoaded('emails', function () {
                return $this->emails->map(fn($e) => [
                    'email' => $e->email,
                    'type' => $e->type,
                    'verification_status' => $e->verification_status,
                ]);
            }),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
