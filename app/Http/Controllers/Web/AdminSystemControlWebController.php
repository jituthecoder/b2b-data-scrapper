<?php

namespace App\Http\Controllers\Web;

use App\Domain\Crawling\Models\CrawlJob;
use App\Domain\Crawling\Models\CrawlerNode;
use App\Domain\Crawling\Services\SystemCrawlControlService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AdminSystemControlWebController extends Controller
{
    public function index(SystemCrawlControlService $crawlControl): View
    {
        $status = $crawlControl->getStatus();

        // System Health & Infrastructure Diagnostics
        $dbStatus = 'Connected';
        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            $dbStatus = 'Disconnected: ' . $e->getMessage();
        }

        $s3Disk = config('filesystems.default', 'public');
        $s3Bucket = config('filesystems.disks.s3.bucket', 'N/A');
        $s3Region = config('filesystems.disks.s3.region', 'N/A');

        $activeWorkers = CrawlerNode::where('status', 'active')
            ->where('last_heartbeat_at', '>=', now()->subMinutes(2))
            ->count();

        $pendingJobs = CrawlJob::where('status', 'pending')->count();
        $inProgressJobs = CrawlJob::where('status', 'in_progress')->count();

        return view('admin.system-info', [
            'sysStatus' => $status,
            'dbStatus' => $dbStatus,
            's3Disk' => $s3Disk,
            's3Bucket' => $s3Bucket,
            's3Region' => $s3Region,
            'activeWorkers' => $activeWorkers,
            'pendingJobs' => $pendingJobs,
            'inProgressJobs' => $inProgressJobs,
            'phpVersion' => PHP_VERSION,
            'laravelVersion' => app()->version(),
        ]);
    }

    public function control(Request $request, SystemCrawlControlService $crawlControl): RedirectResponse
    {
        $action = strtolower($request->input('action', 'start'));

        $statusMap = [
            'start' => 'active',
            'resume' => 'active',
            'pause' => 'paused',
            'stop' => 'stopped',
        ];

        $newStatus = $statusMap[$action] ?? 'active';
        $crawlControl->setStatus($newStatus);

        $messages = [
            'active' => '🟢 Global crawling system is ACTIVE! Worker nodes will now poll and process jobs.',
            'paused' => '⏸️ Global crawling system is PAUSED! Worker nodes are sleeping safely.',
            'stopped' => '🛑 Global crawling system is STOPPED! Active job claims are halted.',
        ];

        return redirect()->back()->with('success', $messages[$newStatus]);
    }
}
