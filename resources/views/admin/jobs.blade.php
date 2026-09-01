@extends('admin.layout')

@section('title', 'Crawl Jobs Queue - B2B Control Plane')
@section('page-title', 'Crawl Job Queue')
@section('page-sub', 'Inspect pending, claimed, completed, and failed distributed crawl jobs.')

@section('content')
<div class="table-card">
    <div class="table-header">
        <h2 class="table-title">Crawl Jobs Queue ({{ $jobs->total() }})</h2>
        <form method="GET" action="/admin/jobs" style="display: flex; gap: 12px;">
            <select name="status" class="search-box" style="width: 150px;">
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
