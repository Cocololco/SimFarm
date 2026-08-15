# 🚜 Farm Sim

A turn-based browser farming game built with Laravel. Plant and harvest
crops, raise animals, sell your produce, and invest in machinery to grow
your farm — one day at a time.

## Gameplay

- **Fields** — buy seeds and plant them. Crops take a few days to mature;
  harvest once ready. Planting a *different* crop than grew there last time
  earns a crop-rotation bonus, and harvesting a crop in its favored season
  (spring/summer/fall/winter, a 7-day cycle) earns another. Fertilizer adds
  a further one-cycle yield boost. "Harvest All" / "Plant All" handle a
  whole farm's fields in one click; fields can be nicknamed.
- **Animals** — buy chickens, sheep, cows, pigs, goats, alpacas, and (at
  higher levels) ostriches. Feed them daily (or "Feed All" at once) for
  eggs, wool, milk, truffles, and more. Well-fed animals occasionally breed
  for free. Animals left unfed for 3 days run away — insure one to protect
  it, or automate feeding entirely with a Farmhand machine. Your barn has a
  capacity — expand it with machinery. Animals can be nicknamed.
- **Market** — sell harvested crops and animal produce for cash, or "Sell
  All" at once. Prices drift a little each day, so timing sales matters. A
  visiting trader occasionally offers a premium for one random item.
  Storage has a capacity; a Compost Bin turns what would spoil into
  fertilizer instead of wasting it.
- **Machinery** — tractors, irrigation, harvesters, greenhouses, feed silos,
  storage/barn expansion, a Farmhand (auto-feeds daily), an Auto-Harvester
  Drone (auto-harvests daily), and a Compost Bin. Higher-tier machines
  (through level 6) unlock as your farm levels up.
- **Leveling** — harvesting, selling, and buying earn XP. Every 100 XP is a
  new farm level, unlocking higher-tier crops, animals, and machinery.
- **Daily & Weekly Goals** — a daily objective and a bigger weekly challenge
  both reward cash + XP when completed.
- **Wealth Milestones** — one-time cash + XP payouts at $1k/$5k/$10k/$25k/
  $50k net worth.
- **Bank & Gifts** — borrow up to $1,000 with daily compounding interest;
  gift cash *or* inventory items to another farmer by email.
- **Random Events** — a small daily chance of a lucky find, a generous
  neighbor, storm damage, pests (pesticide blocks these), or the visiting
  trader — never enough to push cash negative.
- **Achievements, Leaderboard & Stats** — unlock badges (with progress
  hints while locked), see how your net worth compares to other farmers
  (searchable/sortable, with public farm profiles), and check community-wide
  totals on the platform stats page. A dedicated Alerts feed and full
  Activity log (with a cash-history chart) track everything that happens.
- **End Day** — time only advances when you choose to end the day, so play
  at your own pace (not real-time/idle).

Multiple accounts are supported — register and you get your own farm
automatically, separate from everyone else's.

## Requirements

- PHP 8.1+ with the `pdo_sqlite` and `sqlite3` extensions enabled
- Composer
- Node.js 18+ and npm (for building the Tailwind/Vite front-end assets)

> **Note (Laragon on Windows):** if you have both a global Node install and
> Laragon's bundled Node, make sure Node 18 is the one on `PATH` when
> running `npm` commands — Vite 5 doesn't run on Node 16. See `CLAUDE.md`
> for the exact commands used during setup.

## Setup

```bash
composer install
npm install

cp .env.example .env    # already done if you're reading this after setup
php artisan key:generate

# Local dev DB: a SQLite file at database/database.sqlite (DB_CONNECTION=sqlite in .env)
# It's gitignored, so create it on a fresh clone before migrating:
touch database/database.sqlite
php artisan migrate --seed

npm run build            # or `npm run dev` while developing
php artisan serve
```

Then visit `http://127.0.0.1:8000` — you'll land on a Farm Sim landing
page. Register an account and you'll get your own farm dashboard with 4
empty fields and $500 to get started. In-app `/help` explains all the
mechanics above.

## Running tests

```bash
php artisan test
```

Tests use an in-memory SQLite database (configured in `phpunit.xml`) and
cover the full gameplay loop end to end — leveling gates, storage/barn
capacity, loans, random events (including the probabilistic ones, via
many-simulated-days invariant tests), animal neglect/insurance/breeding,
crop rotation/seasons/fertilizer, quick/bulk actions, daily/weekly quests,
cash/item gifting, market fluctuation, automation machinery, achievements,
milestones, and the leaderboard — see `tests/Feature` (156 tests as of
this writing).
