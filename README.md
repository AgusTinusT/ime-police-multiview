# 🚔 IME Police Multiview

A tactical CCTV and bodycam-style multiview command center for the **IME Roleplay Police Division** (GTA V roleplay): live YouTube patrol streams on one wall, an officer directory, duty rankings, a clip tool, and Tactical Bio-Link pages for officers.

> Fan-made community project. Not affiliated with or endorsed by Rockstar Games or Take-Two Interactive.

## Features

- **Multiview wall**: auto-fit, 2×2, 3×3, and 4×4 grids, plus a focus layout (1 large stream with thumbnails). Only one stream plays audio at a time.
- **Bodycam HUD** on each feed: officer name, callsign, rank, department badge, REC indicator, and clock.
- **Departments and divisions**: LSPD, BCSO, SASP, SAPR, SWAT, AIR-1, Traffic, and K9, with agencies, ranks, and divisions stored in the database.
- **Officer directory and rankings**: monthly duty leaderboard, hiatus detection, subscriber milestones ("Road to 1K").
- **Admin dispatcher hub** (`/admin/officers`): manage officers, verify YouTube channels, and trigger sync.
- **Tactical Bio-Link**: public officer profile pages (`/@slug`) with claim and moderation flow.
- **RP toolkit**: 10-codes reference, MDT scratchpad, quick feed injector, and Signal 100 alert banner.

## Tech stack

- **Backend:** Laravel 13, PHP 8.3+
- **Frontend:** Inertia.js 2, Vue 3, Tailwind CSS 4 (shadcn-vue components), Vite
- **Database:** MySQL/MariaDB in production; SQLite for local development and tests
- **Queue:** database driver, with workers for the clipper and stream sync

## Local development

Requirements: PHP 8.3+ (extensions `pdo_sqlite`, `mbstring`, `intl`, `gd`, `zip`), Composer, and Node.js 22.

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php -r "touch('database/database.sqlite');"
php artisan migrate --seed   # schema and demo data
```

Run the app (two terminals):

```bash
php artisan serve
npm run dev
```

Open http://localhost:8000.

### Demo accounts (local only)

The seeders create demo users with the password `password123`, for example `admin@ime-rp.com` (admin) and `free.tester@ime-rp.com` (member). They are skipped when `APP_ENV` is `production` or `staging`. See `database/seeders/UserSeeder.php` for the full list.

## Useful commands

```bash
php artisan make:admin                 # create or update an admin user (interactive)
php artisan officer:sync               # sync live streams of registered channels
php artisan officer:sync-subscribers   # refresh subscriber counts
php artisan officer:manage list        # CLI officer manager (list|add|delete)
```

Scheduled tasks are defined in `routes/console.php`. In production, run `php artisan schedule:run` every minute from cron.

## Tests and checks

```bash
php artisan test   # PHPUnit (SQLite in-memory)
npm run check      # Tactical UI Kit lint and production Vite build
```

CI runs both on every pull request.

## Contributing

Contributions are welcome. Read [CONTRIBUTING.md](CONTRIBUTING.md) for the branch flow (`staging` → `main`), local setup, and project conventions, and follow the [Code of Conduct](CODE_OF_CONDUCT.md).

To report a security issue, **do not open a public issue**; follow [SECURITY.md](SECURITY.md).

## Deployment

Pushing to `main` deploys to production and pushing to `staging` deploys to staging, after CI passes. See [docs/DEPLOY_GITHUB_ACTIONS.md](docs/DEPLOY_GITHUB_ACTIONS.md). A production `.env` template is in `.env.production.example`.

## License

No license has been chosen yet. Until a `LICENSE` file is added, all rights are reserved by the author.
