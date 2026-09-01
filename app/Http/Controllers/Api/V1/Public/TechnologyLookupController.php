<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Domain\DataProcessing\DomainNormalizationService;
use App\Domain\Domains\Models\Domain;
use App\Domain\Technologies\Models\Technology;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\TechnologyResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TechnologyLookupController extends Controller
{
    public function index(Request $request, DomainNormalizationService $normalizer): AnonymousResourceCollection
    {
        if ($request->filled('domain')) {
            $domainInput = $request->input('domain');
            $norm = $normalizer->normalize($domainInput);
            $domain = Domain::where('normalized_domain', $norm['normalized_domain'])->first();

            if ($domain) {
                return TechnologyResource::collection($domain->technologies);
            }

            return TechnologyResource::collection(collect());
        }

        $query = Technology::query();
        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        $perPage = min((int) $request->input('per_page', 20), 100);

        return TechnologyResource::collection($query->paginate($perPage));
    }
}
