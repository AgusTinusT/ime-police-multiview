#!/usr/bin/env bash
# Siapkan lingkungan staging di VPS yang sama dengan production (sekali jalan).
# Jalankan di VPS sebagai user deploy (bukan root); sudo dipakai bila perlu.
#
#   bash scripts/setup-staging.sh --domain staging.domain-anda.com [opsi]
#
# Opsi:
#   --domain <host>      subdomain staging (wajib)
#   --dir <folder>       folder staging           (default: /var/www/ime-police-staging)
#   --prod-dir <folder>  folder production         (default: /var/www/ime-police-multiview)
#   --db <nama>          database staging          (default: ime_police_staging)
#   --db-user <nama>     user MySQL staging        (default: ime_staging)
#   --copy-data          salin isi database production ke staging (menimpa data staging)
#   --workers            pasang worker queue staging di Supervisor (clipper + sync)
#   --no-nginx           jangan buat konfigurasi nginx
#
# Aman dijalankan ulang: langkah yang sudah selesai dilewati. .env staging yang sudah ada
# tidak ditimpa, kecuali nilai yang wajib berbeda dari production (lihat apply_staging_env).
set -Eeuo pipefail

domain="" dir="/var/www/ime-police-staging" prod_dir="/var/www/ime-police-multiview"
db="ime_police_staging" db_user="ime_staging"
copy_data=false workers=false nginx=true
branch="staging"

step() { printf '\n\033[1;34m==> %s\033[0m\n' "$*"; }
warn() { printf '\033[1;33m[peringatan] %s\033[0m\n' "$*" >&2; }
fail() { printf '\033[1;31m[gagal] %s\033[0m\n' "$*" >&2; exit 1; }

while [[ $# -gt 0 ]]; do
    case "$1" in
        --domain) domain="${2:?}"; shift 2 ;;
        --dir) dir="${2:?}"; shift 2 ;;
        --prod-dir) prod_dir="${2:?}"; shift 2 ;;
        --db) db="${2:?}"; shift 2 ;;
        --db-user) db_user="${2:?}"; shift 2 ;;
        --copy-data) copy_data=true; shift ;;
        --workers) workers=true; shift ;;
        --no-nginx) nginx=false; shift ;;
        -h|--help) sed -n '2,20p' "$0" | sed 's/^# \{0,1\}//'; exit 0 ;;
        *) fail "Opsi tidak dikenal: $1" ;;
    esac
done

[[ -n "$domain" ]] || fail "--domain wajib diisi, mis. --domain staging.domain-anda.com"
[[ "$(id -u)" -ne 0 ]] || fail "Jalankan sebagai user deploy (mis. turnipserver), bukan root."
[[ "$db" =~ ^[A-Za-z0-9_]+$ && "$db_user" =~ ^[A-Za-z0-9_]+$ ]] || fail "Nama database/user hanya boleh huruf, angka, dan _."
[[ -f "$prod_dir/.env" ]] || fail "Production tidak ditemukan di $prod_dir (.env tidak ada)."
[[ "$(realpath -m "$dir")" != "$(realpath -m "$prod_dir")" ]] || fail "Folder staging tidak boleh sama dengan production."

# Ambil nilai dari file .env (tanpa tanda kutip)
env_get() {
    local value
    value="$(grep -E "^$2=" "$1" | tail -n 1 | cut -d= -f2- || true)"
    value="${value%\"}"; value="${value#\"}"; value="${value%\'}"; value="${value#\'}"
    printf '%s' "$value"
}

# Set KEY=VALUE di .env (ganti baris yang ada, atau tambahkan di akhir)
env_set() {
    local file="$1" key="$2" value="$3" escaped
    escaped="$(printf '%s' "$value" | sed -e 's/[\/&|]/\\&/g')"
    if grep -qE "^$key=" "$file"; then
        sed -i -E "s|^$key=.*|$key=$escaped|" "$file"
    else
        printf '%s=%s\n' "$key" "$value" >> "$file"
    fi
}

