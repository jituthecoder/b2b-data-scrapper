<?php

namespace App\Http\Controllers\Api\V1\Crawler;

use App\Domain\Crawling\Models\CrawlerNode;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CrawlerRegistrationController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'hostname' => 'required|string|max:255',
            'version' => 'required|string|max:50',
            'worker_count' => 'nullable|integer|min:1|max:500',
            'capabilities' => 'required|array',
            'capabilities.*' => 'string',
        ]);

        $crawlerId = 'node-' . Str::lower(Str::random(12));
        $rawApiKey = Str::random(40);
        $keyHash = hash('sha256', $rawApiKey);

        $node = CrawlerNode::create([
            'crawler_id' => $crawlerId,
            'api_key_hash' => $keyHash,
            'hostname' => $validated['hostname'],
            'version' => $validated['version'],
            'worker_count' => $validated['worker_count'] ?? 20,
            'status' => 'active',
            'capabilities' => $validated['capabilities'],
            'last_heartbeat_at' => now(),
        ]);

        return response()->json([
            'message' => 'Crawler node registered successfully.',
            'crawler_id' => $node->crawler_id,
            'api_key' => $rawApiKey,
            'worker_count' => $node->worker_count,
            'capabilities' => $node->capabilities,
        ], 201);
    }
}
