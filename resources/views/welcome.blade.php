<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="A turn-based browser farming game — plant crops, raise animals, and grow your farm at your own pace.">

        <title>{{ config('app.name', 'Farm Sim') }} — Grow your farm, one day at a time</title>

        <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Ctext y='.9em' font-size='90'%3E%F0%9F%9A%9C%3C/text%3E%3C/svg%3E">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        <!-- Styles -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            .hero-bg {
                background-image:
                    radial-gradient(circle at 15% 20%, rgba(255,255,255,0.5) 0, transparent 45%),
                    radial-gradient(circle at 85% 75%, rgba(255,255,255,0.35) 0, transparent 40%),
                    linear-gradient(160deg, #ecfdf5 0%, #fefce8 55%, #fff7ed 100%);
            }
            @keyframes float-slow {
                0%, 100% { transform: translateY(0) rotate(0deg); }
                50% { transform: translateY(-10px) rotate(-3deg); }
            }
            .float-slow { animation: float-slow 5s ease-in-out infinite; }
            .float-slow-delayed { animation: float-slow 5s ease-in-out infinite; animation-delay: 1.2s; }
        </style>
    </head>
    <body class="font-sans antialiased text-gray-800">
        <div class="hero-bg min-h-screen">

            <!-- Top bar -->
            <header class="max-w-6xl mx-auto px-6 py-6 flex items-center justify-between">
                <div class="flex items-center gap-2 text-xl font-extrabold text-emerald-900">
                    <span class="text-2xl">🚜</span> Farm Sim
                </div>
                <nav class="flex items-center gap-4 text-sm font-semibold">
                    @auth
                        <a href="{{ route('dashboard') }}" class="rounded-md bg-emerald-700 px-4 py-2 text-white shadow-sm hover:bg-emerald-600 transition">
                            Go to your farm →
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="text-emerald-900 hover:text-emerald-700 transition">Log in</a>
                        <a href="{{ route('register') }}" class="rounded-md bg-emerald-700 px-4 py-2 text-white shadow-sm hover:bg-emerald-600 transition">
                            Start farming — it's free
                        </a>
                    @endauth
                </nav>
            </header>

            <!-- Hero -->
            <section class="max-w-6xl mx-auto px-6 pt-10 pb-20 grid lg:grid-cols-2 gap-12 items-center">
                <div>
                    <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 text-amber-800 text-xs font-semibold px-3 py-1 mb-5">
                        🌱 Free to play · No downloads
                    </span>
                    <h1 class="text-4xl sm:text-5xl font-extrabold text-emerald-950 leading-tight tracking-tight">
                        Grow your farm,<br>
                        <span class="text-emerald-700">one day at a time.</span>
                    </h1>
                    <p class="mt-5 text-lg text-gray-600 leading-relaxed max-w-lg">
                        Plant crops, raise animals, sell your harvest, and invest in machinery. Turn-based, not real-time — advance a day whenever <em>you're</em> ready, no pressure, no timers.
                    </p>
                    <div class="mt-8 flex flex-wrap items-center gap-4">
                        @auth
                            <a href="{{ route('dashboard') }}" class="rounded-md bg-emerald-700 px-6 py-3 text-white font-semibold shadow-md hover:bg-emerald-600 transition">
                                Go to your farm →
                            </a>
                        @else
                            <a href="{{ route('register') }}" class="rounded-md bg-emerald-700 px-6 py-3 text-white font-semibold shadow-md hover:bg-emerald-600 transition">
                                Start your farm →
                            </a>
                            <a href="{{ route('login') }}" class="rounded-md bg-white px-6 py-3 text-emerald-800 font-semibold shadow-sm border border-emerald-200 hover:bg-emerald-50 transition">
                                I already have a farm
                            </a>
                        @endauth
                    </div>
                    <p class="mt-4 text-xs text-gray-500">Every new farmer starts with 4 fields and $500 — no strings attached.</p>
                </div>

                <!-- Mock dashboard preview -->
                <div class="relative">
                    <div class="absolute -top-6 -left-4 text-4xl float-slow select-none" aria-hidden="true">🌾</div>
                    <div class="absolute -bottom-4 -right-2 text-4xl float-slow-delayed select-none" aria-hidden="true">🐔</div>
                    <div class="rounded-2xl bg-white shadow-2xl shadow-emerald-900/10 border border-emerald-100 p-5">
                        <div class="flex items-center justify-between mb-4">
                            <span class="font-bold text-emerald-950">🚜 Sunnybrook Farm</span>
                            <span class="text-xs rounded-full bg-amber-100 text-amber-800 px-2 py-1">📅 Day 12</span>
                        </div>
                        <div class="grid grid-cols-4 gap-2 mb-4">
                            <div class="rounded-lg bg-emerald-50 border border-emerald-200 p-2 text-center">
                                <div class="text-xl">🌽</div>
                                <div class="text-[10px] text-emerald-700 font-medium">Ready!</div>
                            </div>
                            <div class="rounded-lg bg-gray-50 border border-gray-200 p-2 text-center">
                                <div class="text-xl">🥕</div>
                                <div class="text-[10px] text-gray-500">2 days</div>
                            </div>
                            <div class="rounded-lg bg-gray-50 border border-gray-200 p-2 text-center">
                                <div class="text-xl">🍓</div>
                                <div class="text-[10px] text-gray-500">1 day</div>
                            </div>
                            <div class="rounded-lg border border-dashed border-gray-300 p-2 text-center text-gray-300">
                                <div class="text-xl">➕</div>
                            </div>
                        </div>
                        <div class="flex items-center justify-between text-xs text-gray-500 mb-1">
                            <span>XP</span><span>240 / 300</span>
                        </div>
                        <div class="h-1.5 w-full bg-gray-200 rounded-full overflow-hidden mb-4">
                            <div class="h-full bg-purple-400" style="width: 80%"></div>
                        </div>
                        <div class="flex items-center justify-between rounded-lg bg-emerald-700 text-white text-sm font-semibold px-4 py-2">
                            <span>💰 $1,284.50</span>
                            <span>End Day →</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Feature grid -->
            <section class="max-w-6xl mx-auto px-6 py-16">
                <h2 class="text-2xl font-bold text-emerald-950 text-center mb-2">Everything a farm needs</h2>
                <p class="text-center text-gray-500 mb-10">And a fair bit more than most.</p>
                <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
                    @foreach ([
                        ['icon' => '🌱', 'title' => 'Fields & Crops', 'body' => 'Plant, tend, and harvest — with a crop-rotation bonus for switching it up.'],
                        ['icon' => '🐮', 'title' => 'Animals', 'body' => 'Feed chickens, cows, sheep and more for eggs, milk, and wool. They can even breed.'],
                        ['icon' => '📦', 'title' => 'A living market', 'body' => 'Prices drift daily, and a traveling trader occasionally pays a premium.'],
                        ['icon' => '🚜', 'title' => 'Machinery', 'body' => 'Automate feeding and harvesting, or boost yield, growth speed, and storage.'],
                        ['icon' => '🌸', 'title' => 'Seasons', 'body' => 'A 4-season cycle rewards planting the right crop at the right time.'],
                        ['icon' => '🏆', 'title' => 'Achievements & Leaderboard', 'body' => 'Chase milestones and see how your net worth stacks up against other farmers.'],
                    ] as $feature)
                        <div class="rounded-xl bg-white border border-emerald-100 p-6 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition">
                            <div class="text-3xl mb-3">{{ $feature['icon'] }}</div>
                            <h3 class="font-semibold text-emerald-950 mb-1">{{ $feature['title'] }}</h3>
                            <p class="text-sm text-gray-500 leading-relaxed">{{ $feature['body'] }}</p>
                        </div>
                    @endforeach
                </div>
            </section>

            <!-- CTA band -->
            <section class="max-w-4xl mx-auto px-6 pb-20">
                <div class="rounded-2xl bg-emerald-800 text-white px-8 py-10 text-center shadow-lg">
                    <h2 class="text-2xl font-bold mb-2">Ready to break ground?</h2>
                    <p class="text-emerald-100 mb-6">Registration takes about ten seconds. Your farm is waiting.</p>
                    @auth
                        <a href="{{ route('dashboard') }}" class="inline-block rounded-md bg-white px-6 py-3 text-emerald-800 font-semibold shadow-md hover:bg-emerald-50 transition">
                            Go to your farm →
                        </a>
                    @else
                        <a href="{{ route('register') }}" class="inline-block rounded-md bg-white px-6 py-3 text-emerald-800 font-semibold shadow-md hover:bg-emerald-50 transition">
                            Create your free farm →
                        </a>
                    @endauth
                </div>
            </section>

            <footer class="max-w-6xl mx-auto px-6 pb-10 text-center text-xs text-gray-400">
                🚜 Farm Sim — a hobby project, not affiliated with any real farm.
            </footer>
        </div>
    </body>
</html>
