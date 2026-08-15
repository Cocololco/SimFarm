<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            🏅 Leaderboard
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6">
                <p class="text-sm text-gray-500 mb-4">Ranked by net worth — cash plus the resale value of animals, machinery, and inventory, minus any debt.</p>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500 border-b">
                                <th class="py-2 pr-4">#</th>
                                <th class="py-2 pr-4">Farmer</th>
                                <th class="py-2 pr-4">Farm</th>
                                <th class="py-2 pr-4">Level</th>
                                <th class="py-2 pr-4">Net worth</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rankings as $index => $row)
                                <tr class="border-b last:border-0 {{ $row['farm']->user_id === auth()->id() ? 'bg-indigo-50' : '' }}">
                                    <td class="py-2 pr-4 text-gray-500">
                                        {{ $index === 0 ? '🥇' : ($index === 1 ? '🥈' : ($index === 2 ? '🥉' : $index + 1)) }}
                                    </td>
                                    <td class="py-2 pr-4">{{ $row['farm']->user->name }}</td>
                                    <td class="py-2 pr-4 text-gray-500">
                                        <a href="{{ route('farms.show', $row['farm']) }}" class="hover:underline hover:text-indigo-600">{{ $row['farm']->name }}</a>
                                    </td>
                                    <td class="py-2 pr-4">⭐ {{ $row['farm']->level }}</td>
                                    <td class="py-2 pr-4 font-medium">${{ number_format($row['net_worth'], 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
