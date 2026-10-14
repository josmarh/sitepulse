
<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Add Website</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <div class="rounded-xl bg-white p-6 shadow-sm sm:p-8">
                <form method="POST" action="{{ route('websites.store') }}">
                    @csrf
                    @include('websites._form')
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