prod_db="$(env_get "$prod_dir/.env" DB_DATABASE)"
[[ "$prod_db" != "$db" ]] || fail "Database staging ($db) sama dengan production. Pakai --db nama lain."

step "1. Branch '$branch' di GitHub"
remote_url="$(git -C "$prod_dir" remote get-url origin)"
git ls-remote --exit-code --heads "$remote_url" "$branch" >/dev/null \
    || fail "Branch '$branch' belum ada di GitHub. Dari komputer Anda: git push origin main:$branch"
echo "OK ($remote_url)"

step "2. Clone ke $dir"
if [[ -d "$dir/.git" ]]; then
    echo "Sudah ada, dilewati."
else
    sudo install -d -o "$USER" -g www-data "$dir"
    git clone --branch "$branch" "$remote_url" "$dir"
fi
cd "$dir"
[[ "$(git rev-parse --abbrev-ref HEAD)" == "$branch" ]] || git checkout "$branch"

step "3. Database $db"
db_pass_file="$dir/storage/.staging-db-password"
mkdir -p storage
if [[ -f .env ]] && [[ "$(env_get .env DB_DATABASE)" == "$db" ]] && [[ -n "$(env_get .env DB_PASSWORD)" ]]; then
    db_pass="$(env_get .env DB_PASSWORD)"
elif [[ -f "$db_pass_file" ]]; then
    db_pass="$(cat "$db_pass_file")"
else
    db_pass="$(openssl rand -hex 20)"
    (umask 077; printf '%s' "$db_pass" > "$db_pass_file")
