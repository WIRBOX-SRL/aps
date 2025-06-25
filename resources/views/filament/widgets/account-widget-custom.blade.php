@php
    $user = filament()->auth()->user();
@endphp

<x-filament::card>
    <div class="flex items-center gap-4">
        <x-filament-panels::avatar.user size="lg" :user="$user" />
        <div>
            <div class="mt-2 text-primary-600 font-semibold">{{ $extra }}</div>
            <div class="text-sm text-gray-500">{{ auth()->user()->email }}</div>
        </div>
    </div>
</x-filament::card>
