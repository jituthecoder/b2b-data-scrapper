@extends('admin.layout')

@section('title', 'Dashboard Overview - B2B Control Plane')
@section('page-title', 'System Dashboard Overview')
@section('page-sub', 'Live statistics, entity totals, crawler capacity, and queue metrics.')

@section('content')
<div class="metrics-grid">
    <div class="card">
        <div class="card-label">Total Target Domains</div>
        <div class="card-value">{{ number_format($stats['domains']['total']) }}</div>
        <div class="card-desc">Indexed & Normalized</div>
    </div>
    <div class="card">
        <div class="card-label">Accessible Domains</div>
        <div class="card-value" style="color: var(--emerald);">{{ number_format($stats['domains']['accessible']) }}</div>
        <div class="card-desc">HTTP 200 Reachable</div>
    </div>
    <div class="card">
        <div class="card-label">Crawler Worker Nodes</div>
        <div class="card-value" style="color: var(--cyan);">
            <span style="color: var(--emerald);">🟢 {{ $stats['crawlers']['active_count'] }} Active</span>
            @if($stats['crawlers']['stopped_count'] > 0)
                <span style="font-size: 16px; color: var(--rose); margin-left: 8px;">🔴 {{ $stats['crawlers']['stopped_count'] }} Stopped</span>
            @endif
        </div>
        <div class="card-desc">{{ $stats['crawlers']['total_capacity'] }} Concurrent Workers Capacity</div>
    </div>
    <div class="card">
        <div class="card-label">Pending Crawl Queue</div>
        <div class="card-value" style="color: var(--amber);">{{ number_format($stats['jobs']['pending']) }}</div>
        <div class="card-desc">{{ number_format($stats['jobs']['completed']) }} Jobs Completed</div>
    </div>
</div>

<div class="metrics-grid">
    <div class="card">
        <div class="card-label">Enriched Companies</div>
        <div class="card-value">{{ number_format($stats['entities']['companies']) }}</div>
    </div>
    <div class="card">
        <div class="card-label">Executive Contacts</div>
        <div class="card-value">{{ number_format($stats['entities']['contacts']) }}</div>
    </div>
    <div class="card">
        <div class="card-label">Extracted Emails</div>
        <div class="card-value">{{ number_format($stats['entities']['emails']) }}</div>
    </div>
    <div class="card">
        <div class="card-label">Technologies Detected</div>
        <div class="card-value">{{ number_format($stats['entities']['technologies']) }}</div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
    <div class="table-card">
        <div class="table-header">
            <h2 class="table-title">Recent Domain Discovery</h2>
            <a href="/admin/domains" class="btn">View All</a>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Domain</th>
                    <th>TLD</th>
                    <th>Status</th>
                    <th>Crawl Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($recentDomains as $d)
                <tr>
                    <td style="font-weight: 600;">{{ $d->domain }}</td>
                    <td><span class="badge badge-info">{{ $d->tld }}</span></td>
                    <td>
                        @if($d->is_accessible)
                            <span class="badge badge-success">Accessible</span>
                        @else
                            <span class="badge badge-warning">Unchecked</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge badge-{{ $d->crawl_status === 'completed' ? 'success' : ($d->crawl_status === 'failed' ? 'danger' : 'warning') }}">
                            {{ $d->crawl_status }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="table-card">
        <div class="table-header">
            <h2 class="table-title">Live Crawl Queue Activity</h2>
            <a href="/admin/jobs" class="btn">View All</a>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Job ID</th>
                    <th>Domain</th>
                    <th>Stage / Type</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($recentJobs as $job)
                <tr>
                    <td style="font-family: monospace; font-size: 11px; color: var(--text-muted);">
                        {{ Str::limit($job->id, 8) }}
                    </td>
                    <td>{{ $job->domain ? $job->domain->domain : 'N/A' }}</td>
                    <td><span class="badge badge-info">{{ $job->job_type }}</span></td>
                    <td>
                        <span class="badge badge-{{ $job->status === 'completed' ? 'success' : ($job->status === 'claimed' ? 'info' : 'warning') }}">
                            {{ $job->status }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
