#!/usr/bin/env bash
# Update this checkout from origin/main and rebuild it.
# Works in both VPS folders (ime-police-staging and ime-police-multiview):
#
#   cd /path/to/ime-police-staging && bash scripts/deploy.sh
#
# Options:
#   --maintenance  show the maintenance page while it runs
#   --force        rebuild even when there are no new commits
#
# Composer and npm reinstall only when their lock file differs from what was
# last installed in this folder.
set -euo pipefail

# Everything runs inside main(): bash parses the whole function before running
# it, so the pull below can safely replace this file.
main() {
    local branch="main" maintenance=false force=false arg
    for arg in "$@"; do
        case "$arg" in
            --maintenance) maintenance=true ;;
            --force) force=true ;;
            *) echo "Opsi tidak dikenal: $arg" >&2; exit 1 ;;
        esac
    done

    cd "$(dirname "$0")/.."
    echo "==> $(pwd)"

    # `npm install` on the server rewrites package-lock.json; git's copy is the source of truth.
    if ! git diff --quiet -- package-lock.json; then
        echo "==> package-lock.json diubah di server (npm install), dikembalikan ke versi git"
        git checkout -- package-lock.json
    fi

    # Reset bootstrap/cache/.gitignore jika sempat tersentuh otomatis di server
    if ! git diff --quiet -- bootstrap/cache/.gitignore 2>/dev/null; then
        echo "==> bootstrap/cache/.gitignore diubah di server, dikembalikan ke versi git"
        git checkout -- bootstrap/cache/.gitignore 2>/dev/null || true
    fi

    # Local edits to tracked files would be overwritten or block the pull.
    if ! git diff --quiet || ! git diff --cached --quiet; then
        echo "!! Ada perubahan lokal pada file yang dilacak git. Batalkan atau simpan dulu:" >&2
        git status --short --untracked-files=no >&2
        exit 1
    fi

    local before after
    before=$(git rev-parse HEAD)
    git fetch origin "$branch"
    git merge --ff-only "origin/$branch"
    after=$(git rev-parse HEAD)

    if [[ "$before" == "$after" ]] && ! $force; then
        echo "==> Sudah versi terbaru ($(git log -1 --format='%h %s')). Pakai --force untuk build ulang."
        exit 0
    fi

    if [[ "$before" != "$after" ]]; then
        echo "==> ${before:0:7} -> ${after:0:7}"
        git log --oneline "$before..$after" | head -20
    fi

    if $maintenance; then
        php artisan down --render="errors.503" --retry=30 || true
        trap 'php artisan up' EXIT
    fi

    if lock_stale composer.lock vendor/.deploy-lock; then
        echo "==> composer install"
        composer install --no-dev --optimize-autoloader --no-interaction
        lock_hash composer.lock > vendor/.deploy-lock
    fi

    if lock_stale package-lock.json node_modules/.deploy-lock; then
        echo "==> npm ci"
        npm ci
        lock_hash package-lock.json > node_modules/.deploy-lock
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
    php artisan event:cache 2>/dev/null || true

    echo "==> periksa permission storage & bootstrap/cache"
    chmod -R 775 storage bootstrap/cache 2>/dev/null || true
    if command -v sudo >/dev/null 2>&1; then
        sudo chown -R "${USER:-www-data}":www-data storage bootstrap/cache 2>/dev/null || true
        sudo chmod -R 775 storage bootstrap/cache 2>/dev/null || true
    fi

    # Workers keep old code in memory until restarted.
    echo "==> restart queue worker"
    php artisan queue:restart

    # Reload PHP-FPM untuk membersihkan OPcache jika systemctl tersedia
    if command -v systemctl >/dev/null 2>&1; then
        for fpm in php8.3-fpm php8.4-fpm php-fpm; do
            if systemctl is-active --quiet "$fpm" 2>/dev/null; then
                echo "==> reload $fpm (flush opcache)"
                sudo systemctl reload "$fpm" 2>/dev/null || true
                break
            fi
        done
    fi

    echo "==> Selesai: $(git log -1 --format='%h %s')"
}

lock_hash() { sha1sum "$1" | cut -d' ' -f1; }

# True when the lock file differs from the one recorded at the last install.
lock_stale() { [[ "$(lock_hash "$1")" != "$(cat "$2" 2>/dev/null)" ]]; }

main "$@"
