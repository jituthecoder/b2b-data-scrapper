<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Domain\Companies\Models\Company;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\CompanyResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CompanySearchController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Company::with(['socialProfiles']);

        if ($request->filled('industry')) {
            $query->where('industry', 'LIKE', '%' . $request->input('industry') . '%');
        }

        if ($request->filled('country')) {
            $query->where('country', $request->input('country'));
        }

        if ($request->filled('name')) {
            $query->where('name', 'LIKE', '%' . $request->input('name') . '%');
        }

        if ($request->filled('technology')) {
            $techSlug = $request->input('technology');
            $query->whereHas('domains.technologies', function ($q) use ($techSlug) {
                $q->where('slug', $techSlug);
            });
        }

        $perPage = min((int) $request->input('per_page', 20), 100);

        return CompanyResource::collection($query->paginate($perPage));
    }
}
