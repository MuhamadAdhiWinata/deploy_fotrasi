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

No CI/CD, no pre-commit hooks, no static analysis config.

## Architecture

- **Entry**: `public/index.php` (HTTP), `artisan` (CLI), `bootstrap/app.php`.
- **Routing**: `routes/web.php` (main app), `routes/auth.php` (Breeze auth), `routes/console.php`.
- **All page logic** is Livewire Volt single-file components under `resources/views/livewire/pages/`.
  - Admin: `livewire/pages/admin/*.blade.php`
  - Siswa: `livewire/pages/siswa/*.blade.php`
- **Only 1 traditional controller**: `Auth\VerifyEmailController`.
- **Models** (4): `User`, `Presensi`, `Tugas`, `PengumpulanTugas` in `app/Models/`.
- **Services**: `app/Services/GeminiService.php` — AI student data extraction via Gemini 2.0 Flash.
- **Views**: `layouts/app.blade.php` has dual-mode layout (admin sidebar vs siswa bottom nav).
- **Config** highlights:
  - Timezone: `Asia/Jakarta` (`config/app.php`)
  - Valid classes list: `config/kelas.php`
  - Gemini/OpenAI keys: `config/services.php` (env: `GEMINI_API_KEY`, `OPENAI_API_KEY`)
  - Session driver override: `SESSION_DRIVER=database`

## Database

11 migrations — core tables: `users` (role: admin/siswa), `periodes`, `presensis`, `tugas`, `pengumpulan_tugas`.

Unique constraints: `presensis(user_id, tanggal)`, `pengumpulan_tugas(tugas_id, user_id)`.

`periodes` table has `is_active` boolean (only one active at a time). Users, Presensi, and Tugas each have a nullable `periode_id` FK scoping data to a period. Set active period in admin → Periode.

## Testing

- SQLite in-memory (see `phpunit.xml`). No external services needed.
- Tests in `tests/Feature/` and `tests/Unit/`.
- Auth tests use Breeze Livewire stack (test Volt components via `\Livewire\Volt\test()`).
- `composer test` is the canonical runner.

## Gotchas

- After adding a migration: `php artisan migrate:fresh --seed` to reset and re-seed.
- `php artisan storage:link` needed for public file access (photos, submissions).
- The SEO-friendly slug in `routes/web.php` is `/siswa/*`, `/admin/*` — watch for role-redirect in `/dashboard`.
- Gemini features require `GEMINI_API_KEY` in `.env`.
- `.editorconfig`: 4-space indent, LF line endings.
