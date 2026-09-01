<?php

namespace App\Http\Controllers\Web;

use App\Domain\Integrations\Google\Models\GoogleApiKey;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminGoogleKeyWebController extends Controller
{
    public function index(Request $request): View
    {
        $query = GoogleApiKey::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('api_key', 'LIKE', "%{$search}%")
                ->orWhere('cx', 'LIKE', "%{$search}%");
        }

        if ($request->filled('status')) {
            $status = $request->input('status');
            if ($status === 'active') {
                $query->where('is_active', true)->where('is_exhausted', false);
            } elseif ($status === 'exhausted') {
                $query->where('is_exhausted', true);
            } elseif ($status === 'disabled') {
                $query->where('is_active', false);
            }
        }

        $keys = $query->orderBy('updated_at', 'desc')->paginate(15);

        $stats = [
            'total_keys' => GoogleApiKey::count(),
            'active_keys' => GoogleApiKey::where('is_active', true)->where('is_exhausted', false)->count(),
            'exhausted_keys' => GoogleApiKey::where('is_exhausted', true)->count(),
            'disabled_keys' => GoogleApiKey::where('is_active', false)->count(),
            'total_requests_today' => GoogleApiKey::sum('requests_today'),
        ];

        return view('admin.google-keys', compact('keys', 'stats'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'keys_input' => 'required|string',
            'cx' => 'nullable|string|max:255',
            'daily_limit' => 'nullable|integer|min:1|max:100',
        ]);

        $input = $request->input('keys_input');
        $cx = $request->input('cx');
        $limit = (int) $request->input('daily_limit', 95);

        $lines = explode("\n", str_replace("\r", "", $input));
        $rawKeys = [];

        foreach ($lines as $line) {
            $parts = explode(',', $line);
            foreach ($parts as $p) {
                $trimmed = trim($p);
                if (!empty($trimmed)) {
                    $rawKeys[] = $trimmed;
                }
            }
        }

        $rawKeys = array_unique($rawKeys);
        $count = 0;

        foreach ($rawKeys as $key) {
            GoogleApiKey::updateOrCreate(
                ['api_key' => $key],
                [
                    'cx' => $cx,
                    'daily_limit' => $limit,
                    'is_active' => true,
                ]
            );
            $count++;
        }

        return redirect()->back()->with('success', "Successfully imported {$count} Google Search API keys!");
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $keyModel = GoogleApiKey::findOrFail($id);

        $keyModel->update([
            'is_active' => $request->has('is_active'),
            'cx' => $request->input('cx', $keyModel->cx),
            'daily_limit' => (int) $request->input('daily_limit', $keyModel->daily_limit),
        ]);

        return redirect()->back()->with('success', "API Key updated successfully.");
    }

    public function resetQuota(int $id): RedirectResponse
    {
        $keyModel = GoogleApiKey::findOrFail($id);
        $keyModel->update([
            'requests_today' => 0,
            'is_exhausted' => false,
        ]);

        return redirect()->back()->with('success', "Daily quota reset for API key prefix " . substr($keyModel->api_key, 0, 6) . "...");
    }

    public function resetAllQuotas(): RedirectResponse
    {
        $updated = GoogleApiKey::where('is_active', true)->update([
            'requests_today' => 0,
            'is_exhausted' => false,
        ]);

        return redirect()->back()->with('success', "All daily quotas reset for {$updated} API keys!");
    }

    public function destroy(int $id): RedirectResponse
    {
        $keyModel = GoogleApiKey::findOrFail($id);
        $keyModel->delete();

        return redirect()->back()->with('success', "API Key deleted successfully.");
    }
}
