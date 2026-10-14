
@php
    $editing = isset($website);
@endphp

<div class="space-y-6">
    <div>
        <label for="name" class="block text-sm font-medium text-gray-700">
            Website name
        </label>
        <input id="name" name="name" type="text" required maxlength="150"
               value="{{ old('name', $website->name ?? '') }}"
               placeholder="My Business Website"
               class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
        @error('name')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="url" class="block text-sm font-medium text-gray-700">
            Website URL
        </label>
        <input id="url" name="url" type="url" required maxlength="2048"
               value="{{ old('url', $website->url ?? '') }}"
               placeholder="https://example.com"
               class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
        <p class="mt-1 text-xs text-gray-500">
            Enter the full public HTTP or HTTPS URL.
        </p>
        @error('url')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
        <div>
            <label for="check_interval_minutes" class="block text-sm font-medium text-gray-700">
                Check interval
            </label>
            <select id="check_interval_minutes" name="check_interval_minutes" required
                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm">
                @foreach ([1, 5, 10, 15, 30, 60] as $minutes)
                    <option value="{{ $minutes }}"
                        @selected((int) old('check_interval_minutes', $website->check_interval_minutes ?? 5) === $minutes)>
                        Every {{ $minutes }} minute(s)
                    </option>
                @endforeach
            </select>
            @error('check_interval_minutes')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="timeout_seconds" class="block text-sm font-medium text-gray-700">
                Request timeout
            </label>
            <select id="timeout_seconds" name="timeout_seconds" required
                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm">
                @foreach ([5, 10, 15, 20, 30] as $seconds)
                    <option value="{{ $seconds }}"
                        @selected((int) old('timeout_seconds', $website->timeout_seconds ?? 10) === $seconds)>
                        {{ $seconds }} seconds
                    </option>
                @endforeach
            </select>
            @error('timeout_seconds')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div>
        <input type="hidden" name="is_active" value="0">

        <label class="inline-flex items-center gap-2">
            <input type="checkbox" name="is_active" value="1"
                   @checked((bool) old('is_active', $website->is_active ?? true))
                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
            <span class="text-sm text-gray-700">Enable monitoring</span>
        </label>
        @error('is_active')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="flex items-center justify-end gap-3 border-t pt-5">
        <a href="{{ route('websites.index') }}"
           class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
            Cancel
        </a>

        <button type="submit"
                class="rounded-lg bg-blue-600 px-5 py-2 text-sm font-semibold text-white hover:bg-blue-700">
            {{ $editing ? 'Save Changes' : 'Add Website' }}
        </button>
    </div>
</div>
