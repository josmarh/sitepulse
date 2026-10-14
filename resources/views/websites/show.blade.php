<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">

        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <a href="{{ route('websites.index') }}"
                class="text-sm text-blue-600 hover:text-blue-800">
                    &larr; Back to websites
                </a>

                <h1 class="mt-2 text-2xl font-bold text-gray-900">
                    {{ $website->name }}
                </h1>

                <a href="{{ $website->url }}"
                target="_blank"
                rel="noopener noreferrer"
                class="mt-1 inline-block break-all text-sm text-blue-600">
                    {{ $website->url }}
                </a>
            </div>

            <div class="flex flex-wrap gap-3">
                @if ($website->is_active)
                    <form method="POST"
                        action="{{ route('websites.check', $website) }}">
                        @csrf
                        <button type="submit"
                                class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                            Check now
                        </button>
                    </form>
                @endif

                <a href="{{ route('websites.edit', $website) }}"
                class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Edit website
                </a>
            </div>
        </div>

        @if (session('success'))
            <div class="rounded-lg bg-green-50 p-4 text-sm text-green-800">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="rounded-lg bg-red-50 p-4 text-sm text-red-800">
                {{ session('error') }}
            </div>
        @endif


        <div class="flex flex-wrap items-end justify-between gap-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">
                    Monitoring overview
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    View monitoring metrics and response times for a selected period.
                </p>
            </div>

            <form method="GET"
                action="{{ route('websites.show', $website) }}"
                class="flex items-end gap-3">
                <div>
                    <label for="period" class="mb-1 block text-sm font-medium text-gray-700">
                        Date range
                    </label>

                    <select id="period"
                            name="period"
                            onchange="this.form.submit()"
                            class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="24h" @selected($period === '24h')>
                            Last 24 hours
                        </option>
                        <option value="7d" @selected($period === '7d')>
                            Last 7 days
                        </option>
                        <option value="30d" @selected($period === '30d')>
                            Last 30 days
                        </option>
                    </select>
                </div>

                <noscript>
                    <button type="submit"
                            class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white">
                        Apply
                    </button>
                </noscript>
            </form>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-gray-500">Current status</p>

                <p class="mt-2 text-xl font-semibold
                    {{ $website->current_status === 'up'
                        ? 'text-green-600'
                        : ($website->current_status === 'down'
                            ? 'text-red-600'
                            : 'text-gray-500') }}">
                    {{ ucfirst($website->current_status) }}
                </p>

                @unless ($website->is_active)
                    <p class="mt-1 text-xs text-gray-500">
                        Monitoring is paused
                    </p>
                @endunless
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-gray-500">Recorded check success</p>

                <p class="mt-2 text-xl font-semibold text-gray-900">
                    {{ $checkSuccessRate !== null
                    ? number_format($checkSuccessRate, 2) . '%'
                    : '—' }}
                </p>

                <p class="mt-1 text-xs text-gray-500">
                    {{ $successfulChecks }} successful of {{ $totalChecks }} checks
                </p>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-gray-500">Average response time</p>

                <p class="mt-2 text-xl font-semibold text-gray-900">
                    {{ $summary->average_response_time_ms !== null
                        ? number_format((float) $summary->average_response_time_ms) . ' ms'
                        : '—' }}
                </p>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-gray-500">Check interval</p>

                <p class="mt-2 text-xl font-semibold text-gray-900">
                    {{ $website->check_interval_minutes }} minutes
                </p>

                <p class="mt-1 text-xs text-gray-500">
                    Timeout: {{ $website->timeout_seconds }} seconds
                </p>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-gray-500">Failed checks</p>

                <p class="mt-2 text-xl font-semibold {{ $failedChecks > 0 ? 'text-red-600' : 'text-gray-900' }}">
                    {{ number_format($failedChecks) }}
                </p>

                <p class="mt-1 text-xs text-gray-500">
                    During the selected period
                </p>
            </div>

        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="mb-5">
                <h2 class="text-lg font-semibold text-gray-900">
                    Response-time trend
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    Response time in milliseconds for recorded checks during the selected period.
                </p>
            </div>

            @if (count($chartLabels) > 0)
                <div class="relative h-72"
                    id="responseTimeChartContainer">
                    <canvas
                        id="responseTimeChart"
                        data-labels="{{ json_encode($chartLabels) }}"
                        data-values="{{ json_encode($chartResponseTimes) }}"
                        role="img"
                        aria-label="Response-time trend chart">
                    </canvas>
                </div>
            @else
                <div class="flex min-h-48 items-center justify-center rounded-lg bg-gray-50 px-4 text-center">
                    <p class="text-sm text-gray-500">
                        No response-time data is available for this period yet.
                    </p>
                </div>
            @endif
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-semibold text-gray-900">
                Monitoring schedule
            </h2>

            <dl class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-sm text-gray-500">Last checked</dt>
                    <dd class="mt-1 font-medium text-gray-900">
                        {{ $website->last_checked_at?->format('M j, Y g:i:s A') ?? 'Never' }}
                    </dd>
                </div>

                <div>
                    <dt class="text-sm text-gray-500">Next scheduled check</dt>
                    <dd class="mt-1 font-medium text-gray-900">
                        {{ $website->is_active
                            ? ($website->next_check_at?->format('M j, Y g:i:s A') ?? 'Pending scheduling')
                            : 'Paused' }}
                    </dd>
                </div>
            </dl>
        </div>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-200 px-5 py-4">
                <h2 class="text-lg font-semibold text-gray-900">
                    Monitoring history
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    Most recent uptime checks first.
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-gray-500">
                                Checked at
                            </th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-gray-500">
                                Result
                            </th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-gray-500">
                                HTTP status
                            </th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-gray-500">
                                Response time
                            </th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-gray-500">
                                Details
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-200 bg-white">
                        @forelse ($checks as $check)
                            <tr>
                                <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-600">
                                    {{ $check->checked_at?->format('M j, Y g:i:s A') ?? '—' }}
                                </td>

                                <td class="px-5 py-4">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium
                                        {{ $check->status === 'up'
                                            ? 'bg-green-100 text-green-800'
                                            : ($check->status === 'down'
                                                ? 'bg-red-100 text-red-800'
                                                : 'bg-gray-100 text-gray-700') }}">
                                        {{ ucfirst($check->status) }}
                                    </span>
                                </td>

                                <td class="px-5 py-4 text-sm text-gray-600">
                                    {{ $check->http_status_code ?? '—' }}
                                </td>

                                <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-600">
                                    {{ $check->response_time_ms !== null
                                        ? number_format($check->response_time_ms) . ' ms'
                                        : '—' }}
                                </td>

                                <td class="max-w-sm px-5 py-4 text-sm text-gray-600">
                                    {{ $check->error_message ?: '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5"
                                    class="px-5 py-10 text-center text-sm text-gray-500">
                                    No monitoring checks have been recorded yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($checks->hasPages())
                <div class="border-t border-gray-200 px-5 py-4">
                    {{ $checks->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
