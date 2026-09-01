@extends('admin.layout')

@section('title', $domain->domain . ' - Domain Intelligence & Audit Log')
@section('page-title', 'Domain Intelligence: ' . $domain->domain)
@section('page-sub', 'Complete extracted website data, tech stack, WordPress ecosystem, and step-by-step stage execution audit trail.')

@section('content')
<style>
.custom-scroll::-webkit-scrollbar {
    width: 6px;
}
.custom-scroll::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.03);
    border-radius: 4px;
}
.custom-scroll::-webkit-scrollbar-thumb {
    background: rgba(99, 102, 241, 0.3);
    border-radius: 4px;
}
.custom-scroll::-webkit-scrollbar-thumb:hover {
    background: var(--primary);
}
</style>

@if(session('success'))
    <div style="background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.4); color: var(--emerald); padding: 14px; border-radius: 10px; margin-bottom: 24px; font-size: 14px; font-weight: 600;">
        {{ session('success') }}
    </div>
@endif

<div style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
    <a href="/admin/domains" class="btn" style="background: rgba(255,255,255,0.08); color: var(--text-muted); padding: 6px 14px; font-size: 13px;">
        &larr; Back to Domain Explorer
    </a>

    {{-- Interactive Selective Multi-Step Crawl Form with Normal vs Deep Mode --}}
    <form method="POST" action="/admin/domains/{{ $domain->id }}/crawl" style="background: rgba(15, 23, 42, 0.7); border: 1px solid var(--border-card); padding: 12px 18px; border-radius: 12px; display: flex; align-items: center; gap: 16px; flex-wrap: wrap;">
        @csrf
        <div style="font-size: 12px; font-weight: 700; color: var(--cyan); text-transform: uppercase; letter-spacing: 0.5px;">
            Crawl Mode:
        </div>
        <select name="crawl_mode" class="search-box" style="width: 170px; padding: 6px 10px; font-size: 12px;">
            <option value="normal" selected>⚡ Normal Scrape (Fast)</option>
            <option value="deep">🔬 Deep Scrape (Comprehensive)</option>
        </select>

        <div style="font-size: 12px; font-weight: 700; color: var(--cyan); text-transform: uppercase; letter-spacing: 0.5px;">
            Stages:
        </div>
        <label style="display: flex; align-items: center; gap: 6px; font-size: 12px; color: #fff; cursor: pointer;">
            <input type="checkbox" name="stages[]" value="all" checked onchange="toggleCrawlStageCheckboxes(this)">
            <strong>⚡ Full Pipeline</strong>
        </label>
        <label style="display: flex; align-items: center; gap: 6px; font-size: 12px; color: var(--text-muted); cursor: pointer;">
            <input type="checkbox" name="stages[]" value="reachability" class="stage-sub-check">
            🌐 Stage 1
        </label>
        <label style="display: flex; align-items: center; gap: 6px; font-size: 12px; color: var(--text-muted); cursor: pointer;">
            <input type="checkbox" name="stages[]" value="homepage" class="stage-sub-check">
            🏠 Stage 2
        </label>
        <label style="display: flex; align-items: center; gap: 6px; font-size: 12px; color: var(--text-muted); cursor: pointer;">
            <input type="checkbox" name="stages[]" value="subpage" class="stage-sub-check">
            📄 Stage 3
        </label>

        <button type="submit" class="btn" style="background: linear-gradient(135deg, var(--emerald), #059669); font-size: 13px; font-weight: 700; padding: 8px 18px; box-shadow: 0 0 15px rgba(16, 185, 129, 0.3);">
            🚀 Execute Crawl
        </button>
    </form>
</div>

<script>
function toggleCrawlStageCheckboxes(allCheckbox) {
    const subChecks = document.querySelectorAll('.stage-sub-check');
    if (allCheckbox.checked) {
        subChecks.forEach(c => { c.checked = false; c.disabled = true; });
    } else {
        subChecks.forEach(c => { c.disabled = false; });
    }
}
document.addEventListener('DOMContentLoaded', function() {
    const allCb = document.querySelector('input[value="all"]');
    if (allCb) toggleCrawlStageCheckboxes(allCb);
});
</script>

