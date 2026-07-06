# FortasiMupa — Agent Guide

## Stack

- **Laravel 13** + Livewire 3 + Volt (no Vue/React).
- Tailwind CSS v4, Vite 8, no TypeScript.
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

- **Routing**: `routes/web.php` — `/siswa/*` for siswa, `/admin/*` for admin. `/dashboard` redirects by role (`isAdmin()`). `routes/auth.php` (Breeze), `routes/console.php`.
- **All page logic**: Livewire Volt single-file components under `resources/views/livewire/pages/`.
  - Admin: `pages/admin/*.blade.php` — dashboard, siswa (index/detail/import), periode, presensi, tugas, tugas-pengumpulan.
  - Siswa: `pages/siswa/*.blade.php` — dashboard, todo, presensi, tugas.
- **Only 1 traditional controller**: `Auth\VerifyEmailController`.
- **Models** (5): `User`, `Presensi`, `Tugas`, `PengumpulanTugas`, `Periode` in `app/Models/`.
- **Services**: `app/Services/GeminiService.php` — AI student data extraction via Gemini 2.0 Flash.
- **Views**: `layouts/app.blade.php` has dual-mode layout (admin sidebar vs siswa bottom nav). Livewire layout configured to `components.layouts.app` in `config/livewire.php`.
- **Config highlights**:
  - Timezone: `Asia/Jakarta`
  - Valid classes list: `config/kelas.php` — 11 values (`TSM A/B/C`, `TKR A-D`, `PBS`, `RPL`, `DPIB`, `Animasi`)
  - Gemini/OpenAI keys: `config/services.php` (env: `GEMINI_API_KEY`, `OPENAI_API_KEY`)
  - Session/Cache/Queue driver override: `SESSION_DRIVER=database`, `CACHE_STORE=database`, `QUEUE_CONNECTION=database`
  - SPA mode (`wire:navigate`) enabled in `config/livewire.php`

## Database

12 migrations. Core tables: `users` (role: admin/siswa), `periodes` (`is_active` boolean, only one active), `presensis`, `tugas`, `pengumpulan_tugas`.

Unique constraints: `presensis(user_id, tanggal)`, `pengumpulan_tugas(tugas_id, user_id)`.

`periode_id` FK on `users`, `presensis`, and `tugas` — scopes data to active period. Set via admin → Periode.

## Testing

- SQLite in-memory (`phpunit.xml`). No external services needed.
- Auth tests use Breeze Livewire stack — test Volt components via `\Livewire\Volt\test()`.
- `composer test` is the canonical runner.

## Gotchas

- After adding a migration: `php artisan migrate:fresh --seed` to reset and re-seed.
- `php artisan storage:link` needed for public file access (presensi photos, tugas submissions).
- Gemini features require `GEMINI_API_KEY` in `.env`.
- Siswa import uses `phpoffice/phpspreadsheet` (Excel), `phpoffice/phpword` (Word), and `smalot/pdfparser` (PDF) for document parsing. These are non-Laravel deps specific to this app.
- `queue:listen` must run in dev (`composer dev` handles this) — DB queue driver processes async jobs.
- `.editorconfig`: 4-space indent, LF line endings.
