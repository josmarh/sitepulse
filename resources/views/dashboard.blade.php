
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">
                    SitePulse Dashboard
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    Monitor your websites from one place.
                </p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-xl bg-white p-6 shadow-sm">
                    <p class="text-sm text-gray-500">Total websites</p>
                    <p class="mt-2 text-3xl font-semibold">
                        {{ $totalWebsites }}
                    </p>
                </div>

                <div class="rounded-xl bg-white p-6 shadow-sm">
                    <p class="text-sm text-gray-500">Active monitoring</p>
                    <p class="mt-2 text-3xl font-semibold text-blue-600">
                        {{ $activeWebsites }}
                    </p>
                </div>

                <div class="rounded-xl bg-white p-6 shadow-sm">
                    <p class="text-sm text-gray-500">Websites down</p>
                    <p class="mt-2 text-3xl font-semibold text-red-600">
                        {{ $downWebsites }}
                    </p>
                </div>

                <div class="rounded-xl bg-white p-6 shadow-sm">
                    <p class="text-sm text-gray-500">Awaiting first check</p>
                    <p class="mt-2 text-3xl font-semibold text-gray-600">
                        {{ $unknownWebsites }}
                    </p>
                </div>
            </div>

            <div class="overflow-hidden rounded-xl bg-white shadow-sm">
                <div class="flex items-center justify-between border-b p-6">
                    <h3 class="font-semibold text-gray-800">
                        Recently added websites
                    </h3>
                </div>

                @forelse ($recentWebsites as $website)
                    <div class="flex items-center justify-between border-b p-5 last:border-b-0">
                        <div>
                            <p class="font-medium text-gray-800">
                                {{ $website->name }}
                            </p>
                            <p class="mt-1 text-sm text-gray-500">
                                {{ $website->url }}
                            </p>
                        </div>

                        <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium">
                            {{ ucfirst($website->current_status) }}
                        </span>
                    </div>
                @empty
                    <div class="p-10 text-center">
                        <p class="font-medium text-gray-700">
                            No websites added yet
                        </p>
                        <p class="mt-2 text-sm text-gray-500">
                            Your monitored websites will appear here.
                        </p>
                    </div>
                @endforelse
            </div>

        </div>
    </div>
</x-app-layout>
