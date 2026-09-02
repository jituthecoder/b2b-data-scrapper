<?php

namespace App\Http\Controllers\Web;

use App\Domain\Companies\Models\Company;
use App\Domain\Contacts\Models\Contact;
use App\Domain\Crawling\Models\CrawlerNode;
use App\Domain\Crawling\Models\CrawlJob;
use App\Domain\DataProcessing\DomainNormalizationService;
use App\Domain\Domains\Models\Domain;
use App\Domain\Emails\Models\Email;
use App\Domain\Technologies\Models\Technology;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminDashboardWebController extends Controller
{
    public function index(): View
    {
        $stats = \Illuminate\Support\Facades\Cache::remember('admin_dashboard_stats', 60, function () {
            return [
                'domains' => [
                    'total' => Domain::count(),
                    'accessible' => Domain::where('is_accessible', true)->count(),
                    'completed' => Domain::where('crawl_status', 'completed')->count(),
                ],
                'crawlers' => [
                    'total' => CrawlerNode::count(),
                    'active_count' => CrawlerNode::where('status', 'active')->where('last_heartbeat_at', '>=', now()->subMinutes(2))->count(),
                    'stopped_count' => CrawlerNode::where(function ($q) {
                        $q->where('status', '!=', 'active')
                          ->orWhereNull('last_heartbeat_at')
                          ->orWhere('last_heartbeat_at', '<', now()->subMinutes(2));
                    })->count(),
                    'total_capacity' => CrawlerNode::sum('worker_count'),
                ],
                'jobs' => [
                    'pending' => CrawlJob::where('status', 'pending')->count(),
                    'claimed' => CrawlJob::where('status', 'claimed')->count(),
                    'completed' => CrawlJob::where('status', 'completed')->count(),
                ],
                'entities' => [
                    'companies' => Company::count(),
                    'contacts' => Contact::count(),
                    'emails' => Email::count(),
                    'technologies' => Technology::count(),
                ],
            ];
        });

        $recentDomains = Domain::orderBy('id', 'desc')->limit(8)->get();
        $recentJobs = CrawlJob::with('domain')->orderBy('created_at', 'desc')->limit(8)->get();

        return view('admin.dashboard', compact('stats', 'recentDomains', 'recentJobs'));
    }

    public function domains(Request $request): View
    {
        $query = Domain::with(['companies', 'technologies', 'emails'])->withCount('emails');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('domain', 'LIKE', "%{$search}%")
                  ->orWhere('normalized_domain', 'LIKE', "%{$search}%");
            });
        }

        if ($request->input('filter') === 'with_emails') {
            $query->has('emails');
        } elseif ($request->input('filter') === 'accessible') {
            $query->where('is_accessible', true);
        } elseif ($request->input('filter') === 'completed') {
            $query->where('crawl_status', 'completed');
        } elseif ($request->input('filter') === 'in_progress') {
            $query->where('crawl_status', 'in_progress');
        }

        $domains = $query->orderBy('id', 'desc')->paginate(15)->withQueryString();

        return view('admin.domains', compact('domains'));
    }

    public function storeDomain(Request $request, DomainNormalizationService $normalizer): RedirectResponse
    {
        $request->validate([
            'domains_input' => 'required|string',
        ]);

        $input = $request->input('domains_input');
        $lines = explode("\n", str_replace("\r", "", $input));
        $rawDomains = [];

        foreach ($lines as $line) {
            $parts = explode(',', $line);
            foreach ($parts as $p) {
                $trimmed = trim($p);
                if (!empty($trimmed)) {
                    $rawDomains[] = $trimmed;
                }
            }
        }

        $rawDomains = array_unique($rawDomains);
        $count = 0;

        foreach ($rawDomains as $rawStr) {
            try {
                $norm = $normalizer->normalize($rawStr);
                if (empty($norm['normalized_domain'])) continue;

                $domain = Domain::firstOrCreate(
                    ['normalized_domain' => $norm['normalized_domain']],
                    [
                        'domain' => $norm['domain'],
                        'scheme' => $norm['scheme'],
                        'www_variant' => $norm['www_variant'],
                        'tld' => $norm['tld'],
                        'status' => 'active',
                        'crawl_status' => 'pending',
                        'priority' => 1000,
                        'first_discovered_at' => now(),
                    ]
                );

                // Queue Stage 1 Reachability Check with Priority 1000
                CrawlJob::firstOrCreate(
                    [
                        'domain_id' => $domain->id,
                        'job_type' => 'reachability',
                        'status' => 'pending',
                    ],
                    [
                        'id' => (string) Str::uuid(),
                        'priority' => 1000,
                        'attempt_count' => 0,
                        'max_attempts' => 3,
                    ]
                );

                $count++;
            } catch (\Throwable $e) {
                // Skip invalid domain format
            }
        }

        return redirect()->back()->with('success', "Successfully registered {$count} custom domains! Stage 1 crawl jobs queued with priority 1000.");
    }

    public function crawlers(): View
    {
        $nodes = CrawlerNode::orderBy('updated_at', 'desc')->paginate(15);
        $todayStart = now()->startOfDay();

        foreach ($nodes as $node) {
            $cachedStats = \Illuminate\Support\Facades\Cache::remember("crawler_node_stats_{$node->crawler_id}", 30, function () use ($node, $todayStart) {
                return [
                    'active_jobs_count' => CrawlJob::where('crawler_id', $node->crawler_id)
                        ->where('status', 'claimed')
                        ->where('lease_expires_at', '>=', now())
                        ->count(),
                    'completed_today_count' => CrawlJob::where('crawler_id', $node->crawler_id)
                        ->where('status', 'completed')
                        ->where(function ($q) use ($todayStart) {
                            $q->where('completed_at', '>=', $todayStart)
                              ->orWhere('updated_at', '>=', $todayStart);
                        })->count(),
                    'failed_today_count' => CrawlJob::where('crawler_id', $node->crawler_id)
                        ->where('status', 'failed')
                        ->where(function ($q) use ($todayStart) {
                            $q->where('failed_at', '>=', $todayStart)
                              ->orWhere('updated_at', '>=', $todayStart);
                        })->count(),
                    'total_completed_count' => CrawlJob::where('crawler_id', $node->crawler_id)->where('status', 'completed')->count(),
                ];
            });

            $node->active_jobs_count = $cachedStats['active_jobs_count'];
            $node->completed_today_count = $cachedStats['completed_today_count'];
            $node->failed_today_count = $cachedStats['failed_today_count'];
            $node->total_completed_count = $cachedStats['total_completed_count'];
            
            // Get the list of 30 active unexpired target domains currently being crawled
            $node->active_domains = CrawlJob::with('domain')
                ->where('crawler_id', $node->crawler_id)
                ->where('status', 'claimed')
                ->where('lease_expires_at', '>=', now())
                ->limit(30)
                ->get();
        }

        return view('admin.crawlers', compact('nodes'));
    }

    public function jobs(Request $request): View
    {
        $query = CrawlJob::with(['domain', 'attempts']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('crawler_id')) {
            $query->where('crawler_id', $request->input('crawler_id'));
        }

        $jobs = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        return view('admin.jobs', compact('jobs'));
    }
}
