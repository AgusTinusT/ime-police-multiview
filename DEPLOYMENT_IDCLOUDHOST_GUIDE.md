# Panduan Lengkap Deploy ke VPS IDCloudHost (Pemula)
## Project: Tactical Police Multiview (Laravel + Vue Inertia)

Panduan ini dibuat khusus untuk pemula yang baru pertama kali menggunakan **IaaS / VPS (Virtual Private Server)**, dengan target provider **IDCloudHost** (lokasi data center Jakarta/Indonesia).

---

## Daftar Isi
1. [Langkah 1: Memilih & Membuat VPS di IDCloudHost](#langkah-1-memilih--membuat-vps-di-idcloudhost)
2. [Langkah 2: Menghubungkan Laptop ke Server (SSH)](#langkah-2-menghubungkan-laptop-ke-server-ssh)
3. [Langkah 3: Instalasi Web Server & PHP (LEMP Stack)](#langkah-3-instalasi-web-server--php-lemp-stack)
4. [Langkah 4: Konfigurasi Database MySQL](#langkah-4-konfigurasi-database-mysql)
5. [Langkah 5: Upload / Clone Source Code Project](#langkah-5-upload--clone-source-code-project)
6. [Langkah 6: Konfigurasi Nginx untuk Laravel](#langkah-6-konfigurasi-nginx-untuk-laravel)
7. [Langkah 7: Menghubungkan Domain & Memasang SSL Gratis (HTTPS)](#langkah-7-menghubungkan-domain--memasang-ssl-gratis-https)
8. [Langkah 8: Mengaktifkan Scheduler / Cron Job (PENTING untuk Sync Stream)](#langkah-8-mengaktifkan-scheduler--cron-job-penting-untuk-sync-stream)
9. [Cheatsheet Perawatan & Update Kode di Masa Depan](#cheatsheet-perawatan--update-kode-di-masa-depan)
10. [Alternatif Paling Mudah: Menggunakan Panel Gratis (CloudPanel)](#alternatif-paling-mudah-menggunakan-panel-gratis-cloudpanel)

---

## Langkah 1: Memilih & Membuat VPS di IDCloudHost

1. Buka [Console IDCloudHost](https://console.idcloudhost.com/) dan login.
2. Masuk ke menu **Compute** -> **Virtual Machine** -> **+ New VM**.
3. Pilih konfigurasi berikut:
   * **Location**: Jakarta (ID).
   * **Operating System**: **Ubuntu 24.04 LTS** atau **Ubuntu 22.04 LTS** (Sangat disarankan untuk stabilitas Laravel).
   * **Specification Package**:
     * **CPU**: 1 Core / 2 Core
     * **RAM**: 2 GB (Minimal yang direkomendasikan agar build npm lancar)
     * **Storage**: 20 GB – 40 GB NVMe SSD
     * *Estimasi Biaya: ~Rp 60.000 – Rp 100.000 / bulan*.
   * **Authentication**: 
     * Buat password root yang kuat (simpan di Notepad) atau gunakan SSH Key.
4. Klik **Create**. Tunggu 1–2 menit hingga VM berstatus **Running** dan Anda mendapatkan **Public IP Address** (misal: `103.xxx.xxx.xxx`).

---

## Langkah 2: Menghubungkan Laptop ke Server (SSH)

Gunakan **PowerShell** atau **Terminal Windows** di laptop Anda:

```bash
ssh root@IP_SERVER_ANDA
```
*(Ganti `IP_SERVER_ANDA` dengan IP publik dari IDCloudHost, misal: `ssh root@103.123.45.67`)*.

* Jika muncul pertanyaan `Are you sure you want to continue connecting (yes/no)?`, ketik `yes` lalu tekan Enter.
* Masukkan password root Anda (karakter password memang tidak akan terlihat saat diketik di terminal, langsung ketik dan tekan Enter).

---

## Langkah 3: Instalasi Web Server & PHP (LEMP Stack)

Setelah berhasil masuk ke server, jalankan perintah di bawah ini secara berurutan:

### 3.1. Update Sistem Operasi
```bash
sudo apt update && sudo apt upgrade -y
```

### 3.2. Install Nginx, Git, Unzip, dan Curl
```bash
sudo apt install nginx git curl unzip ufw -y
```

### 3.3. Install PHP 8.3 dan Ekstensi Laravel
```bash
sudo apt install software-properties-common -y
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

sudo apt install php8.3-fpm php8.3-cli php8.3-mysql php8.3-curl php8.3-mbstring \
php8.3-xml php8.3-zip php8.3-bcmath php8.3-intl php8.3-redis -y
```

### 3.4. Install Composer (PHP Package Manager)
```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### 3.5. Install Node.js (v20 LTS) & NPM
```bash
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install nodejs -y
```
*Verifikasi instalasi:*
```bash
php -v
composer -v
node -v
npm -v
```

---

## Langkah 4: Konfigurasi Database MySQL

### 4.1. Install MySQL Server
```bash
sudo apt install mysql-server -y
sudo systemctl start mysql
sudo systemctl enable mysql
```

### 4.2. Buat Database & Akun Pengguna
Masuk ke terminal MySQL:
```bash
sudo mysql
```

Ketik query SQL berikut di dalam console MySQL (ubah password sesuai keinginan):
```sql
CREATE DATABASE ime_police CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'police_user'@'localhost' IDENTIFIED BY 'PasswordRahasia123!';
GRANT ALL PRIVILEGES ON ime_police.* TO 'police_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

---

## Langkah 5: Upload / Clone Source Code Project

### 5.1. Clone Repository ke Folder Web
```bash
# Berikan izin akses folder /var/www ke user Anda
sudo chown -R $USER:$USER /var/www

cd /var/www
# Ganti dengan URL Repository Git Anda (GitHub / GitLab)
git clone https://github.com/USERNAME/ime-police-multiview.git ime-police-multiview
cd /var/www/ime-police-multiview
```

### 5.2. Atur Hak Akses Folder (Permissions)
Laravel membutuhkan izin tulis (*write permission*) untuk folder `storage` dan `bootstrap/cache`:
```bash
sudo chown -R www-data:www-data /var/www/ime-police-multiview
sudo chmod -R 775 /var/www/ime-police-multiview/storage
sudo chmod -R 775 /var/www/ime-police-multiview/bootstrap/cache
```

### 5.3. Setup File `.env`
Salin template `.env`:
```bash
cp .env.example .env
nano .env
```
Ubah baris-baris berikut di dalam file `.env`:
```ini
APP_NAME="Tactical Police Multiview"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://domainanda.com   <-- ganti dengan domain Anda

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ime_police
DB_USERNAME=police_user
DB_PASSWORD=PasswordRahasia123!   <-- password database di langkah 4

CACHE_STORE=database
SESSION_DRIVER=database
QUEUE_CONNECTION=database
```
*Tekan `CTRL + O`, lalu `Enter` untuk menyimpan, lalu `CTRL + X` untuk keluar dari nano.*

### 5.4. Install Dependensi PHP & Build Frontend (Inertia/Vue)
```bash
# 1. Install dependensi PHP untuk production
composer install --optimize-autoloader --no-dev

# 2. Generate Application Key
php artisan key:generate

# 3. Jalankan Database Migration & Seeder
php artisan migrate --force --seed

# 4. Install & Build Frontend Assets (Vite)
npm install
npm run build

# 5. Buat Cache Konfigurasi Laravel untuk Kecepatan Tinggi
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## Langkah 6: Konfigurasi Nginx untuk Laravel

Buat file konfigurasi virtual host Nginx:
```bash
sudo nano /etc/nginx/sites-available/ime-police
```

Tempelkan (*paste*) konfigurasi berikut:
```nginx
server {
    listen 80;
    server_name domainanda.com www.domainanda.com; # Ganti dengan domain Anda atau IP VPS

    root /var/www/ime-police-multiview/public;
    index index.php index.html index.htm;

    charset utf-8;

    # Caching untuk Assets Vite (JS, CSS, Gambar)
    location ~* \.(ico|css|js|gif|jpe?g|png|woff2?|eot|ttf|svg)$ {
        expires 1y;
        add_header Cache-Control "public, max-age=31536000, immutable";
        access_log off;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Aktifkan konfigurasi dan restart Nginx:
```bash
sudo ln -s /etc/nginx/sites-available/ime-police /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl reload nginx
```

---

## Langkah 7: Menghubungkan Domain & Memasang SSL Gratis (HTTPS)

> **PENTING**: YouTube Live Chat dan Player API **hanya bekerja dengan baik jika web menggunakan HTTPS (SSL)**.

1. **Arahkan DNS Domain ke VPS**:
   * Buka panel domain Anda (Cloudflare, IDCloudHost Domain, Niagahoster, dll).
   * Tambahkan **A Record**:
     * Host/Name: `@` -> Value: `IP_VPS_ANDA`
     * Host/Name: `www` -> Value: `IP_VPS_ANDA`
   * Tunggu propagasi DNS (sekitar 5–15 menit).

2. **Pasang SSL Gratis (Let's Encrypt via Certbot)**:
   ```bash
   sudo apt install certbot python3-certbot-nginx -y
   sudo certbot --nginx -d domainanda.com -d www.domainanda.com
   ```
   * Ikuti instruksi di layar (masukkan email dan setujui ToS). Certbot akan otomatis mengonfigurasi SSL dan mengatur auto-renewal.

---

## Langkah 8: Mengaktifkan Scheduler / Cron Job (PENTING untuk Sync Stream)

Agar server mengecek status live YouTube streamer/polisi di latar belakang tanpa memberatkan pengunjung web:

1. Buka konfigurasi crontab:
   ```bash
   sudo crontab -e
   ```
   *(Jika pertama kali, pilih nomor 1 untuk editor nano).*

2. Tambahkan baris ini di bagian paling bawah:
   ```bash
   * * * * * cd /var/www/ime-police-multiview && php artisan schedule:run >> /dev/null 2>&1
   ```
3. Simpan dan keluar (`CTRL + O`, `Enter`, `CTRL + X`).

Sekarang, Laravel Scheduler akan otomatis menjalankan background sync setiap 3–5 menit sesuai pengaturan di `routes/console.php`.

---

## Cheatsheet Perawatan & Update Kode di Masa Depan

Jika di kemudian hari Anda melakukan perubahan kode di laptop dan ingin mengupdate server:

```bash
cd /var/www/ime-police-multiview

# 1. Tarik kode terbaru dari Git
git pull origin main

# 2. Update dependensi jika ada perubahan
composer install --optimize-autoloader --no-dev
npm install
npm run build

# 3. Jalankan migrasi jika ada tabel baru
php artisan migrate --force

# 4. Refresh Cache Laravel
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Melihat Log Error Jika Ada Masalah:
```bash
# Log Laravel
tail -f /var/www/ime-police-multiview/storage/logs/laravel.log

# Log Nginx
tail -f /var/log/nginx/error.log
```

---

## Alternatif Paling Mudah: Menggunakan Panel Gratis (CloudPanel)

Jika Anda merasa mengetik command Linux terlalu rumit untuk pertama kali, IDCloudHost memiliki fitur **App Catalog**:

1. Saat membuat VM di IDCloudHost, pada pilihan OS pilih **App Catalog** -> **CloudPanel** (atau **aaPanel**).
2. Setelah VM aktif, Anda akan diberikan link URL untuk membuka Dashboard Web (berbasis GUI mirip cPanel/Railway).
3. Di CloudPanel:
   * Anda tinggal klik **+ Add Site** -> **Create a PHP Site (Laravel)**.
   * Tinggal klik tombol untuk pasang SSL Let's Encrypt (1-klik).
   * Tersedia visual file manager, database manager (phpMyAdmin), dan cron job manager langsung dari browser tanpa perlu menghafal perintah terminal.
