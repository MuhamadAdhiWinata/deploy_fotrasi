# FortasiMupa — Agent Guide

## Stack

- **Laravel 13** + Livewire 3 + Volt (no Vue/React).
- Tailwind CSS v4 (`@import 'tailwindcss'` in CSS, `@tailwindcss/vite` plugin, `@theme` directive for custom colors). Tailwind config file is legacy — CSS `@theme` is source of truth.
- Vite 8, no TypeScript.
- MySQL (dev), SQLite in-memory (test). DB-driven queue/cache/session.

## Commands

| Command | What it does |
|---|---|
| `composer test` | `config:clear && php artisan test` — run all tests |
| `composer dev` | Runs `artisan serve`, `queue:listen`, `pail`, `npm run dev` concurrently |
| `composer setup` | Full bootstrap: install deps, create `.env`, key gen, migrate, npm build |
| `php artisan test --filter={TestName}` | Single test |
| `./vendor/bin/phpunit tests/Feature/FooTest.php` | Single file |
| `./vendor/bin/pint` | Format PHP code (default Laravel rules) |
| `npm run build` | Vite production build |

`.env` already exists. No CI/CD, no pre-commit hooks.

## Architecture

- **Routing**: `routes/web.php` — `/siswa/*` for siswa (dashboard, todo, kaih, kaih/rekap), `/admin/*` for admin (dashboard, siswa/index/detail/import/export, periode, presensi, tugas, tugas/pengumpulan). `/dashboard` redirects by role (`isAdmin()`). `/orang-tua` (no auth) for parent monitoring. `routes/auth.php` (Breeze), `routes/console.php`.
- **All page logic**: Livewire Volt single-file components under `resources/views/livewire/pages/`. Only 1 traditional controller: `Auth\VerifyEmailController`.
- **Admin routes include** GET `/admin/siswa/export` (inline controller, generates XLSX with presensi & tugas rekap per periode).
- **Models** (8): `User`, `Presensi`, `Tugas`, `PengumpulanTugas`, `Periode`, `KaihReflection`, `KaihEntry`, `KaihActivity` in `app/Models/`.
- **Services**: `app/Services/GeminiService.php` — AI student data extraction via Gemini 2.0 Flash (image/PDF/text input).
- **Layouts**: `app.blade.php` — dual-mode (admin sidebar + mobile slide-out menu vs siswa bottom nav + desktop sidebar). `parent.blade.php` — minimal layout for `/orang-tua`. `guest.blade.php` — unauthenticated pages.
- **Custom CSS theme** in `resources/css/app.css`: `primary (#406093)`, `secondary (#4C8CE4)`, `accent (#91D06C)`, `highlight (#FFF799)`, `dark (#1a1a1a)`, `surface (#f5f5f0)`.
- **Config highlights**:
  - Timezone: `Asia/Jakarta`
  - Valid classes list: `config/kelas.php` — 11 values (`TSM A/B/C`, `TKR A-D`, `PBS`, `RPL`, `DPIB`, `Animasi`)
  - Gemini/OpenAI keys: `config/services.php` (env: `GEMINI_API_KEY`, `OPENAI_API_KEY`)
  - Session/Cache/Queue driver override: `SESSION_DRIVER=database`, `CACHE_STORE=database`, `QUEUE_CONNECTION=database`
  - SPA mode (`wire:navigate`) enabled in `config/livewire.php`

## Database

13 migrations. Core tables:
- `users` (role: admin/siswa), `periodes` (`is_active` boolean, only one active), `presensis`, `tugas`, `pengumpulan_tugas`
- `kaih_activities` (predefined daily activities keyed by `key`), `kaih_entries` (per-user daily check-in per activity), `kaih_reflections` (per-user daily journal)

Unique constraints: `presensis(user_id, tanggal)`, `pengumpulan_tugas(tugas_id, user_id)`, `kaih_entries(user_id, activity_key, tanggal)`, `kaih_reflections(user_id, tanggal)`.

`periode_id` FK on `users`, `presensis`, `tugas`, `kaih_entries`, `kaih_reflections` — scopes data to active period. Set via admin → Periode.

Seeder: `DatabaseSeeder` calls `KaihActivitySeeder` first, then creates a default active periode, 1 admin + 3 siswa, 2 tugas, 1 presensi.

## Testing

- SQLite in-memory (`phpunit.xml`). Queue/sync, cache/array, session/array in test env. No external services needed.
- Auth tests use Breeze Livewire stack — test Volt components via `\Livewire\Volt\test()`.
- `composer test` is the canonical runner.

## Gotchas

- After adding a migration: `php artisan migrate:fresh --seed` to reset and re-seed (also seeds kaih_activities).
- `php artisan storage:link` needed for public file access (presensi photos, tugas submissions).
- Gemini features require `GEMINI_API_KEY` in `.env`.
- Siswa import uses `phpoffice/phpspreadsheet` (Excel), `phpoffice/phpword` (Word), and `smalot/pdfparser` (PDF) for document parsing. These are non-Laravel deps specific to this app.
- `queue:listen` must run in dev (`composer dev` handles this) — DB queue driver processes async jobs.
- `.editorconfig`: 4-space indent, LF line endings.
