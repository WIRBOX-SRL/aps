<x-filament::card>
    <div class="text-lg font-bold mb-2">Ads per user</div>
    <ul>
        @foreach ($users as $user)
            <li class="flex justify-between border-b py-1">
                <span>{{ $user['name'] }}</span>
                <span class="font-mono">{{ $user['count'] }}</span>
            </li>
        @endforeach
    </ul>
</x-filament::card>
