@extends('admin.layout')

@section('title', 'System Info & Crawl Engine Control - B2B Control Plane')
@section('page-title', 'System Info & Global Crawl Control')
@section('page-sub', 'Manage global crawler orchestration, pause/resume system status, and view infrastructure health diagnostics.')

@section('content')
@if(session('success'))
    <div style="background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.4); color: var(--emerald); padding: 14px; border-radius: 10px; margin-bottom: 24px; font-size: 14px; font-weight: 600;">
        {{ session('success') }}
    </div>
@endif

{{-- Master System Crawl Engine Control Card --}}
<div class="table-card" style="background: linear-gradient(135deg, rgba(15, 23, 42, 0.9), rgba(30, 41, 59, 0.8)); border: 1px solid var(--border-card); padding: 28px; border-radius: 16px; margin-bottom: 32px; box-shadow: 0 10px 30px rgba(0,0,0,0.4);">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
        <div>
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
                <h2 style="font-family: 'Outfit', sans-serif; font-size: 22px; font-weight: 800; color: #fff;">
                    ⚙️ Global System Crawl Engine
                </h2>
                @if($sysStatus === 'active')
                    <span class="badge badge-success" style="font-size: 13px; padding: 6px 16px;">🟢 SYSTEM ACTIVE</span>
                @elseif($sysStatus === 'paused')
                    <span class="badge badge-warning" style="font-size: 13px; padding: 6px 16px;">⏸️ SYSTEM PAUSED</span>
                @else
                    <span class="badge badge-danger" style="font-size: 13px; padding: 6px 16px;">🛑 SYSTEM STOPPED</span>
                @endif
            </div>
            <p style="font-size: 14px; color: var(--text-muted); max-width: 650px; line-height: 1.5;">
                Control the entire crawling system globally. Pausing or stopping crawling will safely instruct all node workers to pause polling, protecting database resources during data imports.
            </p>
        </div>

        <div style="display: flex; gap: 12px; flex-wrap: wrap;">
            <form method="POST" action="/admin/system/crawl-control">
                @csrf
                <input type="hidden" name="action" value="start">
                <button type="submit" class="btn" style="background: {{ $sysStatus === 'active' ? 'rgba(16, 185, 129, 0.3)' : 'linear-gradient(135deg, var(--emerald), #059669)' }}; font-size: 13px; font-weight: 700; padding: 10px 20px; box-shadow: 0 0 15px rgba(16, 185, 129, 0.2);" {{ $sysStatus === 'active' ? 'disabled' : '' }}>
                    ▶️ Start Engine
                </button>
            </form>

            <form method="POST" action="/admin/system/crawl-control">
                @csrf
                <input type="hidden" name="action" value="pause">
                <button type="submit" class="btn" style="background: {{ $sysStatus === 'paused' ? 'rgba(245, 158, 11, 0.3)' : 'linear-gradient(135deg, var(--amber), #d97706)' }}; font-size: 13px; font-weight: 700; padding: 10px 20px;" {{ $sysStatus === 'paused' ? 'disabled' : '' }}>
                    ⏸️ Pause Engine
                </button>
            </form>

            <form method="POST" action="/admin/system/crawl-control">
                @csrf
                <input type="hidden" name="action" value="stop">
                <button type="submit" class="btn" style="background: {{ $sysStatus === 'stopped' ? 'rgba(244, 63, 94, 0.3)' : 'linear-gradient(135deg, var(--rose), #e11d48)' }}; font-size: 13px; font-weight: 700; padding: 10px 20px;" {{ $sysStatus === 'stopped' ? 'disabled' : '' }}>
                    🛑 Stop Engine
                </button>
            </form>
        </div>
    </div>
</div>

{{-- Infrastructure Health Metrics Grid --}}
<div class="metrics-grid">
    <div class="card">
        <div class="card-label">Database Status</div>
        <div class="card-value" style="font-size: 20px; color: var(--emerald);">
            🐘 PostgreSQL
        </div>
        <div class="card-desc">{{ $dbStatus }}</div>
    </div>

    <div class="card">
        <div class="card-label">AWS S3 Storage</div>
        <div class="card-value" style="font-size: 20px; color: var(--cyan);">
            📦 {{ $s3Bucket }}
        </div>
        <div class="card-desc">Region: {{ $s3Region }} ({{ strtoupper($s3Disk) }})</div>
    </div>

    <div class="card">
        <div class="card-label">Active Worker Nodes</div>
        <div class="card-value">{{ $activeWorkers }}</div>
        <div class="card-desc">Node.js Distributed Workers</div>
    </div>

    <div class="card">
        <div class="card-label">Pending Crawl Queue</div>
        <div class="card-value">{{ $pendingJobs }}</div>
        <div class="card-desc">{{ $inProgressJobs }} Active Leasing</div>
    </div>
</div>

{{-- System Environment Diagnostics Table --}}
<div class="table-card">
    <h2 class="table-title" style="margin-bottom: 16px;">System Environment & Runtime Diagnostics</h2>
    <table>
        <thead>
            <tr>
                <th>Component</th>
                <th>Configuration Value</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="font-weight: 700; color: #fff;">PHP Runtime Version</td>
                <td style="font-family: monospace; color: var(--cyan);">{{ $phpVersion }}</td>
                <td><span class="badge badge-success">OK</span></td>
            </tr>
            <tr>
                <td style="font-weight: 700; color: #fff;">Laravel Framework Version</td>
                <td style="font-family: monospace; color: var(--cyan);">{{ $laravelVersion }}</td>
                <td><span class="badge badge-success">OK</span></td>
            </tr>
            <tr>
                <td style="font-weight: 700; color: #fff;">Storage Target Disk</td>
                <td style="font-family: monospace; color: var(--cyan);">{{ $s3Disk }}</td>
                <td><span class="badge badge-info">{{ strtoupper($s3Disk) }}</span></td>
            </tr>
            <tr>
                <td style="font-weight: 700; color: #fff;">AWS S3 Bucket Name</td>
                <td style="font-family: monospace; color: var(--cyan);">{{ $s3Bucket }}</td>
                <td><span class="badge badge-success">Configured</span></td>
            </tr>
            <tr>
                <td style="font-weight: 700; color: #fff;">Server Timezone</td>
                <td style="font-family: monospace; color: var(--cyan);">{{ config('app.timezone') }}</td>
                <td><span class="badge badge-info">Active</span></td>
            </tr>
        </tbody>
    </table>
</div>
@endsection
