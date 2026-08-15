<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            🌍 Platform Stats
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6">
                <p class="text-sm text-gray-500 mb-6">A snapshot of every farm on Farm Sim, combined.</p>

                <div class="grid grid-cols-2 sm:grid-cols-3 gap-6 text-center">
                    <div>
                        <p class="text-3xl font-semibold text-gray-800">{{ $stats['farm_count'] }}</p>
                        <p class="text-xs text-gray-500">Farms</p>
                    </div>
                    <div>
                        <p class="text-3xl font-semibold text-green-700">${{ number_format($stats['total_cash'], 0) }}</p>
                        <p class="text-xs text-gray-500">Combined cash</p>
                    </div>
                    <div>
                        <p class="text-3xl font-semibold text-amber-700">{{ $stats['total_harvests'] }}</p>
                        <p class="text-xs text-gray-500">Harvests made</p>
                    </div>
                    <div>
                        <p class="text-3xl font-semibold text-gray-800">{{ $stats['total_animals'] }}</p>
                        <p class="text-xs text-gray-500">Animals raised</p>
                    </div>
                    <div>
                        <p class="text-3xl font-semibold text-purple-700">⭐ {{ $stats['highest_level_farm']?->level ?? 1 }}</p>
                        <p class="text-xs text-gray-500">Top level ({{ $stats['highest_level_farm']?->user?->name ?? '—' }})</p>
                    </div>
                    <div>
                        <p class="text-3xl font-semibold text-gray-800">{{ $stats['top_crop'] ?? '—' }}</p>
                        <p class="text-xs text-gray-500">Most-harvested crop</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
