<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Domain\Companies\Models\Company;
use App\Domain\Contacts\Models\Contact;
use App\Domain\Crawling\Models\CrawlerNode;
use App\Domain\Crawling\Models\CrawlJob;
use App\Domain\Domains\Models\Domain;
use App\Domain\Emails\Models\Email;
use App\Domain\Pages\Models\Page;
use App\Domain\Phones\Models\Phone;
use App\Domain\Technologies\Models\Technology;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class AdminDashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $activeThreshold = now()->subMinutes(10);
        $activeNodes = CrawlerNode::where('status', 'active')
            ->where('last_heartbeat_at', '>=', $activeThreshold)
            ->get();

        return response()->json([
            'domains' => [
                'total' => Domain::count(),
                'accessible' => Domain::where('is_accessible', true)->count(),
                'inaccessible' => Domain::where('is_accessible', false)->count(),
                'pending_crawls' => Domain::where('crawl_status', 'pending')->count(),
                'completed_crawls' => Domain::where('crawl_status', 'completed')->count(),
                'failed_crawls' => Domain::where('crawl_status', 'failed')->count(),
            ],
            'entities' => [
                'companies' => Company::count(),
                'contacts' => Contact::count(),
                'emails' => Email::count(),
                'verified_emails' => Email::where('verification_status', 'verified')->count(),
                'phones' => Phone::count(),
                'technologies' => Technology::count(),
                'pages' => Page::count(),
            ],
            'crawlers' => [
                'active_nodes_count' => $activeNodes->count(),
                'total_worker_capacity' => $activeNodes->sum('worker_count'),
                'active_nodes' => $activeNodes->map(fn($node) => [
                    'crawler_id' => $node->crawler_id,
                    'hostname' => $node->hostname,
                    'version' => $node->version,
                    'worker_count' => $node->worker_count,
                    'capabilities' => $node->capabilities,
                    'last_heartbeat_at' => $node->last_heartbeat_at?->toIso8601String(),
                ]),
            ],
            'jobs' => [
                'total_jobs' => CrawlJob::count(),
                'pending' => CrawlJob::where('status', 'pending')->count(),
                'claimed' => CrawlJob::where('status', 'claimed')->count(),
                'completed' => CrawlJob::where('status', 'completed')->count(),
                'failed' => CrawlJob::where('status', 'failed')->count(),
            ],
        ]);
    }
}
