@extends('admin.layout')

@section('title', 'Crawler Workers Monitor - B2B Control Plane')
@section('page-title', 'Crawler Worker Nodes')
@section('page-sub', 'Monitor registered Python/Node.js worker instances, heartbeat health, and active vs stopped status.')

@section('content')
<div class="table-card">
    <div class="table-header">
        <h2 class="table-title">Registered Worker Nodes ({{ $nodes->total() }})</h2>
    </div>

    <table>
        <thead>
            <tr>
                <th>Crawler ID</th>
                <th>Hostname</th>
                <th>Version</th>
                <th>Worker Concurrency</th>
                <th>State</th>
                <th>Capabilities</th>
                <th>Last Heartbeat</th>
            </tr>
        </thead>
        <tbody>
            @forelse($nodes as $node)
            <tr>
                <td style="font-weight: 700; color: var(--cyan);">{{ $node->crawler_id }}</td>
                <td>{{ $node->hostname }}</td>
                <td><span class="badge badge-info">v{{ $node->version }}</span></td>
                <td style="font-weight: 600;">{{ $node->worker_count }} concurrent domains</td>
                <td>
                    @if($node->is_online)
                        <span class="badge badge-success">🟢 RUNNING / ACTIVE</span>
                    @else
                        <span class="badge badge-danger">🔴 STOPPED / OFFLINE</span>
                    @endif
                </td>
                <td>
                    @foreach($node->capabilities ?? [] as $cap)
                        <span class="badge badge-info" style="margin-right: 4px;">{{ $cap }}</span>
                    @endforeach
                </td>
                <td>{{ $node->last_heartbeat_at ? $node->last_heartbeat_at->diffForHumans() : 'Never' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 30px;">
                    No crawler nodes registered yet. External Python/Node.js worker nodes will appear here when registered.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div style="margin-top: 20px;">
        {{ $nodes->links('admin.pagination') }}
    </div>
</div>
@endsection
