<?php

namespace App\Http\Controllers;

use App\Models\Website;
use App\Models\MonitoringCheck;
use App\Support\WebsiteUrlValidator;
use App\Services\Monitoring\UptimeMonitoringService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Illuminate\Support\Carbon;

class WebsiteController extends Controller
{
    public function __construct(
        private readonly WebsiteUrlValidator $urlValidator
    ) {}

    public function index(): View
    {
        $websites = Website::query()
            ->orderBy('name')
            ->paginate(15);

        return view('websites.index', compact('websites'));
    }

    public function create(): View
    {
        return view('websites.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);

        $data['host'] = $this->urlValidator->validate($data['url']);
        $data['current_status'] = 'unknown';
        $data['next_check_at'] = now();

        Website::create($data);

        return redirect()
            ->route('websites.index')
            ->with('success', 'Website added successfully.');
    }

    public function show(Request $request, Website $website): View
    {
        $validated = $request->validate([
            'period' => ['nullable', 'in:24h,7d,30d'],
        ]);

        $period = $validated['period'] ?? '7d';

        $periodStart = match ($period) {
            '24h' => now()->subHours(24),
            '30d' => now()->subDays(30),
            default => now()->subDays(7),
        };

        $periodQuery = $website->monitoringChecks()
            ->where('check_type', 'uptime')
            ->whereNotNull('checked_at')
            ->where('checked_at', '>=', $periodStart);

        $summary = (clone $periodQuery)
            ->selectRaw('COUNT(*) as total_checks')
            ->selectRaw("SUM(CASE WHEN status = 'up' THEN 1 ELSE 0 END) as successful_checks")
            ->selectRaw("SUM(CASE WHEN status = 'down' THEN 1 ELSE 0 END) as failed_checks")
            ->selectRaw('AVG(response_time_ms) as average_response_time_ms')
            ->first();

        $totalChecks = (int) ($summary->total_checks ?? 0);
        $successfulChecks = (int) ($summary->successful_checks ?? 0);
        $failedChecks = (int) ($summary->failed_checks ?? 0);

        $checkSuccessRate = $totalChecks > 0
            ? round(($successfulChecks / $totalChecks) * 100, 2)
            : null;

        // Backward compatibility with the existing Blade view.
        $uptimePercentage = $checkSuccessRate;

        $responseTimeChecks = (clone $periodQuery)
            ->whereNotNull('response_time_ms')
            ->orderBy('checked_at')
            ->get(['checked_at', 'response_time_ms']);

        $chartLabels = $responseTimeChecks->map(
            fn ($check) => Carbon::parse($check->checked_at)->format('M j, H:i')
        )->values();

        $chartResponseTimes = $responseTimeChecks->map(
            fn ($check) => (int) $check->response_time_ms
        )->values();

        // Keep the full monitoring history separate from the selected summary period.
        $checks = $website->monitoringChecks()
            ->where('check_type', 'uptime')
            ->latest('checked_at')
            ->paginate(20)
            ->withQueryString();

        return view('websites.show', compact(
            'website',
            'checks',
            'period',
            'totalChecks',
            'successfulChecks',
            'failedChecks',
            'checkSuccessRate',
            'summary',
            'chartLabels',
            'chartResponseTimes',
            'uptimePercentage'
        ));
    }

    public function edit(Website $website): View
    {
        return view('websites.edit', compact('website'));
    }

    public function update(
        Request $request,
        Website $website
    ): RedirectResponse {
        $data = $this->validatedData($request);

        $data['host'] = $this->urlValidator->validate($data['url']);

        if (! $data['is_active']) {
            $data['next_check_at'] = null;
        } elseif (! $website->is_active) {
            $data['next_check_at'] = now();
        }

        $website->update($data);

        return redirect()
            ->route('websites.index')
            ->with('success', 'Website updated successfully.');
    }

    public function destroy(Website $website): RedirectResponse
    {
        $website->delete();

        return redirect()
            ->route('websites.index')
            ->with('success', 'Website deleted successfully.');
    }

    public function toggle(Website $website): RedirectResponse
    {
        $website->is_active = ! $website->is_active;
        $website->next_check_at = $website->is_active ? now() : null;
        $website->save();

        return redirect()
            ->route('websites.index')
            ->with(
                'success',
                $website->is_active
                    ? 'Monitoring resumed.'
                    : 'Monitoring paused.'
            );
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'url' => [
                'required',
                'string',
                'max:2048',
                'url:http,https',
            ],
            'check_interval_minutes' => [
                'required',
                'integer',
                Rule::in([1, 5, 10, 15, 30, 60]),
            ],
            'timeout_seconds' => [
                'required',
                'integer',
                Rule::in([5, 10, 15, 20, 30]),
            ],
            'is_active' => ['required', 'boolean'],
        ]);
    }

    public function checkNow(
        Website $website,
        UptimeMonitoringService $service
    ): RedirectResponse {
        if (! $website->is_active) {
            return redirect()
                ->route('websites.index')
                ->with('error', 'Resume monitoring before running a check.');
        }

        $check = $service->check($website);

        return redirect()
            ->route('websites.index')
            ->with(
                $check->status === 'up' ? 'success' : 'error',
                $check->status === 'up'
                    ? "Website is reachable ({$check->response_time_ms} ms)."
                    : 'The website check failed. Review its monitoring history.'
            );
    }
}