fi
sudo mysql <<SQL
CREATE DATABASE IF NOT EXISTS \`$db\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '$db_user'@'localhost' IDENTIFIED BY '$db_pass';
ALTER USER '$db_user'@'localhost' IDENTIFIED BY '$db_pass';
GRANT ALL PRIVILEGES ON \`$db\`.* TO '$db_user'@'localhost';
FLUSH PRIVILEGES;
SQL
echo "Database dan user siap (password tersimpan di .env staging)."

step "4. .env staging"
if [[ -f .env ]]; then
    echo ".env sudah ada; hanya nilai khusus staging yang dipastikan."
else
    cp "$prod_dir/.env" .env
    env_set .env APP_KEY ""
    echo ".env disalin dari production."
fi
chmod 640 .env

# Nilai yang wajib berbeda dari production, supaya staging tidak menyentuh data, cookie,
# email, notifikasi Discord, atau analytics milik production.
apply_staging_env() {
    local app_name
    app_name="$(env_get "$prod_dir/.env" APP_NAME)"
    env_set .env APP_NAME "\"${app_name:-App} (Staging)\""
    env_set .env APP_ENV staging
    env_set .env APP_DEBUG false
    env_set .env APP_URL "https://$domain"
    env_set .env DB_DATABASE "$db"
    env_set .env DB_USERNAME "$db_user"
    env_set .env DB_PASSWORD "$db_pass"
    env_set .env SESSION_COOKIE ime_police_staging_session
    env_set .env SESSION_DOMAIN null
    env_set .env CACHE_PREFIX ime_police_staging_
    env_set .env REDIS_PREFIX ime_police_staging_
    env_set .env MAIL_MAILER log
    env_set .env DISCORD_FEEDBACK_WEBHOOK ""
    env_set .env DISCORD_TACTICAL_WEBHOOK_URL ""
    env_set .env GA_MEASUREMENT_ID ""
    env_set .env GOOGLE_SITE_VERIFICATION ""
}
apply_staging_env
rm -f "$db_pass_file"

step "5. Izin folder"
mkdir -p storage/app/public storage/framework/{cache/data,sessions,views} storage/logs bootstrap/cache
sudo chown -R "$USER":www-data storage bootstrap/cache
sudo chmod -R ug+rwX storage bootstrap/cache
sudo find storage bootstrap/cache -type d -exec chmod g+s {} +

step "6. Composer dan APP_KEY"
composer install --no-dev --optimize-autoloader --no-interaction
if [[ -z "$(env_get .env APP_KEY)" ]]; then
    php artisan key:generate --force
else
    echo "APP_KEY sudah ada."
fi

if $copy_data; then
    step "7. Salin database production -> staging"
    prod_user="$(env_get "$prod_dir/.env" DB_USERNAME)"
    prod_pass="$(env_get "$prod_dir/.env" DB_PASSWORD)"
    prod_host="$(env_get "$prod_dir/.env" DB_HOST)"
    MYSQL_PWD="$prod_pass" mysqldump --single-transaction --quick --no-tablespaces \
        -h "${prod_host:-127.0.0.1}" -u "$prod_user" "$prod_db" \
        | MYSQL_PWD="$db_pass" mysql -h 127.0.0.1 -u "$db_user" "$db"
    echo "Data production disalin. File unggahan (storage/app) tidak ikut disalin."
fi

step "8. Deploy pertama (build + migrate)"
bash scripts/deploy.sh --branch "$branch" --force
[[ -L public/storage ]] || php artisan storage:link

if $nginx; then
    step "9. nginx untuk $domain"
    site="/etc/nginx/sites-available/ime-police-staging"
    fpm_sock="$(ls /run/php/php8.3-fpm.sock /run/php/php*-fpm.sock 2>/dev/null | head -n 1 || true)"
    [[ -n "$fpm_sock" ]] || fail "Socket PHP-FPM tidak ditemukan di /run/php."
    if [[ -f "$site" ]]; then
        echo "$site sudah ada, tidak ditimpa."
    else
        sudo tee "$site" >/dev/null <<NGINX
server {
    listen 80;
    listen [::]:80;
    server_name $domain;
    root $dir/public;
    index index.php;
    charset utf-8;
    client_max_body_size 64M;

    # Staging tidak boleh diindeks mesin pencari
    add_header X-Robots-Tag "noindex, nofollow" always;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    location ~ ^/index\.php(/|$) {
        fastcgi_pass unix:$fpm_sock;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT \$realpath_root;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* { deny all; }
}
NGINX
    fi
    sudo ln -sf "$site" /etc/nginx/sites-enabled/ime-police-staging
    sudo nginx -t
    sudo systemctl reload nginx

    if command -v certbot >/dev/null 2>&1; then
        if sudo grep -rqs "managed by Certbot" /etc/nginx/sites-available/; then
            step "10. SSL (Certbot)"
            sudo certbot --nginx -d "$domain" --redirect --non-interactive --keep-until-expiring \
                || warn "Certbot gagal. Pastikan DNS $domain sudah mengarah ke VPS, lalu: sudo certbot --nginx -d $domain"
        else
            warn "Production tidak memakai Certbot (mungkin SSL lewat Cloudflare). SSL staging tidak dipasang."
        fi
    fi
fi

if $workers; then
    step "11. Worker queue staging (Supervisor)"
    conf="/etc/supervisor/conf.d/police-staging.conf"
    sudo tee "$conf" >/dev/null <<SUP
[program:police-staging-clipper]
command=php $dir/artisan queue:listen database --queue=clipper --timeout=600 --tries=1
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=$dir/storage/logs/clipper_worker.log

[program:police-staging-sync]
command=php $dir/artisan queue:work database --queue=sync,default --timeout=180 --memory=256 --sleep=3 --tries=1 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=$dir/storage/logs/sync_worker.log
SUP
    sudo supervisorctl reread
    sudo supervisorctl update
fi

cat <<DONE

$(printf '\033[1;32m')Staging siap: https://$domain$(printf '\033[0m')

Langkah berikutnya:
  1. DNS: record A "$domain" -> IP VPS ini (lewat Cloudflare bila production juga lewat Cloudflare).
  2. GitHub Secret VPS_PATH_STAGING = $dir
     Setelah itu setiap "git push origin staging" otomatis deploy ke sini.
  3. Scheduler (sync YouTube, backup, reset bulanan) SENGAJA tidak dipasang untuk staging,
     supaya tidak memakai kuota API atau menjalankan tugas ganda. Jalankan manual bila perlu:
       php artisan schedule:run
DONE
