<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                🚜 {{ $farm->name }}
            </h2>
            <div class="flex flex-wrap items-center gap-3 text-sm font-medium">
                <span class="inline-flex items-center gap-1 rounded-full bg-purple-100 px-3 py-1 text-purple-800">
                    ⭐ Level {{ $farm->level }}
                </span>
                <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-3 py-1 text-amber-800">
                    📅 Day {{ $farm->current_day }}
                </span>
                <span class="inline-flex items-center gap-1 rounded-full bg-sky-100 px-3 py-1 text-sky-800 capitalize">
                    {{ \App\Models\Farm::seasonIcon($farm->currentSeason()) }} {{ $farm->currentSeason() }}
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
        <div class="mt-3 flex flex-wrap gap-6">
            <div class="max-w-xs w-full sm:w-56">
                <div class="flex justify-between text-xs text-gray-500 mb-1">
                    <span>XP</span>
                    <span>{{ $farm->xpIntoLevel() }} / {{ \App\Models\Farm::XP_PER_LEVEL }}</span>
                </div>
                <div class="h-1.5 w-full bg-gray-200 rounded-full overflow-hidden">
                    <div class="h-full bg-purple-400" style="width: {{ (int) round(($farm->xpIntoLevel() / \App\Models\Farm::XP_PER_LEVEL) * 100) }}%"></div>
                </div>
            </div>
            @if ($nextMilestone)
                <div class="max-w-xs w-full sm:w-56">
                    <div class="flex justify-between text-xs text-gray-500 mb-1">
                        <span>💰 Next milestone</span>
                        <span>${{ number_format($nextMilestone['progress'], 0) }} / ${{ number_format($nextMilestone['threshold'], 0) }}</span>
                    </div>
                    <div class="h-1.5 w-full bg-gray-200 rounded-full overflow-hidden">
                        <div class="h-full bg-emerald-400" style="width: {{ min(100, (int) round(($nextMilestone['progress'] / $nextMilestone['threshold']) * 100)) }}%"></div>
                    </div>
                </div>
            @endif
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('celebrate'))
                <style>
                    @keyframes celebrate-pop {
                        0% { transform: scale(0.85); opacity: 0; }
                        60% { transform: scale(1.03); opacity: 1; }
                        100% { transform: scale(1); opacity: 1; }
                    }
                    @keyframes celebrate-emoji {
                        0% { transform: translateY(0) rotate(0deg); opacity: 1; }
                        100% { transform: translateY(-24px) rotate(15deg); opacity: 0; }
                    }
                    .celebrate-banner { animation: celebrate-pop 0.4s ease-out; }
                    .celebrate-emoji { display: inline-block; animation: celebrate-emoji 1.1s ease-in forwards; }
                </style>
            @endif

            @if (session('status'))
                <div class="rounded-md {{ session('celebrate') ? 'celebrate-banner bg-amber-50 border-amber-300' : 'bg-green-50 border-green-200' }} border px-4 py-3 text-sm {{ session('celebrate') ? 'text-amber-900' : 'text-green-800' }}">
                    @if (session('celebrate'))
                        <span class="celebrate-emoji">🎉</span>
                    @endif
                    {{ session('status') }}
                    @if (session('celebrate'))
                        <span class="celebrate-emoji" style="animation-delay: 0.15s">✨</span>
                    @endif
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

            {{-- Needs attention --}}
            @php($readyFields = $farm->fields->filter(fn ($f) => $f->isReady())->count())
            @php($unfedAnimals = $farm->animals->filter(fn ($a) => ! $a->isFedToday())->count())
            @php($storageFull = $farm->storageCapacity() > 0 && $farm->inventoryUsed() / $farm->storageCapacity() >= 0.9)
            @if ($readyFields || $unfedAnimals || $storageFull)
                <div class="rounded-md bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800">
                    📋 Needs attention:
                    @if ($readyFields) {{ $readyFields }} field(s) ready to harvest @endif
                    @if ($readyFields && $unfedAnimals) · @endif
                    @if ($unfedAnimals) {{ $unfedAnimals }} animal(s) not fed today @endif
                    @if (($readyFields || $unfedAnimals) && $storageFull) · @endif
                    @if ($storageFull) storage almost full @endif
                </div>
            @endif

            {{-- Today's Goal --}}
            @php($quest = $questProgress['quest'])
            <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6 border-l-4 {{ $questProgress['completed'] ? 'border-green-400' : 'border-emerald-300' }}">
                <div class="flex items-center justify-between flex-wrap gap-2">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide">🎯 Today's Goal</h3>
                        <p class="text-base text-gray-800">{{ $quest['description'] }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-medium {{ $questProgress['completed'] ? 'text-green-600' : 'text-gray-600' }}">
                            {{ min($questProgress['progress'], $quest['goal']) }} / {{ $quest['goal'] }}
                            {{ $questProgress['completed'] ? '✅' : '' }}
                        </p>
                        <p class="text-xs text-gray-400">Reward: ${{ $quest['reward_cash'] }} + {{ $quest['reward_xp'] }} XP at End Day</p>
                    </div>
                </div>
            </div>

            {{-- Weekly Challenge --}}
            @php($challenge = $weeklyChallengeProgress['challenge'])
            <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6 border-l-4 {{ $weeklyChallengeProgress['completed'] ? 'border-green-400' : 'border-teal-300' }}">
                <div class="flex items-center justify-between flex-wrap gap-2">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide">🗓️ Weekly Challenge <span class="normal-case text-gray-400">(day {{ $weeklyChallengeProgress['day_in_week'] }} / {{ \App\Models\Farm::SEASON_LENGTH_DAYS }})</span></h3>
                        <p class="text-base text-gray-800">{{ $challenge['description'] }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-medium {{ $weeklyChallengeProgress['completed'] ? 'text-green-600' : 'text-gray-600' }}">
                            {{ min($weeklyChallengeProgress['progress'], $challenge['goal']) }} / {{ $challenge['goal'] }}
                            {{ $weeklyChallengeProgress['completed'] ? '✅' : '' }}
                        </p>
                        <p class="text-xs text-gray-400">Reward: ${{ $challenge['reward_cash'] }} + {{ $challenge['reward_xp'] }} XP on the last day of the week</p>
                    </div>
                </div>
            </div>

            {{-- Fields --}}
            <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6">
                <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
                    <h3 class="text-lg font-semibold text-gray-800">🌱 Fields</h3>
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-gray-500">🧪 {{ $farm->fertilizer_count }} fertilizer · 🐛 {{ $farm->pesticide_count }} pesticide</span>
                        <form method="POST" action="{{ route('fertilizer.buy') }}">
                            @csrf
                            <input type="hidden" name="quantity" value="1">
                            <x-secondary-button type="submit" class="text-xs">Buy Fertilizer (${{ number_format(\App\Services\FarmService::FERTILIZER_PRICE, 2) }})</x-secondary-button>
                        </form>
                        <form method="POST" action="{{ route('pesticide.buy') }}">
                            @csrf
                            <input type="hidden" name="quantity" value="1">
                            <x-secondary-button type="submit" class="text-xs">Buy Pesticide (${{ number_format(\App\Services\FarmService::PESTICIDE_PRICE, 2) }})</x-secondary-button>
                        </form>
                        @if ($farm->fields->contains(fn ($f) => $f->isReady()))
                            <form method="POST" action="{{ route('fields.harvest-all') }}">
                                @csrf
                                <x-secondary-button type="submit" class="text-xs">Harvest All</x-secondary-button>
                            </form>
                        @endif
                        @if ($farm->fields->contains(fn ($f) => $f->isEmpty()))
                            <form method="POST" action="{{ route('fields.plant-all') }}" class="flex items-center gap-1">
                                @csrf
                                <select name="crop_type_id" class="rounded-md border-gray-300 text-xs" required>
                                    <option value="">Plant all empty…</option>
                                    @foreach ($cropTypes->where('required_level', '<=', $farm->level) as $crop)
                                        <option value="{{ $crop->id }}">{{ $crop->icon }} {{ $crop->name }}</option>
                                    @endforeach
                                </select>
                                <x-secondary-button type="submit" class="text-xs">Plant All</x-secondary-button>
                            </form>
                        @endif
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    @foreach ($farm->fields as $field)
                        <div class="border rounded-lg p-4 flex flex-col justify-between {{ $field->isReady() ? 'border-green-400 bg-green-50' : 'border-gray-200' }}">
                            <form method="POST" action="{{ route('fields.rename', $field) }}" class="flex items-center gap-1 mb-2">
                                @csrf
                                <input type="text" name="nickname" value="{{ $field->nickname }}" placeholder="Plot #{{ $field->plot_number }}" class="text-xs text-gray-500 border-0 border-b border-transparent hover:border-gray-300 focus:border-gray-400 focus:ring-0 px-0 py-0 bg-transparent w-full" maxlength="255">
                                <button type="submit" class="text-xs text-gray-300 hover:text-gray-500">✓</button>
                            </form>

                            @if ($field->isEmpty())
                                <div class="text-3xl mb-2">🟫</div>
                                <p class="text-sm text-gray-500 mb-3">Empty field</p>
                                <form method="POST" action="{{ route('fields.plant', $field) }}" class="space-y-2">
                                    @csrf
                                    <select name="crop_type_id" class="w-full rounded-md border-gray-300 text-sm" required>
                                        <option value="">Select seed…</option>
                                        @foreach ($cropTypes->where('required_level', '<=', $farm->level) as $crop)
                                            <option value="{{ $crop->id }}">{{ $crop->icon }} {{ $crop->name }} (${{ number_format((float) $crop->seed_price, 2) }}){{ $crop->season === $farm->currentSeason() ? ' 🌟 in season' : '' }}</option>
                                        @endforeach
                                    </select>
                                    <x-secondary-button type="submit" class="w-full justify-center">Plant</x-secondary-button>
                                </form>
                            @elseif ($field->isReady())
                                <div class="text-3xl mb-2">{{ $field->cropType->icon }}</div>
                                <p class="text-sm font-medium text-green-700 mb-1">{{ $field->cropType->name }} ready! (+{{ $field->harvestYield() }})</p>
                                @if ($field->isRotated() || $field->fertilized)
                                    <p class="text-xs text-emerald-600 mb-2">
                                        {{ $field->isRotated() ? '🔄 Rotation' : '' }}{{ $field->isRotated() && $field->fertilized ? ' + ' : '' }}{{ $field->fertilized ? '🧪 Fertilized' : '' }} bonus!
                                    </p>
                                @else
                                    <div class="mb-2"></div>
                                @endif
                                <form method="POST" action="{{ route('fields.harvest', $field) }}">
                                    @csrf
                                    <x-primary-button type="submit" class="w-full justify-center">Harvest</x-primary-button>
                                </form>
                            @else
                                <div class="text-3xl mb-2">{{ $field->cropType->icon }}</div>
                                <p class="text-sm text-gray-500 mb-1">{{ $field->cropType->name }} growing… {{ $field->daysRemaining() }} day(s) left</p>
                                @if ($field->fertilized)
                                    <p class="text-xs text-emerald-600 mb-2">🧪 Fertilized</p>
                                @else
                                    <div class="mb-2"></div>
                                @endif
                                <div class="h-2 w-full bg-gray-200 rounded-full overflow-hidden mb-2">
                                    <div class="h-full bg-amber-400" style="width: {{ min(100, (int) round((($farm->current_day - $field->planted_on_day) / max(1, $field->effectiveGrowthDays())) * 100)) }}%"></div>
                                </div>
                                @if (! $field->fertilized && $farm->fertilizer_count > 0)
                                    <form method="POST" action="{{ route('fields.fertilize', $field) }}">
                                        @csrf
                                        <x-secondary-button type="submit" class="w-full justify-center text-xs">Apply Fertilizer</x-secondary-button>
                                    </form>
                                @endif
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

                @php($lockedCrops = $cropTypes->where('required_level', '>', $farm->level))
                @if ($lockedCrops->isNotEmpty())
                    <p class="text-xs text-gray-400 mt-4">
                        🔒 Unlocks later:
                        @foreach ($lockedCrops as $crop)
                            {{ $crop->icon }} {{ $crop->name }} (Lv.{{ $crop->required_level }}){{ ! $loop->last ? ',' : '' }}
                        @endforeach
                    </p>
                @endif
            </div>

            {{-- Animals --}}
            <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">🐮 Animals <span class="text-xs font-normal text-gray-400">({{ $farm->animals->count() }}/{{ $farm->animalCapacity() }})</span></h3>
                    @if ($farm->animals->contains(fn ($a) => ! $a->isFedToday()))
                        <form method="POST" action="{{ route('animals.feed-all') }}">
                            @csrf
                            <x-secondary-button type="submit" class="text-xs">Feed All</x-secondary-button>
                        </form>
                    @endif
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    @forelse ($farm->animals as $animal)
                        @php($daysUnfed = $animal->isFedToday() ? 0 : $farm->current_day - ($animal->fed_on_day ?? $animal->purchased_on_day))
                        <div class="border rounded-lg p-4 {{ $animal->isFedToday() ? 'border-green-400 bg-green-50' : ($daysUnfed >= 2 ? 'border-red-300 bg-red-50' : 'border-gray-200') }}">
                            <div class="text-3xl mb-1">{{ $animal->animalType->icon }}</div>
                            <form method="POST" action="{{ route('animals.rename', $animal) }}" class="flex items-center gap-1 mb-1">
                                @csrf
                                <input type="text" name="nickname" value="{{ $animal->nickname }}" placeholder="{{ $animal->animalType->name }}" class="text-sm font-medium text-gray-800 border-0 border-b border-transparent hover:border-gray-300 focus:border-gray-400 focus:ring-0 px-0 py-0 bg-transparent w-full" maxlength="255">
                                <button type="submit" class="text-xs text-gray-300 hover:text-gray-500">✓</button>
                            </form>
                            <p class="text-xs text-gray-500 mb-1">
                                @if ($animal->isFedToday())
                                    Fed today ✅
                                @elseif ($animal->isInsured())
                                    🛡️ Insured — safe from neglect
                                @elseif ($daysUnfed >= 2)
                                    ⚠️ Unfed {{ $daysUnfed }} days — at risk!
                                @else
                                    Not fed today
                                @endif
                            </p>
                            <div class="mb-2"></div>
                            <div class="flex gap-2 flex-wrap">
                                @unless ($animal->isFedToday())
                                    <form method="POST" action="{{ route('animals.feed', $animal) }}" class="flex-1">
                                        @csrf
                                        <x-secondary-button type="submit" class="w-full justify-center text-xs">
                                            Feed (${{ number_format($animal->animalType->feed_cost, 2) }})
                                        </x-secondary-button>
                                    </form>
                                @endunless
                                @unless ($animal->isInsured())
                                    <form method="POST" action="{{ route('animals.insure', $animal) }}">
                                        @csrf
                                        <x-secondary-button type="submit" class="text-xs" title="Protects from neglect loss for {{ \App\Services\FarmService::INSURANCE_DAYS }} days">
                                            🛡️ (${{ number_format(\App\Services\FarmService::INSURANCE_PRICE, 2) }})
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
                @if ($farm->animals->count() >= $farm->animalCapacity())
                    <p class="text-sm text-amber-600 mb-3">🏚️ Your barn is full — sell an animal or buy a Barn Expansion to make room.</p>
                @endif
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    @foreach ($animalTypes->where('required_level', '<=', $farm->level) as $animalType)
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

                @php($lockedAnimals = $animalTypes->where('required_level', '>', $farm->level))
                @if ($lockedAnimals->isNotEmpty())
                    <p class="text-xs text-gray-400 mt-4">
                        🔒 Unlocks later:
                        @foreach ($lockedAnimals as $animalType)
                            {{ $animalType->icon }} {{ $animalType->name }} (Lv.{{ $animalType->required_level }}){{ ! $loop->last ? ',' : '' }}
                        @endforeach
                    </p>
                @endif
            </div>

            {{-- Inventory / Market --}}
            @php($marketPct = (int) round((float) $farm->market_multiplier * 100))
            <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6">
                <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
                    <h3 class="text-lg font-semibold text-gray-800">📦 Inventory &amp; Market</h3>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-medium {{ $marketPct >= 110 ? 'bg-green-100 text-green-800' : ($marketPct <= 90 ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-600') }}">
                            {{ $marketPct >= 110 ? '📈' : ($marketPct <= 90 ? '📉' : '➖') }} Prices at {{ $marketPct }}% today
                        </span>
                        <span class="text-xs text-gray-500">{{ $farm->inventoryUsed() }} / {{ $farm->storageCapacity() }} slots used</span>
                        @if ($farm->inventoryItems->isNotEmpty())
                            <form method="POST" action="{{ route('inventory.sell-all') }}">
                                @csrf
                                <x-secondary-button type="submit" class="text-xs">Sell All</x-secondary-button>
                            </form>
                        @endif
                    </div>
                </div>
                <div class="h-1.5 w-full bg-gray-200 rounded-full overflow-hidden mb-4">
                    @php($storagePct = $farm->storageCapacity() > 0 ? min(100, (int) round(($farm->inventoryUsed() / $farm->storageCapacity()) * 100)) : 0)
                    <div class="h-full {{ $storagePct >= 90 ? 'bg-red-400' : 'bg-blue-400' }}" style="width: {{ $storagePct }}%"></div>
                </div>

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
                                    <th class="py-2" colspan="2"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($farm->inventoryItems as $item)
                                    @php($product = $item->product())
                                    @php($unitPrice = $product['sell_price'] * (float) $farm->market_multiplier)
                                    <tr class="border-b last:border-0">
                                        <td class="py-2 pr-4">{{ $product['icon'] }} {{ $product['name'] }}</td>
                                        <td class="py-2 pr-4">{{ $item->quantity }}</td>
                                        <td class="py-2 pr-4">${{ number_format($unitPrice, 2) }}</td>
                                        <td class="py-2 pr-4">${{ number_format($unitPrice * $item->quantity, 2) }}</td>
                                        <td class="py-2">
                                            <form method="POST" action="{{ route('inventory.sell', $item) }}">
                                                @csrf
                                                <input type="hidden" name="quantity" value="{{ $item->quantity }}">
                                                <x-secondary-button type="submit" class="text-xs">Sell all</x-secondary-button>
                                            </form>
                                        </td>
                                        <td class="py-2">
                                            <form method="POST" action="{{ route('gifts.store-item', $item) }}" class="flex items-center gap-1">
                                                @csrf
                                                <input type="email" name="recipient_email" placeholder="email" class="rounded-md border-gray-300 text-xs w-28" required>
                                                <input type="number" name="quantity" value="{{ $item->quantity }}" min="1" max="{{ $item->quantity }}" class="rounded-md border-gray-300 text-xs w-14" required>
                                                <x-secondary-button type="submit" class="text-xs">🎁</x-secondary-button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            {{-- Bank --}}
            <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">🏦 Bank</h3>

                @php($loan = $farm->loans->first(fn ($l) => $l->isActive()))
                @if ($loan)
                    <p class="text-sm text-gray-600 mb-3">
                        Outstanding balance: <span class="font-semibold text-red-600">${{ number_format((float) $loan->balance, 2) }}</span>
                        — accrues {{ number_format($loan->daily_interest_rate * 100, 0) }}% interest per day.
                    </p>
                    <form method="POST" action="{{ route('loans.repay', $loan) }}" class="flex flex-wrap items-end gap-2">
                        @csrf
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Repay amount</label>
                            <input type="number" name="amount" step="0.01" min="0.01" max="{{ $loan->balance }}" class="rounded-md border-gray-300 text-sm" required>
                        </div>
                        <x-secondary-button type="submit">Repay</x-secondary-button>
                    </form>
                @else
                    <p class="text-sm text-gray-600 mb-3">
                        Need cash fast? Borrow up to ${{ number_format($maxLoanAmount, 2) }} — repay whenever you like, but interest accrues daily.
                    </p>
                    <form method="POST" action="{{ route('loans.store') }}" class="flex flex-wrap items-end gap-2">
                        @csrf
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Loan amount</label>
                            <input type="number" name="amount" step="0.01" min="1" max="{{ $maxLoanAmount }}" class="rounded-md border-gray-300 text-sm" required>
                        </div>
                        <x-secondary-button type="submit">Take Loan</x-secondary-button>
                    </form>
                @endif

                <hr class="my-4">

                <h4 class="text-sm font-semibold text-gray-600 mb-2">🎁 Gift cash to another farmer</h4>
                <form method="POST" action="{{ route('gifts.store') }}" class="flex flex-wrap items-end gap-2">
                    @csrf
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Recipient email</label>
                        <input type="email" name="recipient_email" class="rounded-md border-gray-300 text-sm" required>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Amount</label>
                        <input type="number" name="amount" step="0.01" min="0.01" class="rounded-md border-gray-300 text-sm w-28" required>
                    </div>
                    <x-secondary-button type="submit">Send Gift</x-secondary-button>
                </form>
            </div>

            {{-- Machinery --}}
            <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">🚜 Machinery</h3>

                @php($ownedIds = $farm->machinery->pluck('machinery_type_id'))

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    @foreach ($machineryTypes->where('required_level', '<=', $farm->level) as $machineryType)
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

                @php($lockedMachinery = $machineryTypes->where('required_level', '>', $farm->level))
                @if ($lockedMachinery->isNotEmpty())
                    <p class="text-xs text-gray-400 mt-4">
                        🔒 Unlocks later:
                        @foreach ($lockedMachinery as $machineryType)
                            {{ $machineryType->icon }} {{ $machineryType->name }} (Lv.{{ $machineryType->required_level }}){{ ! $loop->last ? ',' : '' }}
                        @endforeach
                    </p>
                @endif
            </div>

            {{-- Achievements --}}
            <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">🏆 Achievements</h3>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
                    @foreach ($farm->achievements as $achievement)
                        <div class="border border-amber-300 bg-amber-50 rounded-lg p-3 text-center" title="{{ $achievement->description }}">
                            <div class="text-2xl mb-1">{{ $achievement->icon }}</div>
                            <p class="text-xs font-medium text-gray-700">{{ $achievement->name }}</p>
                        </div>
                    @endforeach
                    @foreach (\App\Models\Achievement::whereNotIn('id', $farm->achievements->pluck('id'))->get() as $locked)
                        @php($progress = $achievementProgress[$locked->key] ?? null)
                        <div class="border border-gray-200 rounded-lg p-3 text-center opacity-60" title="{{ $locked->description }}">
                            <div class="text-2xl mb-1 grayscale">🔒</div>
                            <p class="text-xs font-medium text-gray-500">{{ $locked->name }}</p>
                            @if ($progress && $progress['goal'] > 1)
                                <p class="text-[10px] text-gray-400 mt-1">{{ (int) $progress['progress'] }} / {{ (int) $progress['goal'] }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Recent Activity --}}
            <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">📜 Recent Activity</h3>
                    <a href="{{ route('activity.index') }}" class="text-sm text-emerald-600 hover:underline">View all →</a>
                </div>
                @if ($recentTransactions->isEmpty())
                    <p class="text-sm text-gray-500">Nothing has happened yet.</p>
                @else
                    <ul class="divide-y">
                        @foreach ($recentTransactions as $transaction)
                            <li class="py-2 flex items-center justify-between text-sm">
                                <span class="text-gray-600">
                                    <span class="text-xs text-gray-400">Day {{ $transaction->day }}</span>
                                    {{ $transaction->description }}
                                </span>
                                @if (! is_null($transaction->amount))
                                    <span class="font-medium {{ $transaction->amount >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                        {{ $transaction->amount >= 0 ? '+' : '-' }}${{ number_format(abs($transaction->amount), 2) }}
                                    </span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
