@extends('admin.layout')

@section('title', 'Crawl Jobs Queue - B2B Control Plane')
@section('page-title', 'Crawl Job Queue')
@section('page-sub', 'Inspect pending, claimed, completed, and failed distributed crawl jobs.')

@section('content')
<div class="table-card">
    <div class="table-header">
        <h2 class="table-title">Crawl Jobs Queue ({{ number_format($totalCount ?? 0) }})</h2>
        <form method="GET" action="/admin/jobs" style="display: flex; gap: 12px; align-items: center;">
            @if(request('crawler_id'))
                <input type="hidden" name="crawler_id" value="{{ request('crawler_id') }}">
                <span class="badge badge-info" style="font-family: monospace;">Worker: {{ Str::limit(request('crawler_id'), 15) }}</span>
                <a href="/admin/jobs" style="color: #ff5555; text-decoration: none; font-size: 12px;">Clear Filter</a>
            @endif
            <select name="status" class="search-box" style="width: 150px;" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="claimed" {{ request('status') === 'claimed' ? 'selected' : '' }}>Claimed</option>
                <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
            </select>
            <button type="submit" class="btn">Filter</button>
        </form>
    </div>

    <table>
        <thead>
            <tr>
                <th>Job ID</th>
                <th>Target Domain</th>
                <th>Job Type</th>
                <th>Priority</th>
                <th>Status</th>
                <th>Assigned Crawler</th>
                <th>Attempts</th>
                <th>Lease Expires At</th>
            </tr>
        </thead>
        <tbody>
            @foreach($jobs as $job)
            <tr>
                <td style="font-family: monospace; font-size: 11px; color: var(--text-muted);">
                    {{ Str::limit($job->id, 12) }}
                </td>
                <td style="font-weight: 600; color: #fff;">{{ $job->domain ? $job->domain->domain : 'N/A' }}</td>
                <td><span class="badge badge-info">{{ $job->job_type }}</span></td>
                <td>{{ $job->priority }}</td>
                <td>
                    <span class="badge badge-{{ $job->status === 'completed' ? 'success' : ($job->status === 'claimed' ? 'info' : ($job->status === 'failed' ? 'danger' : 'warning')) }}">
                        {{ $job->status }}
                    </span>
                </td>
                <td style="color: var(--cyan); font-weight: 500;">{{ $job->crawler_id ?? 'Unassigned' }}</td>
                <td>{{ $job->attempt_count }} / {{ $job->max_attempts }}</td>
                <td style="font-size: 12px; color: var(--text-muted);">
                    {{ $job->lease_expires_at ? $job->lease_expires_at->diffForHumans() : '-' }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top: 20px;">
        {{ $jobs->links('admin.pagination') }}
    </div>
</div>
@endsection
