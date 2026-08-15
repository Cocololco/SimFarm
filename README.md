# 🚜 Farm Sim

A turn-based browser farming game built with Laravel. Plant and harvest
crops, raise animals, sell your produce, and invest in machinery to grow
your farm — one day at a time.

## Gameplay

- **Fields** — buy seeds and plant them in your fields. Crops take a few
  days to mature; harvest them once ready to add produce to your inventory.
- **Animals** — buy chickens, sheep, cows, and pigs. Feed them each day to
  keep them producing eggs, wool, milk, and truffles.
- **Market** — sell whatever you've harvested or collected for cash.
- **Machinery** — invest in a tractor, irrigation, a harvester, a feed silo,
  or a greenhouse to speed up crop growth, boost harvest yield, or cut feed
  costs.
- **End Day** — time only advances when you choose to end the day, so play
  at your own pace.

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
your farm dashboard with 4 empty fields and $500 to get started.

## Running tests

```bash
php artisan test
```

Tests use an in-memory SQLite database (configured in `phpunit.xml`) and
cover planting/harvesting, animal care, the market, machinery effects, and
turn advancement — see `tests/Feature`.