{{-- Domain Overview Banner Card --}}
<div class="table-card" style="margin-bottom: 32px;">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 20px;">
        <div>
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
                <h1 style="font-family: 'Outfit', sans-serif; font-size: 26px; font-weight: 800; color: #fff;">{{ $domain->domain }}</h1>
                <span class="badge badge-info">{{ strtoupper($domain->tld) }}</span>
                @if($domain->is_accessible === true)
                    <span class="badge badge-success">HTTP {{ $domain->http_status ?? 200 }} Accessible</span>
                @elseif($domain->is_accessible === false)
                    <span class="badge badge-danger">Unaccessible / Dead</span>
                @else
                    <span class="badge badge-warning">Checking Accessibility...</span>
                @endif
            </div>
            <div style="font-size: 13px; color: var(--text-muted);">
                Normalized: <span style="color: var(--cyan); font-weight: 600;">{{ $domain->normalized_domain }}</span> |
                Scheme: <span style="color: #fff;">{{ $domain->scheme }}://</span> |
                WWW Variant: <span style="color: #fff;">{{ $domain->www_variant ? 'Yes' : 'No' }}</span>
            </div>
        </div>

        <div style="display: flex; gap: 12px;">
            <div class="card" style="padding: 14px 20px; text-align: center; margin-bottom: 0;">
                <div class="card-label">Crawl Status</div>
                <div style="font-size: 14px; font-weight: 700; margin-top: 4px;">
                    <span class="badge badge-{{ $domain->crawl_status === 'completed' ? 'success' : ($domain->crawl_status === 'failed' ? 'danger' : 'warning') }}">
                        {{ strtoupper($domain->crawl_status) }}
                    </span>
                </div>
            </div>
            <div class="card" style="padding: 14px 20px; text-align: center; margin-bottom: 0;">
                <div class="card-label">Last Crawled</div>
                <div style="font-size: 13px; font-weight: 600; color: #fff; margin-top: 4px;">
                    {{ $domain->last_crawled_at ? $domain->last_crawled_at->diffForHumans() : 'Never' }}
                </div>
            </div>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 24px; align-items: start;">
    {{-- Left Column: Scrollable Stage Execution Timeline Audit Trail --}}
    <div class="table-card" style="position: sticky; top: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h2 class="table-title" style="margin-bottom: 0;">Stage Task Execution History</h2>
            <span class="badge badge-info" style="font-size: 11px;">{{ count($timeline) }} Events</span>
        </div>
        
        <div style="max-height: 550px; overflow-y: auto; padding-right: 8px;" class="custom-scroll">
            <div style="position: relative; padding-left: 20px; border-left: 2px solid var(--border-card); margin-left: 8px;">
                @forelse($timeline as $event)
                <div style="position: relative; margin-bottom: 24px;">
                    {{-- Timeline Dot --}}
                    <div style="position: absolute; left: -27px; top: 0; width: 12px; height: 12px; border-radius: 50%; background: {{ $event['status'] === 'success' ? 'var(--emerald)' : ($event['status'] === 'danger' ? 'var(--rose)' : 'var(--cyan)') }}; box-shadow: 0 0 10px {{ $event['status'] === 'success' ? 'var(--emerald)' : 'var(--cyan)' }};"></div>
                    
                    <div style="font-size: 11px; font-weight: 700; color: var(--cyan); text-transform: uppercase; letter-spacing: 0.5px;">
                        {{ $event['stage'] }}
                    </div>
                    <div style="font-size: 14px; font-weight: 700; color: #fff; margin: 2px 0 4px 0;">
                        {{ $event['title'] }}
                    </div>
                    <div style="font-size: 12px; color: var(--text-muted); line-height: 1.4;">
                        {{ $event['description'] }}
                    </div>
                    <div style="font-size: 11px; color: rgba(255,255,255,0.4); margin-top: 4px;">
                        {{ $event['timestamp'] ? \Carbon\Carbon::parse($event['timestamp'])->format('M d, Y H:i:s') : 'N/A' }}
                    </div>
                </div>
                @empty
                <div style="color: var(--text-muted); font-size: 13px;">No execution history recorded yet.</div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Right Column: Extracted Intelligence Data Explorer --}}
    <div style="display: flex; flex-direction: column; gap: 24px;">
        {{-- Website Visual Screenshot Snapshot Card --}}
        @if($domain->screenshot_url)
        <div class="table-card" style="margin-bottom: 0; padding: 18px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                <h2 class="table-title" style="margin-bottom: 0;">Website Visual Snapshot</h2>
                <a href="{{ $domain->screenshot_url }}" target="_blank" rel="noopener noreferrer" class="badge badge-info" style="text-decoration: none;">View Full S3 Snapshot &#x2197;</a>
            </div>
            <div style="border-radius: 10px; overflow: hidden; border: 1px solid var(--border-card); box-shadow: 0 4px 20px rgba(0,0,0,0.4);">
                <img src="{{ $domain->screenshot_url }}" alt="{{ $domain->domain }} Visual Snapshot" style="width: 100%; max-height: 350px; object-fit: cover; object-position: top; display: block;">
            </div>
        </div>
        @endif

        {{-- Company Profile Card --}}
        <div class="table-card" style="margin-bottom: 0;">
            <h2 class="table-title" style="margin-bottom: 16px;">Extracted Company Profile</h2>
            @forelse($domain->companies as $comp)
            <div style="background: rgba(255,255,255,0.03); border: 1px solid var(--border-card); border-radius: 10px; padding: 16px; margin-bottom: 12px; display: flex; gap: 16px; align-items: flex-start;">
                @if($comp->logo_url)
                    <img src="{{ $comp->logo_url }}" alt="{{ $comp->name }} Logo" style="width: 52px; height: 52px; object-fit: contain; border-radius: 8px; background: #fff; padding: 4px; border: 1px solid var(--border-card);">
                @endif
                <div style="flex: 1;">
                    <div style="font-size: 16px; font-weight: 700; color: #fff; margin-bottom: 4px;">{{ $comp->name }}</div>
                    <div style="font-size: 12px; color: var(--cyan); margin-bottom: 8px;">Legal Name: {{ $comp->legal_name ?? $comp->name }}</div>
                    <p style="font-size: 13px; color: var(--text-muted); line-height: 1.5;">
                        {{ $comp->description ?? 'No company description extracted.' }}
                    </p>
                </div>
            </div>
            @empty
            <div style="color: var(--text-muted); font-size: 13px;">No company profile extracted yet.</div>
            @endforelse
        </div>

        {{-- BuiltWith-Style General Technology Stack Card --}}
        @php
            $generalTech = $domain->technologies->whereNotIn('category', ['WordPress Theme', 'WordPress Plugin']);
            $themes = $domain->technologies->where('category', 'WordPress Theme');
            $plugins = $domain->technologies->where('category', 'WordPress Plugin');
        @endphp

        <div class="table-card" style="margin-bottom: 0;">
            <h2 class="table-title" style="margin-bottom: 16px;">Detected Technology Stack ({{ $generalTech->count() }})</h2>
            <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                @forelse($generalTech as $tech)
                <div style="background: rgba(99, 102, 241, 0.12); border: 1px solid rgba(99, 102, 241, 0.3); border-radius: 8px; padding: 8px 14px;">
                    <div style="font-size: 13px; font-weight: 700; color: #fff;">{{ $tech->name }}</div>
                    <div style="font-size: 11px; color: var(--cyan);">{{ $tech->category ?? 'General' }}</div>
                </div>
                @empty
                <div style="color: var(--text-muted); font-size: 13px;">No general technologies detected.</div>
                @endforelse
            </div>
        </div>

        {{-- Dedicated Standalone WordPress Ecosystem Card --}}
        @if($themes->count() > 0 || $plugins->count() > 0)
        <div class="table-card" style="margin-bottom: 0; background: linear-gradient(135deg, rgba(245, 158, 11, 0.05), rgba(15, 23, 42, 0.6)); border: 1px solid rgba(245, 158, 11, 0.3);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <h2 class="table-title" style="color: var(--amber); margin-bottom: 0;">WordPress Theme & Installed Plugins</h2>
                <span class="badge badge-warning" style="font-size: 12px;">WordPress Ecosystem</span>
            </div>

            @if($themes->count() > 0)
            <div style="margin-bottom: 20px;">
                <div style="font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">Active WordPress Theme:</div>
                <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                    @foreach($themes as $theme)
                        <div style="background: rgba(245, 158, 11, 0.15); border: 1px solid rgba(245, 158, 11, 0.4); border-radius: 8px; padding: 10px 16px;">
                            <div style="font-size: 14px; font-weight: 700; color: #fff;">🎨 {{ $theme->name }}</div>
                            <div style="font-size: 11px; color: var(--amber); margin-top: 2px;">Active Theme</div>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif

            @if($plugins->count() > 0)
            <div>
                <div style="font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">Installed WordPress Plugins ({{ $plugins->count() }}):</div>
                <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                    @foreach($plugins as $plugin)
                        <div style="background: rgba(99, 102, 241, 0.12); border: 1px solid rgba(99, 102, 241, 0.3); border-radius: 8px; padding: 8px 14px;">
                            <div style="font-size: 13px; font-weight: 700; color: #fff;">🔌 {{ $plugin->name }}</div>
                            <div style="font-size: 11px; color: #a5b4fc; margin-top: 2px;">WordPress Plugin</div>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
        @endif

        {{-- Extracted Contacts & Emails Card --}}
        <div class="table-card" style="margin-bottom: 0;">
            <h2 class="table-title" style="margin-bottom: 16px;">Extracted Contacts & Emails ({{ $domain->emails->count() }})</h2>
            <table>
                <thead>
                    <tr>
                        <th>Email Address</th>
                        <th>Type</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($domain->emails as $em)
                    <tr>
                        <td style="font-family: monospace; font-weight: 600; color: #fff;">{{ $em->email }}</td>
                        <td><span class="badge badge-info">{{ $em->type ?? 'generic' }}</span></td>
                        <td><span class="badge badge-success">{{ $em->verification_status ?? 'unverified' }}</span></td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" style="color: var(--text-muted); font-size: 13px;">No email addresses extracted yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Extracted Social Profiles Card --}}
        <div class="table-card" style="margin-bottom: 0;">
            <h2 class="table-title" style="margin-bottom: 16px;">Social Media Profiles ({{ $domain->socialProfiles->count() }})</h2>
            <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                @forelse($domain->socialProfiles as $soc)
                @php
                    $targetSocialUrl = $soc->profile_url ?? $soc->normalized_url;
                @endphp
                <a href="{{ $targetSocialUrl }}" target="_blank" rel="noopener noreferrer" class="btn" style="background: rgba(255,255,255,0.06); border: 1px solid var(--border-card); color: #fff; font-size: 12px;">
                    <span style="color: var(--cyan); font-weight: 700; text-transform: uppercase;">{{ $soc->platform }}:</span> {{ Str::limit($targetSocialUrl, 35) }} &#x2197;
                </a>
                @empty
                <div style="color: var(--text-muted); font-size: 13px;">No social media profiles extracted yet.</div>
                @endforelse
            </div>
        </div>

        {{-- Discovered Web Pages Card --}}
        <div class="table-card" style="margin-bottom: 0;">
            <h2 class="table-title" style="margin-bottom: 16px;">Discovered Web Pages ({{ $domain->pages->count() }})</h2>
            <table>
                <thead>
                    <tr>
                        <th>Page Type</th>
                        <th>URL</th>
                        <th>Title</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($domain->pages as $pg)
                    <tr>
                        <td><span class="badge badge-info">{{ $pg->page_type }}</span></td>
                        <td style="font-size: 12px; color: var(--cyan);">
                            <a href="{{ $pg->url }}" target="_blank" rel="noopener noreferrer" style="color: var(--cyan); text-decoration: none;">{{ Str::limit($pg->url, 40) }} &#x2197;</a>
                        </td>
                        <td style="font-size: 12px; color: #fff;">{{ Str::limit($pg->title ?? 'N/A', 30) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" style="color: var(--text-muted); font-size: 13px;">No sub-pages cataloged yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
