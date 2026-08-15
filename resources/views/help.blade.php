<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            ❓ How to Play
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6 text-sm text-gray-700">

            <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6 space-y-3">
                <h3 class="text-lg font-semibold text-gray-800">🌱 Fields &amp; Crops</h3>
                <p>Buy seeds and plant them in your fields. Crops take a few days to mature — advance time with <strong>End Day</strong>. Once ready, harvest for produce you can sell. Planting a <em>different</em> crop than grew there last time earns a +15% crop rotation yield bonus.</p>
            </div>

            <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6 space-y-3">
                <h3 class="text-lg font-semibold text-gray-800">🐮 Animals</h3>
                <p>Buy animals and feed them daily to keep them producing eggs, milk, wool, and more. Animals left unfed for 3 days in a row will run away — keep an eye on the ⚠️ warning. Your barn can only hold so many animals; buy a Barn Expansion to make room.</p>
            </div>

            <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6 space-y-3">
                <h3 class="text-lg font-semibold text-gray-800">📦 Market &amp; Storage</h3>
                <p>Sell harvested crops and animal produce for cash. Your storage has a capacity — once full, extra harvests are wasted, so sell regularly or buy a Storage Barn.</p>
            </div>

            <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6 space-y-3">
                <h3 class="text-lg font-semibold text-gray-800">🚜 Machinery</h3>
                <p>Machinery gives permanent bonuses: faster growth, bigger harvests, cheaper feed, or more storage/barn space. Some machines unlock at higher farm levels.</p>
            </div>

            <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6 space-y-3">
                <h3 class="text-lg font-semibold text-gray-800">⭐ Levels &amp; XP</h3>
                <p>Harvesting, selling, and buying animals/machinery earn XP. Every 100 XP is a new farm level, which unlocks higher-tier crops, animals, and machinery.</p>
            </div>

            <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6 space-y-3">
                <h3 class="text-lg font-semibold text-gray-800">🎯 Daily Goals</h3>
                <p>Each day brings a random objective (harvest 3x, earn $50, feed 2 animals, or plant 2 seeds). Complete it before ending the day for a cash + XP bonus.</p>
            </div>

            <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6 space-y-3">
                <h3 class="text-lg font-semibold text-gray-800">🏦 Bank &amp; Gifts</h3>
                <p>Borrow up to $1,000 when you need cash fast — interest accrues daily until repaid. You can also gift cash to another farmer by email from the Bank section.</p>
            </div>

            <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6 space-y-3">
                <h3 class="text-lg font-semibold text-gray-800">🎲 Random Events</h3>
                <p>Each day carries a small chance of a random event — a lucky find, a generous neighbor, storm damage, or pests. Bad luck never pushes your cash below $0.</p>
            </div>

            <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6 space-y-3">
                <h3 class="text-lg font-semibold text-gray-800">🏆 Achievements &amp; Leaderboard</h3>
                <p>Unlock achievements for milestones like your first harvest or reaching level 5. Check the <a href="{{ route('leaderboard.index') }}" class="text-indigo-600 hover:underline">Leaderboard</a> to see how your net worth compares to other farmers.</p>
            </div>

        </div>
    </div>
</x-app-layout>
