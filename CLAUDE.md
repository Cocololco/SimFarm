# CLAUDE.md

Instructions for Claude Code when working in this repository.

## Project Overview

**Farm Sim** — a turn-based browser farming game. Each player has their own
farm: plant and harvest crops in fields, raise and feed animals for
produce, sell goods on the market, and buy machinery to boost yield/growth
speed/feed costs. Time advances one "day" at a time via an explicit
"End Day" action (not real-time/idle).

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
- Avoid Blade's `@disabled`/`@checked`/`@selected` directives on
  `<x-component>` tags (e.g. `<x-secondary-button>`) — they broke Blade
  compilation here (`syntax error, unexpected token "endif"`). Prefer
  conditionally omitting the element (`@unless`/`@if`) instead.
