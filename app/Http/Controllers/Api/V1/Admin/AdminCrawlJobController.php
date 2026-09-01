<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Domain\Crawling\Models\CrawlJob;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminCrawlJobController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = CrawlJob::with(['domain', 'attempts']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('job_type')) {
            $query->where('job_type', $request->input('job_type'));
        }

        if ($request->filled('crawler_id')) {
            $query->where('crawler_id', $request->input('crawler_id'));
        }

        $perPage = min((int) $request->input('per_page', 20), 100);

        return response()->json($query->orderBy('priority', 'desc')->orderBy('created_at', 'desc')->paginate($perPage));
    }
}
