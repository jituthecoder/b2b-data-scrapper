@extends('admin.layout')

@section('title', 'Domain Explorer - B2B Control Plane')
@section('page-title', 'Domain Explorer')
@section('page-sub', 'Browse normalized domains, accessibility status, and click any domain for full audit history & extracted intelligence.')

@section('content')
@if(session('success'))
    <div style="background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.4); color: var(--emerald); padding: 14px; border-radius: 10px; margin-bottom: 24px; font-size: 14px; font-weight: 600;">
        {{ session('success') }}
    </div>
@endif

{{-- Add & Bulk Import Domains Card --}}
<div class="table-card" style="margin-bottom: 32px;">
    <h2 class="table-title" style="margin-bottom: 16px;">➕ Add / Bulk Import Custom Domains</h2>
    <form method="POST" action="/admin/domains">
        @csrf
        <div style="margin-bottom: 16px;">
            <label style="display: block; font-size: 12px; font-weight: 600; color: var(--text-muted); margin-bottom: 6px;">
                Enter or Paste Domains (1 Domain per line OR comma-separated)
            </label>
            <textarea name="domains_input" rows="3" class="search-box" style="width: 100%; font-family: monospace; font-size: 13px;" placeholder="stripe.com&#10;w3speedup.com&#10;shopify.com&#10;slack.com" required></textarea>
        </div>
        <button type="submit" class="btn" style="background: linear-gradient(135deg, var(--emerald), #059669); font-weight: 700;">
            🚀 Register & Queue Crawl
        </button>
    </form>
</div>

<div class="table-card">
    <div class="table-header">
        <h2 class="table-title">Registered Domains ({{ $domains->total() }})</h2>
        <form method="GET" action="/admin/domains" style="display: flex; gap: 12px; align-items: center;">
            <select name="filter" class="search-box" style="width: 190px;" onchange="this.form.submit()">
                <option value="">All Domains</option>
                <option value="with_emails" {{ request('filter') === 'with_emails' ? 'selected' : '' }}>📧 With Emails Only</option>
                <option value="accessible" {{ request('filter') === 'accessible' ? 'selected' : '' }}>🟢 Accessible Only</option>
                <option value="completed" {{ request('filter') === 'completed' ? 'selected' : '' }}>✅ Completed Crawl</option>
            </select>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search domain name..." class="search-box">
            <button type="submit" class="btn">Filter</button>
        </form>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Domain Name</th>
                <th>TLD</th>
                <th>Accessibility</th>
                <th>Crawl Status</th>
                <th>Extracted Emails</th>
                <th>Companies</th>
                <th style="text-align: right;">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($domains as $d)
            <tr>
                <td>{{ $d->id }}</td>
                <td style="font-weight: 700;">
                    <a href="/admin/domains/{{ $d->id }}" style="color: var(--cyan); text-decoration: none; display: inline-flex; align-items: center; gap: 8px;">
                        @if($d->favicon_url)
                            <img src="{{ $d->favicon_url }}" alt="" style="width: 16px; height: 16px; object-fit: contain; border-radius: 3px;">
                        @endif
                        {{ $d->domain }} &rarr;
                    </a>
                </td>
                <td><span class="badge badge-info">{{ $d->tld }}</span></td>
                <td>
                    @if($d->is_accessible === true)
                        <span class="badge badge-success">HTTP {{ $d->http_status ?? 200 }}</span>
                    @elseif($d->is_accessible === false)
                        <span class="badge badge-danger">Unaccessible</span>
                    @else
                        <span class="badge badge-warning">Checking...</span>
                    @endif
                </td>
                <td>
                    <span class="badge badge-{{ $d->crawl_status === 'completed' ? 'success' : ($d->crawl_status === 'failed' ? 'danger' : 'warning') }}">
                        {{ $d->crawl_status }}
                    </span>
                </td>
                <td>
                    @if(($d->emails_count ?? 0) > 0)
                        <a href="/admin/domains/{{ $d->id }}" style="text-decoration: none;">
                            <span class="badge badge-success">📧 {{ $d->emails_count }} Email{{ $d->emails_count > 1 ? 's' : '' }}</span>
                        </a>
                    @else
                        <span style="color: var(--text-muted); font-size: 12px;">0 Emails</span>
                    @endif
                </td>
                <td>
                    @forelse($d->companies as $c)
                        <span class="badge badge-info">{{ $c->name }}</span>
                    @empty
                        <span style="color: var(--text-muted); font-size: 12px;">None</span>
                    @endforelse
                </td>
                <td style="text-align: right;">
                    <form method="POST" action="/admin/domains/{{ $d->id }}/crawl" style="display: inline-block;">
                        @csrf
                        <button type="submit" class="btn" style="padding: 4px 10px; font-size: 11px; background: rgba(16, 185, 129, 0.2); color: var(--emerald); border: 1px solid rgba(16, 185, 129, 0.4);">
                            🚀 Crawl Now
                        </button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top: 20px;">
        {{ $domains->links('admin.pagination') }}
    </div>
</div>
@endsection
