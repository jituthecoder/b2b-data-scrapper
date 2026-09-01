@extends('admin.layout')

@section('title', 'Google API Keys Manager - B2B Control Plane')
@section('page-title', 'Google API Keys Pool Manager')
@section('page-sub', 'Manage free Google Custom Search API key rotation pool, daily quotas, and bulk imports.')

@section('content')
@if(session('success'))
    <div style="background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.4); color: var(--emerald); padding: 14px; border-radius: 10px; margin-bottom: 24px; font-size: 14px; font-weight: 600;">
        {{ session('success') }}
    </div>
@endif

<div class="metrics-grid">
    <div class="card">
        <div class="card-label">Total Pool Keys</div>
        <div class="card-value">{{ number_format($stats['total_keys']) }}</div>
        <div class="card-desc">Keys in Database</div>
    </div>
    <div class="card">
        <div class="card-label">Active & Available Keys</div>
        <div class="card-value" style="color: var(--emerald);">🟢 {{ number_format($stats['active_keys']) }}</div>
        <div class="card-desc">Under 95 Daily Requests</div>
    </div>
    <div class="card">
        <div class="card-label">Exhausted Keys Today</div>
        <div class="card-value" style="color: var(--rose);">🔴 {{ number_format($stats['exhausted_keys']) }}</div>
        <div class="card-desc">Hit Daily Limit or 429</div>
    </div>
    <div class="card">
        <div class="card-label">Total Searches Today</div>
        <div class="card-value" style="color: var(--cyan);">{{ number_format($stats['total_requests_today']) }}</div>
        <div class="card-desc">Free Google Queries Made</div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 24px; margin-bottom: 32px;">
    {{-- Add & Bulk Paste Keys Form --}}
    <div class="table-card" style="margin-bottom: 0;">
        <h2 class="table-title" style="margin-bottom: 16px;">Add / Bulk Import API Keys</h2>
        <form method="POST" action="/admin/google-keys">
            @csrf
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 12px; font-weight: 600; color: var(--text-muted); margin-bottom: 6px;">
                    Paste API Keys (1 Key per line OR comma-separated)
                </label>
                <textarea name="keys_input" rows="6" class="search-box" style="width: 100%; font-family: monospace; font-size: 12px;" placeholder="AIzaSyB123456789...&#10;AIzaSyB98765432...&#10;AIzaSyC11223344..." required></textarea>
            </div>

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 12px; font-weight: 600; color: var(--text-muted); margin-bottom: 6px;">
                    Custom Search Engine ID (CX) (Optional)
                </label>
                <input type="text" name="cx" placeholder="e.g. 0123456789abcdef:cx" class="search-box" style="width: 100%;">
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 12px; font-weight: 600; color: var(--text-muted); margin-bottom: 6px;">
                    Daily Request Limit per Key (Default 95)
                </label>
                <input type="number" name="daily_limit" value="95" min="1" max="100" class="search-box" style="width: 100%;">
            </div>

            <button type="submit" class="btn" style="width: 100%;">Import Keys into Pool</button>
        </form>
    </div>

    {{-- Keys Pool Table --}}
    <div class="table-card" style="margin-bottom: 0;">
        <div class="table-header">
            <h2 class="table-title">API Keys Pool ({{ $keys->total() }})</h2>
            <div style="display: flex; gap: 10px;">
                <form method="POST" action="/admin/google-keys/reset-all">
                    @csrf
                    <button type="submit" class="btn" style="background: linear-gradient(135deg, #06b6d4, #3b82f6);">
                        Reset All Quotas
                    </button>
                </form>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Key Prefix</th>
                    <th>CX ID</th>
                    <th>Usage Today</th>
                    <th>Status</th>
                    <th>Last Used</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($keys as $k)
                <tr>
                    <td style="font-family: monospace; font-weight: 700; color: var(--cyan);">
                        {{ Str::limit($k->api_key, 12) }}
                    </td>
                    <td style="font-size: 12px; color: var(--text-muted);">
                        {{ $k->cx ? Str::limit($k->cx, 10) : 'Global CX' }}
                    </td>
                    <td>
                        <div style="font-size: 12px; font-weight: 600;">
                            {{ $k->requests_today }} / {{ $k->daily_limit }}
                        </div>
                        <div style="width: 100px; height: 4px; background: rgba(255,255,255,0.1); border-radius: 2px; margin-top: 4px;">
                            <div style="width: {{ min(100, ($k->requests_today / $k->daily_limit) * 100) }}%; height: 100%; background: {{ $k->is_exhausted ? 'var(--rose)' : 'var(--emerald)' }}; border-radius: 2px;"></div>
                        </div>
                    </td>
                    <td>
                        @if(!$k->is_active)
                            <span class="badge badge-warning">⚪ Disabled</span>
                        @elseif($k->is_exhausted)
                            <span class="badge badge-danger">🔴 Exhausted</span>
                        @else
                            <span class="badge badge-success">🟢 Active</span>
                        @endif
                    </td>
                    <td style="font-size: 12px; color: var(--text-muted);">
                        {{ $k->last_used_at ? $k->last_used_at->diffForHumans() : 'Never' }}
                    </td>
                    <td style="text-align: right;">
                        <div style="display: flex; gap: 6px; justify-content: flex-end;">
                            <form method="POST" action="/admin/google-keys/{{ $k->id }}/reset">
                                @csrf
                                <button type="submit" class="btn" style="padding: 4px 8px; font-size: 11px; background: rgba(16, 185, 129, 0.2); color: var(--emerald); border: 1px solid rgba(16, 185, 129, 0.4);">
                                    Reset
                                </button>
                            </form>

                            <form method="POST" action="/admin/google-keys/{{ $k->id }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn" style="padding: 4px 8px; font-size: 11px; background: rgba(244, 63, 94, 0.2); color: var(--rose); border: 1px solid rgba(244, 63, 94, 0.4);" onclick="return confirm('Delete this API key?')">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 30px;">
                        No Google Search API keys added yet. Use the form on the left to paste your keys.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <div style="margin-top: 20px;">
            {{ $keys->links('admin.pagination') }}
        </div>
    </div>
</div>
@endsection
