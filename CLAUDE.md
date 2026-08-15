# CLAUDE.md

Instructions for Claude Code when working in this repository.

## Project Overview

**Farm Sim** — a turn-based browser farming game. Each player has their own
farm: plant and harvest crops in fields, raise and feed animals for
produce, sell goods on a daily-fluctuating market, and buy machinery to
boost yield/growth speed/feed costs/storage/barn capacity. Farms level up
via XP, unlocking higher-tier crops/animals/machinery. Time advances one
"day" at a time via an explicit "End Day" action (not real-time/idle) —
end-of-day also resolves animal production, animal neglect risk, loan
interest, a random event, the daily quest reward, and market drift, all in
`FarmService::endDay()`.

Also has: a bank (loans + cash/item gifting), a unified `transactions`
activity log (backs the recent-activity feed, `/activity` cash chart, and
`/alerts`), achievements + wealth milestones, a searchable/sortable
net-worth leaderboard with public farm profiles, `/stats` platform-wide
totals, seasons, animal breeding/insurance, daily + weekly challenges, a
visiting-trader random event, and automation machinery (Farmhand,
Auto-Harvester Drone, Compost Bin). Full feature list lives in `README.md`
— don't duplicate it here; this file is conventions/gotchas only.

Multiple accounts are supported (Laravel Breeze auth); each user has
exactly one farm (`User hasOne Farm`), created automatically on
registration via the `CreateFarmForNewUser` listener.

## Autonomy & Workflow

- Work in **full autonomy**: install dependencies, scaffold files, run
  builds/tests/linters, and commit changes without pausing to ask for
  confirmation first.
- Don't stop mid-task to check in — carry multi-step work through to
  completion. Only stop if the user explicitly asks you to, or a requirement
  is genuinely ambiguous and no reasonable default exists.
- Git: commit as you go with clear, descriptive messages. Push when it makes
  sense for the task at hand.
- If something breaks (failing build, failing test), fix it as part of the
  task rather than leaving it for later or asking whether to proceed.

## Testing

- Write tests for new features alongside the functional code that implements
  them (`tests/Feature`, PHPUnit, `RefreshDatabase`).
- Run `php artisan test` after making changes, and fix failures before
  considering a task done.
- Game-catalog-dependent tests should seed via the `InteractsWithFarm` trait
  (`tests/Concerns/InteractsWithFarm.php`) rather than duplicating seed
  logic.

## Tech Stack

- **Laravel 10** (PHP 8.1, via Laragon's bundled PHP — see Environment notes
  below), Blade views, no SPA framework.
- **Laravel Breeze** (Blade stack) for auth (login/register/profile).
- **SQLite** (`database/database.sqlite`) for local dev and testing
  (`:memory:` in `phpunit.xml`) — no MySQL service dependency.
- **Tailwind CSS** + Vite for styling/build (Breeze defaults).

## Environment notes (Windows / Laragon)

- The system PHP on PATH resolves to Laragon's `php-8.1.10`. Its `php.ini`
  had `pdo_sqlite`/`sqlite3` extensions disabled by default — they were
  enabled by uncommenting the `extension=` lines in
  `C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.ini`. If PHP ever gets
  reinstalled/updated, re-enable those two extensions.
- There are **two Node installs** on this machine: a global Node 16
  (`C:\Program Files\nodejs`, first on PATH) and Laragon's bundled Node 18
  (`C:\laragon\bin\nodejs\node-v18`). Vite 5 requires Node 18+, so always run
  `npm`/`node` commands with Laragon's Node 18 first on PATH, e.g.:
  ```bash
  export PATH="/c/laragon/bin/nodejs/node-v18:$PATH"
  npm run build   # or npm run dev
  ```

## Conventions

- Game rules/mutations (planting, harvesting, buying, feeding, selling,
  ending the day) live in `App\Services\FarmService`, not in controllers —
  keeps controllers thin and the rules unit-testable. Controllers resolve
  `$request->user()->farm` and delegate to the service.
- The service throws `ValidationException` for invalid actions (wrong
  owner, insufficient cash, already fed, etc.); Laravel's default handling
  turns that into a redirect-back-with-errors for these web (non-JSON)
  routes, which is what the Blade views expect via `session('status')` /
  `$errors`.
- Catalog tables (`crop_types`, `animal_types`, `machinery_types`) are
  seeded data, not user-editable; look them up by their `key` column rather
  than hardcoding IDs.
- `InventoryItem::product()` resolves display info (name/icon/sell price)
  by checking `crop_types.key` then `animal_types.produce_key` — there's no
  separate unified "products" catalog table by design (avoids a redundant
  join for a small catalog).
