#!/usr/bin/env bash
# Update this checkout from origin/main and rebuild it.
# Works in both VPS folders (ime-police-staging and ime-police-multiview):
#
#   cd /path/to/ime-police-staging && bash scripts/deploy.sh
#
# Composer and npm only reinstall when their lock files changed.
# Pass --maintenance to show the maintenance page while it runs.
set -euo pipefail

BRANCH="main"
MAINTENANCE=false
[[ "${1:-}" == "--maintenance" ]] && MAINTENANCE=true

cd "$(dirname "$0")/.."
echo "==> $(pwd)"

# `npm install` on the server rewrites package-lock.json; git's copy is the source of truth.
if ! git diff --quiet -- package-lock.json; then
    echo "==> package-lock.json diubah di server (npm install), dikembalikan ke versi git"
    git checkout -- package-lock.json
fi

# Local edits to tracked files would be overwritten or block the pull.
if ! git diff --quiet || ! git diff --cached --quiet; then
    echo "!! Ada perubahan lokal pada file yang dilacak git. Batalkan atau simpan dulu:" >&2
    git status --short --untracked-files=no >&2
    exit 1
fi

BEFORE=$(git rev-parse HEAD)
git fetch origin "$BRANCH"
git merge --ff-only "origin/$BRANCH"
AFTER=$(git rev-parse HEAD)

if [[ "$BEFORE" == "$AFTER" ]]; then
    echo "==> Sudah versi terbaru ($(git log -1 --format='%h %s'))"
    exit 0
fi

echo "==> ${BEFORE:0:7} -> ${AFTER:0:7}"
git log --oneline "$BEFORE..$AFTER" | head -20

changed() { ! git diff --quiet "$BEFORE" "$AFTER" -- "$@"; }

if $MAINTENANCE; then
    php artisan down --retry=30 || true
    trap 'php artisan up' EXIT
fi

if changed composer.lock; then
    echo "==> composer install"
    composer install --no-dev --optimize-autoloader --no-interaction
fi

if changed package-lock.json; then
    echo "==> npm ci"
    npm ci
fi

echo "==> npm run build"
# A leftover hot file makes Laravel load assets from a dev server.
rm -f public/hot
npm run build

echo "==> migrate"
php artisan migrate --force

echo "==> cache"
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Workers keep old code in memory until restarted.
php artisan queue:restart

echo "==> Selesai: $(git log -1 --format='%h %s')"
