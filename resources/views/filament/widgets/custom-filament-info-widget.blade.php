<x-filament::widget>
    <x-filament::card>
        <h2 class="text-lg font-bold">Contact Suport</h2>
        @forelse($superadmins as $admin)
            <div class="mb-2">
                <strong>Name:</strong> {{ $admin->name }}<br>
                <strong>Email:</strong> <a href="mailto:{{ $admin->email }}">{{ $admin->email }}</a><br>
                @if(!empty($admin->phone))
                    <strong>Telefon:</strong> {{ $admin->phone }}
                @endif
            </div>
        @empty
            <div>No superadmins available.</div>
        @endforelse
    </x-filament::card>
</x-filament::widget>
