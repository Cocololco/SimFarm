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

                <form method="GET" action="{{ route('leaderboard.index') }}" class="flex flex-wrap items-end gap-2 mb-4">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Search</label>
                        <input type="text" name="search" value="{{ $search }}" placeholder="Farmer or farm name…" class="rounded-md border-gray-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Sort by</label>
                        <select name="sort" class="rounded-md border-gray-300 text-sm">
                            <option value="net_worth" @selected($sort === 'net_worth')>Net worth</option>
                            <option value="level" @selected($sort === 'level')>Level</option>
                        </select>
                    </div>
                    <x-secondary-button type="submit">Apply</x-secondary-button>
                    @if ($search !== '' || $sort !== 'net_worth')
                        <a href="{{ route('leaderboard.index') }}" class="text-sm text-gray-500 hover:underline">Reset</a>
                    @endif
                </form>

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
                                <tr class="border-b last:border-0 {{ $row['farm']->user_id === auth()->id() ? 'bg-emerald-50' : '' }}">
                                    <td class="py-2 pr-4 text-gray-500">
                                        {{ $index === 0 ? '🥇' : ($index === 1 ? '🥈' : ($index === 2 ? '🥉' : $index + 1)) }}
                                    </td>
                                    <td class="py-2 pr-4">{{ $row['farm']->user->name }}</td>
                                    <td class="py-2 pr-4 text-gray-500">
                                        <a href="{{ route('farms.show', $row['farm']) }}" class="hover:underline hover:text-emerald-600">{{ $row['farm']->name }}</a>
                                    </td>
                                    <td class="py-2 pr-4">⭐ {{ $row['farm']->level }}</td>
                                    <td class="py-2 pr-4 font-medium">${{ number_format($row['net_worth'], 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @if ($rankings->isEmpty())
                        <p class="text-sm text-gray-500 py-4">No farms match "{{ $search }}".</p>
                    @endif
                </div>
            </div>

            <p class="mt-4 text-sm text-gray-500">
                Curious about the whole community? Check the <a href="{{ route('stats.index') }}" class="text-emerald-600 hover:underline">platform stats</a>.
            </p>
        </div>
    </div>
</x-app-layout>
