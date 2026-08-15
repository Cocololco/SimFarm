# 🚜 Farm Sim

A turn-based browser farming game built with Laravel. Plant and harvest
crops, raise animals, sell your produce, and invest in machinery to grow
your farm — one day at a time.

## Gameplay

- **Fields** — buy seeds and plant them. Crops take a few days to mature;
  harvest once ready. Planting a *different* crop than grew there last time
  earns a crop-rotation yield bonus. "Harvest All" clears every ready field
  in one click.
- **Animals** — buy chickens, sheep, cows, pigs, goats, and alpacas. Feed
  them daily (or "Feed All" at once) to keep them producing eggs, wool,
  milk, truffles, and more. Animals left unfed for 3 days in a row run away.
  Your barn has a capacity — expand it with machinery.
- **Market** — sell harvested crops and animal produce for cash. Prices
  drift a little each day, so timing sales matters. Storage has a capacity;
  once full, extra harvests are wasted.
- **Machinery** — tractors, irrigation, a harvester, greenhouse, feed silo,
  storage barn, and barn expansion — permanent bonuses to growth speed,
  yield, feed cost, or capacity. Higher-tier machines unlock at higher farm
  levels.
- **Leveling** — harvesting, selling, and buying earn XP. Every 100 XP is a
  new farm level, unlocking higher-tier crops, animals, and machinery.
- **Daily Goals** — a random daily objective (harvest 3x / earn $50 / feed 2
  / plant 2) rewards cash + XP if completed before you end the day.
- **Bank** — borrow up to $1,000 with daily compounding interest; repay
  whenever you like. Gift cash to another farmer by email.
- **Random Events** — small daily chance of a lucky find, a generous
  neighbor, storm damage, or pests — never enough to push cash negative.
- **Achievements & Leaderboard** — unlock milestones and see how your net
  worth (cash + assets − debt) compares to other farmers; view any farmer's
  public profile.
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

Then visit `http://127.0.0.1:8000`, register an account, and you'll land on
your farm dashboard with 4 empty fields and $500 to get started. In-app
`/help` explains all the mechanics above.

## Running tests

```bash
php artisan test
```

Tests use an in-memory SQLite database (configured in `phpunit.xml`) and
cover the full gameplay loop, leveling gates, storage/barn capacity, loans,
random events, animal neglect, crop rotation, quick actions, daily quests,
gifting, market fluctuation, achievements, and the leaderboard — see
`tests/Feature` (97 tests as of this writing).
