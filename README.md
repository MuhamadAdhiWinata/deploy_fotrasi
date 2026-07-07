# FortasiMupa

Aplikasi absensi & tugas siswa SMK Pakem.

## Akun Default

| Role      | Email              | Password   |
|-----------|--------------------|------------|
| Admin     | admin@fortasi.test | password   |
| Siswa     | ahmad@fortasi.test | password   |

## Docker

```bash
docker compose up -d
```

Buka `http://localhost`.

## Development (tanpa Docker)

```bash
composer setup
composer dev
```

## Testing

```bash
composer test
```

## Stack

Laravel 13 + Livewire 3 + Volt + Tailwind CSS v4 + MySQL 8.0.
