<?php

namespace App\Http\Controllers\Api\V1\Crawler;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CrawlerHeartbeatController extends Controller
{
    public function heartbeat(Request $request): JsonResponse
    {
        $node = $request->attributes->get('crawler_node');
        $now = now();

        $cacheKey = "node_hb_updated:{$node->id}";
        if (!\Illuminate\Support\Facades\Cache::has($cacheKey)) {
            try {
                $node->update(['last_heartbeat_at' => $now]);
                \Illuminate\Support\Facades\Cache::put($cacheKey, true, 30);
            } catch (\Throwable $e) {
                // Ignore DB transient connection timeout on heartbeat
            }
        }

        return response()->json([
            'status' => 'ok',
            'crawler_id' => $node->crawler_id,
            'last_heartbeat_at' => $now->toIso8601String(),
        ]);
    }
}
