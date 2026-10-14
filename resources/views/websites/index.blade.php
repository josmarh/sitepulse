
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="text-xl font-semibold text-gray-800">
                Websites
            </h2>

            <a href="{{ route('websites.create') }}"
               class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                Add Website
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="rounded-lg bg-green-50 p-4 text-sm text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-lg bg-red-50 p-4 text-sm text-red-700">
                    <ul class="list-inside list-disc">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="overflow-hidden rounded-xl bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Website</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Interval</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Last checked</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">Actions</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100">
                            @forelse ($websites as $website)
                                <tr>
                                    <td class="px-6 py-4">
                                        <div class="font-medium text-gray-900">
                                            <a href="{{ route('websites.show', $website) }}"
                                            class="font-medium text-blue-600 hover:text-blue-800">
                                                {{ $website->name }}
                                            </a>
                                        </div>
                                        <div class="mt-1 max-w-xs truncate text-sm text-gray-500">
                                            {{ $website->url }}
                                        </div>
                                    </td>

                                    <td class="px-6 py-4">
                                        @php
                                            $statusClasses = match ($website->current_status) {
                                                'up' => 'bg-green-100 text-green-800',
                                                'down' => 'bg-red-100 text-red-800',
                                                default => 'bg-gray-100 text-gray-700',
                                            };
                                        @endphp

                                        <span class="rounded-full px-3 py-1 text-xs font-medium {{ $statusClasses }}">
                                            {{ ucfirst($website->current_status) }}
                                        </span>

                                        @unless ($website->is_active)
                                            <span class="ml-1 text-xs text-gray-500">Paused</span>
                                        @endunless
                                    </td>

                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        {{ $website->check_interval_minutes }} min
                                    </td>

                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        {{ $website->last_checked_at?->diffForHumans() ?? 'Never' }}
                                    </td>

                                    <td class="px-6 py-4 text-right text-sm whitespace-nowrap">
                                        <a href="{{ route('websites.edit', $website) }}"
                                           class="font-medium text-blue-600 hover:text-blue-800">
                                            Edit
                                        </a>

                                        @if ($website->is_active)
                                            <form method="POST"
                                                action="{{ route('websites.check', $website) }}"
                                                class="ml-3 inline">
                                                @csrf

                                                <button type="submit"
                                                        class="font-medium text-blue-600 hover:text-blue-800">
                                                    Check now
                                                </button>
                                            </form>
                                        @endif

                                        <form method="POST"
                                              action="{{ route('websites.toggle', $website) }}"
                                              class="ml-3 inline">
                                            @csrf
                                            @method('PATCH')

                                            <button type="submit"
                                                    class="font-medium text-gray-600 hover:text-gray-900">
                                                {{ $website->is_active ? 'Pause' : 'Resume' }}
                                            </button>
                                        </form>

                                        <form method="POST"
                                              action="{{ route('websites.destroy', $website) }}"
                                              class="ml-3 inline"
                                              onsubmit="return confirm('Delete this website and its monitoring history?')">
                                            @csrf
                                            @method('DELETE')

                                            <button type="submit"
                                                    class="font-medium text-red-600 hover:text-red-800">
                                                Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center">
                                        <p class="font-medium text-gray-800">No websites yet</p>
                                        <p class="mt-2 text-sm text-gray-500">
                                            Add your first website to start building your monitoring dashboard.
                                        </p>
                                        <a href="{{ route('websites.create') }}"
                                           class="mt-4 inline-block font-semibold text-blue-600">
                                            Add your first website
                                        </a>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-gray-100 px-6 py-4">
                    {{ $websites->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
