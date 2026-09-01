<?php

namespace App\Http\Controllers\Web;

use App\Domain\Crawling\Models\CrawlJob;
use App\Domain\Domains\Models\Domain;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminDomainDetailWebController extends Controller
{
    public function show(int $id): View
    {
        $domain = Domain::with([
            'companies',
            'technologies',
            'emails',
            'socialProfiles',
            'pages',
            'crawlJobs.attempts',
        ])->findOrFail($id);

        // Build chronological timeline of stage execution tasks
        $timeline = [];

        // 1. Initial Discovery Event
        if ($domain->created_at) {
            $timeline[] = [
                'stage' => 'Registration',
                'title' => 'Domain Registered',
                'description' => "Domain {$domain->domain} registered into database system.",
                'status' => 'success',
                'timestamp' => $domain->created_at,
                'icon' => 'plus',
            ];
        }

        // 2. Process Crawl Jobs & Attempts
        foreach ($domain->crawlJobs as $job) {
            $stageName = match ($job->job_type) {
                'reachability' => 'Stage 1: Reachability Check',
                'homepage' => 'Stage 2: Homepage & Data Scrape',
                'subpage' => 'Stage 3: Sub-Page Deep Scrape',
                default => 'Crawl Task: ' . ucfirst($job->job_type),
            };

            $statusClass = match ($job->status) {
                'completed' => 'success',
                'claimed', 'in_progress' => 'info',
                'failed' => 'danger',
                default => 'warning',
            };

            $timeline[] = [
                'stage' => $stageName,
                'title' => "{$stageName} (" . strtoupper($job->status) . ")",
                'description' => "Job ID: " . substr($job->id, 0, 12) . " | Worker: " . ($job->crawler_id ?? 'Unassigned') . " | Priority: {$job->priority}",
                'status' => $statusClass,
                'timestamp' => $job->created_at,
                'icon' => 'cog',
            ];

            foreach ($job->attempts as $attempt) {
                $timeline[] = [
                    'stage' => "Attempt #{$attempt->attempt_number}",
                    'title' => "Worker Attempt #{$attempt->attempt_number}",
                    'description' => "Response: HTTP {$attempt->response_code} | Duration: {$attempt->duration_ms}ms" . ($attempt->error_message ? " | Error: {$attempt->error_message}" : ""),
                    'status' => $attempt->status === 'success' ? 'success' : 'danger',
                    'timestamp' => $attempt->created_at,
                    'icon' => 'server',
                ];
            }
        }

        // Sort timeline events chronologically (newest first)
        usort($timeline, fn($a, $b) => $b['timestamp'] <=> $a['timestamp']);

        return view('admin.domain-detail', compact('domain', 'timeline'));
    }

    public function triggerCrawl(Request $request, int $id): RedirectResponse
    {
        $domain = Domain::findOrFail($id);

        $selectedStages = $request->input('stages', ['all']);
        if (is_string($selectedStages)) {
            $selectedStages = [$selectedStages];
        }

        $crawlMode = $request->input('crawl_mode', 'normal');

        $domain->update([
            'crawl_status' => 'pending',
            'is_accessible' => null,
            'http_status' => null,
            'last_crawl_error' => null,
        ]);

        $queuedJobsCount = 0;

        // Stage 1 or All
        if (in_array('all', $selectedStages, true) || in_array('reachability', $selectedStages, true)) {
            $autoAdvance = in_array('all', $selectedStages, true) || $request->boolean('auto_advance', true);
            CrawlJob::create([
                'id' => (string) Str::uuid(),
                'domain_id' => $domain->id,
                'job_type' => 'reachability',
                'priority' => 1000,
                'status' => 'pending',
                'attempt_count' => 0,
                'max_attempts' => 3,
                'payload' => [
                    'auto_advance' => $autoAdvance,
                    'crawl_mode' => $crawlMode,
                ],
            ]);
            $queuedJobsCount++;
        }

        // Stage 2 Homepage
        if (!in_array('all', $selectedStages, true) && in_array('homepage', $selectedStages, true)) {
            CrawlJob::create([
                'id' => (string) Str::uuid(),
                'domain_id' => $domain->id,
                'job_type' => 'homepage',
                'priority' => 1000,
                'status' => 'pending',
                'attempt_count' => 0,
                'max_attempts' => 3,
                'payload' => [
                    'crawl_mode' => $crawlMode,
                ],
            ]);
            $queuedJobsCount++;
        }

        // Stage 3 Target Sub-Pages
        if (!in_array('all', $selectedStages, true) && in_array('subpage', $selectedStages, true)) {
            $pages = $domain->pages()->whereIn('page_type', ['contact', 'about', 'careers', 'team'])->get();
            if ($pages->count() > 0) {
                foreach ($pages as $p) {
                    CrawlJob::create([
                        'id' => (string) Str::uuid(),
                        'domain_id' => $domain->id,
                        'job_type' => 'subpage',
                        'priority' => 1000,
                        'status' => 'pending',
                        'attempt_count' => 0,
                        'max_attempts' => 3,
                        'payload' => [
                            'target_url' => $p->url,
                            'crawl_mode' => $crawlMode,
                        ],
                    ]);
                    $queuedJobsCount++;
                }
            } else {
                CrawlJob::create([
                    'id' => (string) Str::uuid(),
                    'domain_id' => $domain->id,
                    'job_type' => 'subpage',
                    'priority' => 1000,
                    'status' => 'pending',
                    'attempt_count' => 0,
                    'max_attempts' => 3,
                    'payload' => [
                        'target_url' => "https://{$domain->normalized_domain}/contact-us",
                        'crawl_mode' => $crawlMode,
                    ],
                ]);
                $queuedJobsCount++;
            }
        }

        $stageText = implode(', ', $selectedStages);
        $modeText = strtoupper($crawlMode);
        return redirect()->back()->with('success', "[{$modeText} MODE] Targeted crawl ({$stageText}) triggered for {$domain->domain}! {$queuedJobsCount} job(s) queued at Priority 1000.");
    }
}