- `crop_types`/`animal_types`/`machinery_types` all have a `required_level`
  column; `FarmService::assertLevel()` enforces it server-side on
  plant/buyAnimal/buyMachinery (not just hidden in the UI). `Farm::level`
  is a derived accessor from `xp` (never stored), so it can't drift out of
  sync — don't add a `level` column.
- The daily quest (`FarmService::DAILY_QUESTS`/`todaysQuest()`) and weekly
  challenge (`WEEKLY_CHALLENGES`/`todaysWeeklyChallenge()`) are both
  deterministic — `(farm_id + current_day_or_week_index) % count(pool)` —
  not stored, so "today's/this week's" pick is always recomputable from a
  farm's own state. The weekly reward only checks/fires when
  `current_day % SEASON_LENGTH_DAYS === 0` (the last day of the week), so
  it can't double-pay without needing a "claimed" column. Same trick for
  `Farm::currentSeason()` (derived from `current_day`, never stored) and
  wealth milestones (`MilestoneService` — "already paid" is checked by
  scanning `transactions` for a `type = 'milestone'` row mentioning that
  threshold, not a separate table).
- `endDay()` order matters and is deliberate: neglect/production loop →
  loan interest → random event → daily quest → weekly challenge → market
  drift → **increment current_day** → auto-feed (Farmhand) → auto-harvest
  (Drone). The automation steps run *after* the increment so a
  Farmhand-fed animal / drone-harvested field already reads as fed/empty
  the instant `endDay()` returns, matching what a manual feed/harvest
  would show at that same point — putting them before the increment (as
  first written) was an off-by-one-day bug, caught by
  `FarmAutomationTest`.
- Random daily events (`RANDOM_EVENTS`) and the market-price random walk
  (`driftMarketMultiplier()`) both clamp so a bad roll never pushes cash
  negative or prices outside their band — tested via invariants over many
  simulated days (`RandomEventTest`, `MarketFluctuationTest`), not exact
  values, since they're non-deterministic (`mt_rand`).
- Every cash-affecting (and several non-cash) action logs a `Transaction`
  via `FarmService::logTransaction()` — this single table backs the
  dashboard's recent-activity feed, the full `/activity` history + cash
  chart, and achievement checks (e.g. counting `type = 'harvest'` rows).
  New gameplay actions should log one too, with `amount` null for
  non-cash events.
- Avoid Blade's `@disabled`/`@checked`/`@selected` directives on
  `<x-component>` tags (e.g. `<x-secondary-button>`) — they broke Blade
  compilation here (`syntax error, unexpected token "endif"`). Prefer
  conditionally omitting the element (`@unless`/`@if`) instead.
- On `Farm`, prefer relation *methods* (`$this->inventoryItems()->sum(...)`)
  over cached relation *properties* (`$this->inventoryItems->sum(...)`) in
  any helper that's read again after a write in the same request/object
  lifetime (`inventoryUsed()`, `machineryEffectValue()`, `activeLoan()`,
  `netWorth()` all do this deliberately). `$request->user()` — and its
  cached `->farm` — persists as the *same* PHP object across multiple
  simulated requests in feature tests (`actingAs()` reuses it), so a
  property-cached collection loaded before a later write goes stale and
  silently returns pre-write data. `FarmService::endDay()` uses `load()`
  instead of `loadMissing()` for the same reason. Bit us as real test
  failures once (loans/storage-cap/animal-neglect tests) — see git history
  around that fix if this class of bug resurfaces.
- Probabilistic mechanics (random events, pesticide blocking, breeding,
  the visiting trader) are tested via **invariants over many simulated
  end-days** (30-100, sized so the chance of a false failure is ~0.001-1%),
  never by forcing a specific `mt_rand` roll — see `RandomEventTest`,
  `FarmAutomationTest`'s pesticide test, `AnimalBreedingTest`,
  `VisitingTraderTest` for the pattern.
- Brand colors are **emerald**, not Breeze's default indigo/gray — all of
  `x-primary-button`/`x-secondary-button`/`x-text-input`/nav-link
  components and every view were switched over in one pass. Keep new UI on
  `emerald-*` (buttons/links/focus rings) plus the existing amber/warm
  neutrals (`stone`/`amber`) for backgrounds; don't reintroduce indigo.
- `Field::harvestYield()` bonuses (machinery yield_boost, rotation,
  fertilizer, season) are computed fresh from model state every call, so
  when logging a "bonus" description in `FarmService::harvest()`, snapshot
  the boolean flags (`$rotated`, `$fertilized`, `$inSeason`) *before*
  calling `harvestYield()` — not after, since the field gets cleared
  (`fertilized` reset etc.) as part of the same method.
