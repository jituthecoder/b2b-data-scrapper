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
        $node->update([
            'last_heartbeat_at' => now(),
        ]);

        return response()->json([
            'status' => 'ok',
            'crawler_id' => $node->crawler_id,
            'last_heartbeat_at' => $node->last_heartbeat_at->toIso8601String(),
        ]);
    }
}
