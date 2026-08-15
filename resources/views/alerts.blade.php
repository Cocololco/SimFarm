<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            🔔 Alerts — {{ $farm->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6">
                <p class="text-sm text-gray-500 mb-4">Lost animals, full storage, gifts received, quest rewards, random events, and loan interest — the stuff worth noticing, filtered out of the full activity log.</p>

                @if ($alerts->isEmpty())
                    <p class="text-sm text-gray-500">Nothing to report yet.</p>
                @else
                    <ul class="divide-y">
                        @foreach ($alerts as $alert)
                            <li class="py-3 flex items-center justify-between text-sm">
                                <span class="text-gray-700">
                                    <span class="text-xs text-gray-400">Day {{ $alert->day }}</span>
                                    {{ $alert->description }}
                                </span>
                                @if (! is_null($alert->amount))
                                    <span class="font-medium {{ $alert->amount >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                        {{ $alert->amount >= 0 ? '+' : '-' }}${{ number_format(abs($alert->amount), 2) }}
                                    </span>
                                @endif
                            </li>
                        @endforeach
                    </ul>

                    <div class="mt-4">
                        {{ $alerts->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
