<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            🚜 {{ $farm->name }} <span class="text-sm font-normal text-gray-500">— {{ $farm->user->name }}</span>
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6">
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-center">
                    <div>
                        <p class="text-2xl font-semibold text-purple-700">⭐ {{ $farm->level }}</p>
                        <p class="text-xs text-gray-500">Level</p>
                    </div>
                    <div>
                        <p class="text-2xl font-semibold text-amber-700">📅 {{ $farm->current_day }}</p>
                        <p class="text-xs text-gray-500">Day</p>
                    </div>
                    <div>
                        <p class="text-2xl font-semibold text-green-700">${{ number_format($farm->netWorth(), 0) }}</p>
                        <p class="text-xs text-gray-500">Net worth</p>
                    </div>
                    <div>
                        <p class="text-2xl font-semibold text-gray-700">{{ $farm->fields->count() }}</p>
                        <p class="text-xs text-gray-500">Fields</p>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">🐮 Animals</h3>
                @if ($farm->animals->isEmpty())
                    <p class="text-sm text-gray-500">No animals.</p>
                @else
                    <div class="flex flex-wrap gap-3">
                        @foreach ($farm->animals as $animal)
                            <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-3 py-1 text-sm">
                                {{ $animal->animalType->icon }} {{ $animal->animalType->name }}
                            </span>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">🚜 Machinery</h3>
                @if ($farm->machinery->isEmpty())
                    <p class="text-sm text-gray-500">No machinery yet.</p>
                @else
                    <div class="flex flex-wrap gap-3">
                        @foreach ($farm->machinery as $machinery)
                            <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-3 py-1 text-sm">
                                {{ $machinery->machineryType->icon }} {{ $machinery->machineryType->name }}
                            </span>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">🏆 Achievements</h3>
                @if ($farm->achievements->isEmpty())
                    <p class="text-sm text-gray-500">No achievements unlocked yet.</p>
                @else
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
                        @foreach ($farm->achievements as $achievement)
                            <div class="border border-amber-300 bg-amber-50 rounded-lg p-3 text-center" title="{{ $achievement->description }}">
                                <div class="text-2xl mb-1">{{ $achievement->icon }}</div>
                                <p class="text-xs font-medium text-gray-700">{{ $achievement->name }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <a href="{{ route('leaderboard.index') }}" class="text-sm text-indigo-600 hover:underline">← Back to leaderboard</a>
        </div>
    </div>
</x-app-layout>
