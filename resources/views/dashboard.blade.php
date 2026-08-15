<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                🚜 {{ $farm->name }}
            </h2>
            <div class="flex items-center gap-4 text-sm font-medium">
                <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-3 py-1 text-amber-800">
                    📅 Day {{ $farm->current_day }}
                </span>
                <span class="inline-flex items-center gap-1 rounded-full bg-green-100 px-3 py-1 text-green-800">
                    💰 ${{ number_format((float) $farm->cash, 2) }}
                </span>
                <form method="POST" action="{{ route('turn.end') }}">
                    @csrf
                    <x-primary-button>End Day →</x-primary-button>
                </form>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Fields --}}
            <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">🌱 Fields</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    @foreach ($farm->fields as $field)
                        <div class="border rounded-lg p-4 flex flex-col justify-between {{ $field->isReady() ? 'border-green-400 bg-green-50' : 'border-gray-200' }}">
                            <div class="text-xs text-gray-500 mb-2">Plot #{{ $field->plot_number }}</div>

                            @if ($field->isEmpty())
                                <div class="text-3xl mb-2">🟫</div>
                                <p class="text-sm text-gray-500 mb-3">Empty field</p>
                                <form method="POST" action="{{ route('fields.plant', $field) }}" class="space-y-2">
                                    @csrf
                                    <select name="crop_type_id" class="w-full rounded-md border-gray-300 text-sm" required>
                                        <option value="">Select seed…</option>
                                        @foreach ($cropTypes as $crop)
                                            <option value="{{ $crop->id }}">{{ $crop->icon }} {{ $crop->name }} (${{ number_format((float) $crop->seed_price, 2) }})</option>
                                        @endforeach
                                    </select>
                                    <x-secondary-button type="submit" class="w-full justify-center">Plant</x-secondary-button>
                                </form>
                            @elseif ($field->isReady())
                                <div class="text-3xl mb-2">{{ $field->cropType->icon }}</div>
                                <p class="text-sm font-medium text-green-700 mb-3">{{ $field->cropType->name }} ready! (+{{ $field->harvestYield() }})</p>
                                <form method="POST" action="{{ route('fields.harvest', $field) }}">
                                    @csrf
                                    <x-primary-button type="submit" class="w-full justify-center">Harvest</x-primary-button>
                                </form>
                            @else
                                <div class="text-3xl mb-2">{{ $field->cropType->icon }}</div>
                                <p class="text-sm text-gray-500 mb-3">{{ $field->cropType->name }} growing… {{ $field->daysRemaining() }} day(s) left</p>
                                <div class="h-2 w-full bg-gray-200 rounded-full overflow-hidden">
                                    <div class="h-full bg-amber-400" style="width: {{ min(100, (int) round((($farm->current_day - $field->planted_on_day) / max(1, $field->effectiveGrowthDays())) * 100)) }}%"></div>
                                </div>
                            @endif
                        </div>
                    @endforeach

                    <div class="border border-dashed rounded-lg p-4 flex flex-col items-center justify-center text-center">
                        <div class="text-3xl mb-2">➕</div>
                        <p class="text-sm text-gray-500 mb-3">New field<br>${{ number_format($nextFieldCost, 2) }}</p>
                        <form method="POST" action="{{ route('fields.buy') }}">
                            @csrf
                            <x-secondary-button type="submit">Buy Field</x-secondary-button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Animals --}}
            <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">🐮 Animals</h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    @forelse ($farm->animals as $animal)
                        <div class="border rounded-lg p-4 {{ $animal->isFedToday() ? 'border-green-400 bg-green-50' : 'border-gray-200' }}">
                            <div class="text-3xl mb-1">{{ $animal->animalType->icon }}</div>
                            <p class="text-sm font-medium text-gray-800">{{ $animal->animalType->name }}</p>
                            <p class="text-xs text-gray-500 mb-3">
                                {{ $animal->isFedToday() ? 'Fed today ✅' : 'Not fed today' }}
                            </p>
                            <div class="flex gap-2">
                                @unless ($animal->isFedToday())
                                    <form method="POST" action="{{ route('animals.feed', $animal) }}" class="flex-1">
                                        @csrf
                                        <x-secondary-button type="submit" class="w-full justify-center text-xs">
                                            Feed (${{ number_format($animal->animalType->feed_cost, 2) }})
                                        </x-secondary-button>
                                    </form>
                                @endunless
                                <form method="POST" action="{{ route('animals.sell', $animal) }}">
                                    @csrf
                                    @method('DELETE')
                                    <x-secondary-button type="submit" class="text-xs">Sell</x-secondary-button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 col-span-full">No animals yet — buy one below.</p>
                    @endforelse
                </div>

                <h4 class="text-sm font-semibold text-gray-600 mb-2">Buy an animal</h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    @foreach ($animalTypes as $animalType)
                        <div class="border rounded-lg p-4">
                            <div class="text-3xl mb-1">{{ $animalType->icon }}</div>
                            <p class="text-sm font-medium text-gray-800">{{ $animalType->name }}</p>
                            <p class="text-xs text-gray-500 mb-3">
                                ${{ number_format($animalType->buy_price, 2) }} · makes {{ $animalType->produce_icon }} {{ $animalType->produce_name }} every {{ $animalType->produce_interval_days }}d
                            </p>
                            <form method="POST" action="{{ route('animals.buy') }}">
                                @csrf
                                <input type="hidden" name="animal_type_id" value="{{ $animalType->id }}">
                                <x-secondary-button type="submit" class="w-full justify-center">Buy</x-secondary-button>
                            </form>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Inventory / Market --}}
            <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">📦 Inventory &amp; Market</h3>

                @if ($farm->inventoryItems->isEmpty())
                    <p class="text-sm text-gray-500">Nothing harvested yet.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-gray-500 border-b">
                                    <th class="py-2 pr-4">Item</th>
                                    <th class="py-2 pr-4">Quantity</th>
                                    <th class="py-2 pr-4">Unit price</th>
                                    <th class="py-2 pr-4">Total</th>
                                    <th class="py-2"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($farm->inventoryItems as $item)
                                    @php($product = $item->product())
                                    <tr class="border-b last:border-0">
                                        <td class="py-2 pr-4">{{ $product['icon'] }} {{ $product['name'] }}</td>
                                        <td class="py-2 pr-4">{{ $item->quantity }}</td>
                                        <td class="py-2 pr-4">${{ number_format($product['sell_price'], 2) }}</td>
                                        <td class="py-2 pr-4">${{ number_format($product['sell_price'] * $item->quantity, 2) }}</td>
                                        <td class="py-2">
                                            <form method="POST" action="{{ route('inventory.sell', $item) }}">
                                                @csrf
                                                <input type="hidden" name="quantity" value="{{ $item->quantity }}">
                                                <x-secondary-button type="submit" class="text-xs">Sell all</x-secondary-button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            {{-- Machinery --}}
            <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">🚜 Machinery</h3>

                @php($ownedIds = $farm->machinery->pluck('machinery_type_id'))

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    @foreach ($machineryTypes as $machineryType)
                        @php($owned = $ownedIds->contains($machineryType->id))
                        <div class="border rounded-lg p-4 {{ $owned ? 'border-green-400 bg-green-50' : 'border-gray-200' }}">
                            <div class="text-3xl mb-1">{{ $machineryType->icon }}</div>
                            <p class="text-sm font-medium text-gray-800">{{ $machineryType->name }}</p>
                            <p class="text-xs text-gray-500 mb-3">{{ $machineryType->description }}</p>
                            @if ($owned)
                                <span class="text-xs font-medium text-green-700">Owned ✅</span>
                            @else
                                <form method="POST" action="{{ route('machinery.buy') }}">
                                    @csrf
                                    <input type="hidden" name="machinery_type_id" value="{{ $machineryType->id }}">
                                    <x-secondary-button type="submit" class="w-full justify-center">
                                        Buy (${{ number_format($machineryType->price, 2) }})
                                    </x-secondary-button>
                                </form>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
