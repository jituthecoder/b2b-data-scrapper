<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Domain\Crawling\Models\CrawlerNode;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminCrawlerNodeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = CrawlerNode::query();

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $perPage = min((int) $request->input('per_page', 20), 100);

        return response()->json($query->orderBy('last_heartbeat_at', 'desc')->paginate($perPage));
    }
}
