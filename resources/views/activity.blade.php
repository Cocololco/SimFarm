<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            📜 Activity — {{ $farm->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Cash over time --}}
            <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-1">Cash balance</h3>
                <p class="text-xs text-gray-500 mb-4">Last {{ count($chart['points_data'] ?? []) }} activity events</p>

                @if ($chart)
                    <div class="flex justify-between text-xs text-gray-400 mb-1">
                        <span>${{ number_format($chart['min'], 0) }}</span>
                        <span>${{ number_format($chart['max'], 0) }}</span>
                    </div>
                    <svg viewBox="0 0 {{ $chart['width'] }} {{ $chart['height'] }}" class="w-full h-40" preserveAspectRatio="none" role="img" aria-label="Cash balance over recent activity">
                        <polyline points="{{ $chart['area'] }}" fill="#6366f114" stroke="none" />
                        <polyline points="{{ $chart['points'] }}" fill="none" stroke="#6366f1" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" vector-effect="non-scaling-stroke" />
                        @foreach ($chart['points_data'] as $point)
                            <circle cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="3" fill="#6366f1">
                                <title>Day {{ $point['day'] }} — {{ $point['label'] }}: ${{ number_format($point['balance'], 2) }}</title>
                            </circle>
                        @endforeach
                    </svg>
                @else
                    <p class="text-sm text-gray-500">Not enough activity yet to chart.</p>
                @endif
            </div>

            {{-- Full history --}}
            <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Full history</h3>

                @if ($transactions->isEmpty())
                    <p class="text-sm text-gray-500">Nothing has happened yet.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-gray-500 border-b">
                                    <th class="py-2 pr-4">Day</th>
                                    <th class="py-2 pr-4">Type</th>
                                    <th class="py-2 pr-4">Description</th>
                                    <th class="py-2 pr-4">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($transactions as $transaction)
                                    <tr class="border-b last:border-0">
                                        <td class="py-2 pr-4 text-gray-500">{{ $transaction->day }}</td>
                                        <td class="py-2 pr-4 text-gray-500 text-xs uppercase tracking-wide">{{ str_replace('_', ' ', $transaction->type) }}</td>
                                        <td class="py-2 pr-4">{{ $transaction->description }}</td>
                                        <td class="py-2 pr-4">
                                            @if (! is_null($transaction->amount))
                                                <span class="font-medium {{ $transaction->amount >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                                    {{ $transaction->amount >= 0 ? '+' : '-' }}${{ number_format(abs($transaction->amount), 2) }}
                                                </span>
                                            @else
                                                <span class="text-gray-300">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $transactions->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
