# Panduan Kontribusi

Terima kasih sudah tertarik berkontribusi di **IME Police Multiview**!

## Alur Branch & Pull Request

- `main` — branch produksi (hanya menerima merge lewat Pull Request).
- `staging` — branch uji coba sebelum masuk ke `main`.
- Buat branch baru dari `staging` (atau `main` untuk hotfix), misalnya:
  - `feat/nama-fitur`
  - `fix/nama-bug`
  - `refactor/nama-modul`

1. Fork / buat branch dari `staging`.
2. Kerjakan perubahan Anda beserta test yang relevan.
3. Pastikan seluruh test dan pengecekan UI lolos sebelum membuka Pull Request ke `staging`.

## Setup Lokal

Butuh PHP 8.3+, Composer, dan Node.js 22. `.env.example` sudah disiapkan untuk lokal: SQLite, email ditulis ke log, dan integrasi luar (YouTube, Discord) kosong.

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php -r "touch('database/database.sqlite');"
php artisan migrate --seed
```

`migrate --seed` mengisi `database/database.sqlite` dan membuat akun demo dengan password `password123`, misalnya `admin@ime-rp.com`. Akun demo hanya untuk lokal; seeder tidak membuatnya di production atau staging.

Jalankan dev server:

```bash
php artisan serve
npm run dev
```

## Pengecekan Wajib Sebelum PR

```bash
# Jalankan seluruh test backend (SQLite in-memory)
php artisan test

# Cek aturan Tactical UI Kit & build produksi Vite
npm run check
```

## Konvensi Proyek

- Panduan arsitektur, konvensi Laravel/Inertia, dan aturan umum ada di `GEMINI.md`.
- Panduan desain komponen & token Tailwind v4 (`shadcn-vue` + Tactical UI Kit) ada di `docs/design-system/README.md` dan `docs/design-system/GEMINI-UI-PLAYBOOK.md`.
- Jangan pernah meng-commit `.env`, kredensial, atau konfigurasi server pribadi.

## Etika dan Keamanan

- Ikuti [Kode Etik](CODE_OF_CONDUCT.md) di issue, PR, dan diskusi.
- Celah keamanan **jangan** dilaporkan lewat issue publik; ikuti [SECURITY.md](SECURITY.md).
- Template issue dan PR tersedia; isi checklist PR sebelum meminta review.
