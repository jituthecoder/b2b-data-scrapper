<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Domain\Domains\Models\Domain;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminDomainController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Domain::with(['companies', 'technologies']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('crawl_status')) {
            $query->where('crawl_status', $request->input('crawl_status'));
        }

        if ($request->filled('tld')) {
            $query->where('tld', $request->input('tld'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('domain', 'LIKE', "%{$search}%");
        }

        $perPage = min((int) $request->input('per_page', 20), 100);

        return response()->json($query->paginate($perPage));
    }
}
