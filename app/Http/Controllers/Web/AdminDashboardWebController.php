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
        $stats = \Illuminate\Support\Facades\Cache::remember('admin_dashboard_stats', 86400, function () {
            // 1. Fast table count estimates in PostgreSQL (0.01ms execution!)
            $isPgsql = \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'pgsql';

            $getFastCount = function (string $tableName, ?string $whereColumn = null, mixed $whereValue = null) use ($isPgsql) {
                if ($whereColumn !== null) {
                    return \Illuminate\Support\Facades\DB::table($tableName)->where($whereColumn, $whereValue)->count();
                }
                if ($isPgsql) {
                    $res = \Illuminate\Support\Facades\DB::selectOne("SELECT reltuples::bigint AS count FROM pg_class WHERE relname = ?", [$tableName]);
                    if ($res && $res->count > 0) {
                        return (int) $res->count;
                    }
                }
                return \Illuminate\Support\Facades\DB::table($tableName)->count();
            };

            // Grouped CrawlJob status counts in 1 single query
            $jobCounts = CrawlJob::select('status', \Illuminate\Support\Facades\DB::raw('COUNT(*) as total'))
                ->groupBy('status')
                ->pluck('total', 'status');

            return [
                'domains' => [
                    'total' => $getFastCount('domains'),
                    'accessible' => $getFastCount('domains', 'is_accessible', true),
                    'completed' => $getFastCount('domains', 'crawl_status', 'completed'),
                ],
                'crawlers' => [
                    'total' => CrawlerNode::count(),
                    'active_count' => CrawlerNode::where('status', 'active')->where('last_heartbeat_at', '>=', now()->subMinutes(2))->count(),
                    'stopped_count' => max(0, CrawlerNode::count() - CrawlerNode::where('status', 'active')->where('last_heartbeat_at', '>=', now()->subMinutes(2))->count()),
                    'total_capacity' => (int) CrawlerNode::sum('worker_count'),
                ],
                'jobs' => [
                    'pending' => (int) ($jobCounts->get('pending') ?? 0),
                    'claimed' => (int) ($jobCounts->get('claimed') ?? 0),
                    'completed' => (int) ($jobCounts->get('completed') ?? 0),
                ],
                'entities' => [
                    'companies' => $getFastCount('companies'),
                    'contacts' => $getFastCount('contacts'),
                    'emails' => $getFastCount('emails'),
                    'technologies' => $getFastCount('technologies'),
                ],
            ];
        });

        $recentDomains = Domain::orderBy('id', 'desc')->limit(8)->get();
        $recentJobs = CrawlJob::with('domain')->orderBy('created_at', 'desc')->limit(8)->get();

        return view('admin.dashboard', compact('stats', 'recentDomains', 'recentJobs'));
    }

    public function domains(Request $request): View
    {
        $query = Domain::with(['companies', 'technologies', 'emails']);

        $filter = $request->input('filter');
        $search = $request->input('search');

        if ($request->filled('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('domain', 'LIKE', "%{$search}%")
                  ->orWhere('normalized_domain', 'LIKE', "%{$search}%");
            });
        }

        if ($filter === 'with_emails') {
            $query->has('emails');
        } elseif ($filter === 'accessible') {
            $query->where('is_accessible', true);
        } elseif ($filter === 'completed') {
            $query->where('crawl_status', 'completed');
        } elseif ($filter === 'in_progress') {
            $query->where('crawl_status', 'in_progress');
        }

        // Cache total count dynamically per filter/search criteria for 60s
        $cacheKey = 'domains_count_' . md5(($filter ?? '') . '_' . ($search ?? ''));
        $totalCount = \Illuminate\Support\Facades\Cache::remember($cacheKey, 60, function () use ($query) {
            return (clone $query)->count();
        });

        // Restore full numbered pagination using cached filtered total count
        $page = \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPage();
        $perPage = 15;

        $items = (clone $query)->orderBy('id', 'desc')
            ->forPage($page, $perPage)
            ->get();

        $domains = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $totalCount,
            $perPage,
            $page,
            ['path' => \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPath(), 'query' => $request->query()]
        );

        return view('admin.domains', compact('domains', 'totalCount'));
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
        $nodeIds = $nodes->pluck('crawler_id')->filter()->toArray();

        // 1. Grouped SQL Aggregation across all nodes in 1 SINGLE QUERY
        $statsMap = \Illuminate\Support\Facades\Cache::remember('crawler_nodes_bulk_stats', 30, function () use ($todayStart) {
            $nowStr = now()->toDateTimeString();
            $isPgsql = \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'pgsql';

            if ($isPgsql) {
                return CrawlJob::select(
                        'crawler_id',
                        \Illuminate\Support\Facades\DB::raw("COUNT(*) FILTER (WHERE status = 'claimed' AND lease_expires_at >= '{$nowStr}') as active_jobs_count"),
                        \Illuminate\Support\Facades\DB::raw("COUNT(*) FILTER (WHERE status = 'completed' AND (completed_at >= '{$todayStart}' OR updated_at >= '{$todayStart}')) as completed_today_count"),
                        \Illuminate\Support\Facades\DB::raw("COUNT(*) FILTER (WHERE status = 'failed' AND (failed_at >= '{$todayStart}' OR updated_at >= '{$todayStart}')) as failed_today_count"),
                        \Illuminate\Support\Facades\DB::raw("COUNT(*) FILTER (WHERE status = 'completed') as total_completed_count")
                    )
                    ->whereNotNull('crawler_id')
                    ->groupBy('crawler_id')
                    ->get()
                    ->keyBy('crawler_id');
            }

            return CrawlJob::select('crawler_id')
                ->selectRaw("SUM(CASE WHEN status = 'claimed' AND lease_expires_at >= ? THEN 1 ELSE 0 END) as active_jobs_count", [$nowStr])
                ->selectRaw("SUM(CASE WHEN status = 'completed' AND (completed_at >= ? OR updated_at >= ?) THEN 1 ELSE 0 END) as completed_today_count", [$todayStart, $todayStart])
                ->selectRaw("SUM(CASE WHEN status = 'failed' AND (failed_at >= ? OR updated_at >= ?) THEN 1 ELSE 0 END) as failed_today_count", [$todayStart, $todayStart])
                ->selectRaw("SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as total_completed_count")
                ->whereNotNull('crawler_id')
                ->groupBy('crawler_id')
                ->get()
                ->keyBy('crawler_id');
        });

        // 2. Fetch active domains for displayed nodes in 1 BATCHED QUERY
        $activeDomainsGrouped = !empty($nodeIds) ? CrawlJob::with('domain')
            ->whereIn('crawler_id', $nodeIds)
            ->where('status', 'claimed')
            ->where('lease_expires_at', '>=', now())
            ->get()
            ->groupBy('crawler_id') : collect();

        foreach ($nodes as $node) {
            $nodeStats = $statsMap->get($node->crawler_id);
            $node->active_jobs_count = (int) ($nodeStats->active_jobs_count ?? 0);
            $node->completed_today_count = (int) ($nodeStats->completed_today_count ?? 0);
            $node->failed_today_count = (int) ($nodeStats->failed_today_count ?? 0);
            $node->total_completed_count = (int) ($nodeStats->total_completed_count ?? 0);
            $node->active_domains = $activeDomainsGrouped->get($node->crawler_id, collect())->take(30);
        }

        return view('admin.crawlers', compact('nodes'));
    }

    public function jobs(Request $request): View
    {
        $query = CrawlJob::with(['domain', 'attempts']);

        $status = $request->input('status');
        $crawlerId = $request->input('crawler_id');

        if ($request->filled('status')) {
            $query->where('status', $status);
        }

        if ($request->filled('crawler_id')) {
            $query->where('crawler_id', $crawlerId);
        }

        $cacheKey = "jobs_count_" . md5(($status ?? '') . '_' . ($crawlerId ?? ''));
        $totalCount = \Illuminate\Support\Facades\Cache::remember($cacheKey, 30, fn() => (clone $query)->count());

        $jobs = $query->orderBy('created_at', 'desc')->simplePaginate(15)->withQueryString();

        return view('admin.jobs', compact('jobs', 'totalCount'));
    }
}
