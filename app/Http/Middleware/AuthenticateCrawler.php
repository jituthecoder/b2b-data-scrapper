<?php

namespace App\Http\Middleware;

use App\Domain\Crawling\Models\CrawlerNode;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateCrawler
{
    public function handle(Request $request, Closure $next): Response
    {
        $crawlerId = $request->header('X-Crawler-ID') ?: $request->header('X-Crawler-Id');
        $crawlerKey = $request->header('X-Crawler-Key');

        if (!$crawlerId || !$crawlerKey) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Missing X-Crawler-ID or X-Crawler-Key headers.',
            ], 401);
        }

        $keyHash = hash('sha256', $crawlerKey);

        $node = CrawlerNode::where('crawler_id', $crawlerId)
            ->where('api_key_hash', $keyHash)
            ->where('status', 'active')
            ->first();

        if (!$node) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Invalid crawler credentials or node inactive.',
            ], 401);
        }

        $request->attributes->set('crawler_node', $node);

        return $next($request);
    }
}
