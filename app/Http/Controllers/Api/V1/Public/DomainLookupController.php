<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Domain\DataProcessing\DomainNormalizationService;
use App\Domain\Domains\Models\Domain;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\DomainResource;
use Illuminate\Http\JsonResponse;

class DomainLookupController extends Controller
{
    public function show(string $domainInput, DomainNormalizationService $normalizer): DomainResource|JsonResponse
    {
        $norm = $normalizer->normalize($domainInput);
        $normalizedDomain = $norm['normalized_domain'];

        $domain = Domain::with(['companies', 'technologies', 'emails'])
            ->where('normalized_domain', $normalizedDomain)
            ->first();

        if (!$domain) {
            return response()->json([
                'error' => 'Not Found',
                'message' => "No domain intelligence found for '{$domainInput}'.",
            ], 404);
        }

        return new DomainResource($domain);
    }
}
