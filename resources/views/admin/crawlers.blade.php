@extends('admin.layout')

@section('title', 'Crawler Workers Monitor - B2B Control Plane')
@section('page-title', 'Crawler Worker Nodes')
@section('page-sub', 'Monitor registered Python/Node.js worker instances, heartbeat health, and active vs stopped status.')

@section('content')
<div style="display: flex; flex-direction: column; gap: 24px;">
    <div style="font-size: 16px; font-weight: 700; color: #fff;">
        Registered Worker Nodes ({{ $nodes->total() }})
    </div>

    @forelse($nodes as $node)
    <div class="table-card" style="padding: 24px;">
        <!-- Header Info -->
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; flex-wrap: wrap; gap: 16px;">
            <div>
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 6px;">
                    <h3 style="font-size: 18px; font-weight: 700; color: var(--cyan); margin: 0; font-family: monospace;">{{ $node->crawler_id }}</h3>
                    @if($node->is_online)
                        <span class="badge badge-success">🟢 ONLINE / RUNNING</span>
                    @else
                        <span class="badge badge-danger">🔴 OFFLINE / STOPPED</span>
                    @endif
                </div>
                <div style="font-size: 13px; color: var(--text-muted); display: flex; gap: 16px;">
                    <span>🖥 Host: <strong>{{ $node->hostname }}</strong></span>
                    <span>📦 Version: <strong>v{{ $node->version }}</strong></span>
                    <span>⏱ Last Heartbeat: <strong>{{ $node->last_heartbeat_at ? $node->last_heartbeat_at->diffForHumans() : 'Never' }}</strong></span>
                </div>
            </div>

            <div>
                <a href="/admin/jobs?crawler_id={{ $node->crawler_id }}" class="btn" style="font-size: 12px; padding: 6px 14px;">
                    View Worker Jobs Log ➔
                </a>
            </div>
        </div>

        <!-- Metrics Grid -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 12px; margin-bottom: 20px;">
            <div style="background: rgba(255, 255, 255, 0.03); border: 1px solid var(--border); padding: 12px 16px; border-radius: 8px;">
                <div style="font-size: 11px; color: var(--text-muted); text-transform: uppercase;">Active Leased Jobs</div>
                <div style="font-size: 20px; font-weight: 700; color: #38bdf8;">{{ $node->active_jobs_count }} / {{ $node->worker_count }}</div>
            </div>
            <div style="background: rgba(255, 255, 255, 0.03); border: 1px solid var(--border); padding: 12px 16px; border-radius: 8px;">
                <div style="font-size: 11px; color: var(--text-muted); text-transform: uppercase;">Completed Today</div>
                <div style="font-size: 20px; font-weight: 700; color: #4ade80;">{{ number_format($node->completed_today_count) }}</div>
            </div>
            <div style="background: rgba(255, 255, 255, 0.03); border: 1px solid var(--border); padding: 12px 16px; border-radius: 8px;">
                <div style="font-size: 11px; color: var(--text-muted); text-transform: uppercase;">Failed Today</div>
                <div style="font-size: 20px; font-weight: 700; color: #f87171;">{{ number_format($node->failed_today_count) }}</div>
            </div>
            <div style="background: rgba(255, 255, 255, 0.03); border: 1px solid var(--border); padding: 12px 16px; border-radius: 8px;">
                <div style="font-size: 11px; color: var(--text-muted); text-transform: uppercase;">Total Completed</div>
                <div style="font-size: 20px; font-weight: 700; color: #a78bfa;">{{ number_format($node->total_completed_count) }}</div>
            </div>
        </div>

        <!-- Currently Active Crawling Target Domains (20-30 Domains) -->
        <div>
            <h4 style="font-size: 13px; font-weight: 600; color: var(--text-muted); margin-bottom: 10px; text-transform: uppercase; letter-spacing: 0.5px;">
                ⚡ Currently Active Target Domains Being Crawled Right Now ({{ count($node->active_domains) }})
            </h4>

            @if(count($node->active_domains) > 0)
                <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                    @foreach($node->active_domains as $activeJob)
                        <div style="background: rgba(56, 189, 248, 0.1); border: 1px solid rgba(56, 189, 248, 0.3); padding: 6px 12px; border-radius: 6px; font-size: 12px;">
                            <span style="font-weight: 600; color: #fff;">{{ $activeJob->domain ? $activeJob->domain->domain : 'Domain #' . $activeJob->domain_id }}</span>
                            <span style="color: var(--text-muted); font-size: 10px; margin-left: 6px;">[{{ $activeJob->job_type }}]</span>
                        </div>
                    @endforeach
                </div>
            @else
                <div style="font-size: 12px; color: var(--text-muted); font-style: italic; background: rgba(255, 255, 255, 0.02); padding: 12px; border-radius: 6px;">
                    No active leased jobs currently processing on this worker node.
                </div>
            @endif
        </div>
    </div>
    @empty
    <div class="table-card" style="padding: 40px; text-align: center; color: var(--text-muted);">
        No crawler worker nodes registered yet. Worker instances will appear here as soon as Docker containers boot up.
    </div>
    @endforelse

    <div>
        {{ $nodes->links('admin.pagination') }}
    </div>
</div>
@endsection
