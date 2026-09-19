# 📘 Panduan Edukasi & Knowledge Base: Troubleshooting Multi-Project 1 VPS

Dokumen ini berisi panduan komprehensif, solusi teknis, dan analisis penyebab masalah umum yang sering terjadi saat mengelola **banyak project aplikasi (Laravel/NodeJS/Web App)** di dalam **1 Virtual Private Server (VPS)** yang sama.

---

## 📑 Daftar Isi
1. [⚡ 1. Konflik Berbeda Versi PHP (Multi PHP Version)](#1-konflik-berbeda-versi-php-multi-php-version)
2. [🍪 2. Bentrokan Sesi & Cookie Login (Session & Cache Collision)](#2-bentrokan-sesi--cookie-login-session--cache-collision)
3. [🐢 3. Penyebab Server Lambat, Lelempar, & Hang (Performance Bottlenecks)](#3-penyebab-server-lambat-lelempar--hang-performance-bottlenecks)
4. [🌐 4. Masalah Port & Virtual Host Nginx](#4-masalah-port--virtual-host-nginx)
5. [⏱️ 5. Bentrokan Cron Job Scheduler & Queue Worker](#5-bentrokan-cron-job-scheduler--queue-worker)
6. [🔐 6. Masalah Izin Berkas (Permission Denied `www-data` vs `root`)](#6-masalah-izin-berkas-permission-denied-www-data-vs-root)
7. [🚑 7. Tabel Penyelamat Cepat (Emergency Troubleshooting Matrix)](#7-tabel-penyelamat-cepat-emergency-troubleshooting-matrix)

---

## ⚡ 1. Konflik Berbeda Versi PHP (Multi PHP Version)

### 📌 Masalah yang Terjadi:
Project A (Legacy / Laravel 9) membutuhkan **PHP 8.1**, sedangkan Project B (Laravel 11) membutuhkan **PHP 8.3**. Jika perintah `php` di CLI hanya mengarah ke 1 versi, maka `composer install` atau `php artisan` di salah satu project akan error.

### 🛠️ Solusi Penanganan:

#### A. Install Multiple PHP-FPM di Ubuntu:
Gunakan PPA dari Ondřej Surý untuk memasang beberapa versi PHP sekaligus:
```bash
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install php8.1-fpm php8.1-cli php8.1-mysql php8.1-xml php8.1-mbstring php8.1-curl -y
sudo apt install php8.3-fpm php8.3-cli php8.3-mysql php8.3-xml php8.3-mbstring php8.3-curl -y
```

#### B. Arahkan Nginx ke Sock PHP Sesuai Versi:
Di file konfigurasi Nginx masing-masing project (`/etc/nginx/sites-available/`):

* **Project A (PHP 8.1):**
  ```nginx
  location ~ \.php$ {
      fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
      fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
      include fastcgi_params;
  }
  ```
* **Project B (PHP 8.3):**
  ```nginx
  location ~ \.php$ {
      fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
      fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
      include fastcgi_params;
  }
  ```

#### C. Menjalankan Command Artisan / Composer per Versi PHP:
Saat menjalankan perintah di terminal SSH, panggil versi binary PHP secara spesifik:

```bash
# Untuk Project A (PHP 8.1):
php8.1 artisan migrate
php8.1 /usr/local/bin/composer install

# Untuk Project B (PHP 8.3):
php8.3 artisan migrate
php8.3 /usr/local/bin/composer install
```

---

## 🍪 2. Bentrokan Sesi & Cookie Login (Session & Cache Collision)

### 📌 Masalah yang Terjadi:
User membuka Project A lalu membuka Project B di browser yang sama. Tiba-tiba user ter-logout otomatis dari Project A, atau data cache Project A tertimpa oleh data Project B.

### 🛠️ Solusi Penanganan:
Laravel secara default menggunakan nama cookie `laravel_session` dan prefix cache kosong. Pada setiap project di 1 VPS, ubah nilai berikut di berkas `.env` masing-masing:

* **Project A (`/var/www/project-a/.env`):**
  ```env
  APP_NAME="Project Alpha"
  SESSION_COOKIE="project_alpha_session"
  CACHE_PREFIX="project_alpha_cache_"
  ```
* **Project B (`/var/www/project-b/.env`):**
  ```env
  APP_NAME="Project Beta"
  SESSION_COOKIE="project_beta_session"
  CACHE_PREFIX="project_beta_cache_"
  ```

Setelah mengubah `.env`, wajib jalankan:
```bash
php artisan config:clear && php artisan config:cache
```

---

## 🐢 3. Penyebab Server Lambat, Lelempar, & Hang (Performance Bottlenecks)

Di VPS berkapasitas hemat (seperti 2 GB RAM / 2 vCPU), berikut 5 pembunuh utama performa server:

### A. Kompilasi Aset (`npm run build`) Langsung di VPS
* **Penyebab:** Perintah `npm run build` / `vite build` memakan CPU 100% dan RAM hingga 1.5 GB. Jika dijalankan saat server ramai, VPS akan **hang / unresponsive**.
* **Solusi:** Jalankan `npm run build` di **komputer lokal (PC dev)** Anda, lalu upload folder `public/build` yang sudah jadi ke server melalui Git / SCP.

### B. Beban Request Polling Frontend Berlebihan
* **Penyebab:** Aplikasi yang memiliki fitur polling otomatis di frontend (seperti fetch API setiap 5-15 detik) tanpa response caching akan membanting MySQL & PHP-FPM worker secara konstan.
* **Solusi:**
  1. Bungkus query berat di Controller dengan `Cache::remember('key', 30, function() { ... });`.
  2. Gunakan WebSockets (Pusher / Soketi) jika memungkinkan daripada polling HTTP berulang.

### C. Pembengkakan Berkas Log (`laravel.log` & `access.log`)
* **Penyebab:** Log yang ditulis terus menerus bisa berukuran hingga puluhan GigaByte (GB) dan memenuhi 100% disk SSD. Jika disk 100% penuh, MySQL akan crash otomatis (*Read-Only mode*).
* **Solusi:**
  1. Di `.env`, atur `LOG_STACK=daily` dan `LOG_LEVEL=error` (jangan `debug` di production).
  2. Pasang `logrotate` otomatis di Ubuntu atau rutin bersihkan log:
     ```bash
     echo "" > /var/www/project-a/storage/logs/laravel.log
     ```

### D. Kehabisan PHP-FPM Worker (Error 502 / 504 Gateway Timeout)
* **Penyebab:** Pool PHP-FPM default mengizinkan `pm.max_children = 5`. Jika ada 6 request simultan yang lambat, request ke-6 akan kena timeout.
* **Solusi:** Buat Pool terpisah per project di `/etc/php/8.3/fpm/pool.d/` dan batasi `pm.max_children` secara proporsional sesuai RAM VPS.

---

## 🌐 4. Masalah Port & Virtual Host Nginx

### 📌 Masalah yang Terjadi:
Gagal membuka project baru karena mengonfigurasi Port 80/443 secara bentrok atau salah mengarahkan `root` path.

### 🛠️ Solusi Penanganan:
* **JANGAN** menggunakan nomor port berbeda di URL publik (seperti `domain.com:8080`), karena akan diblokir oleh firewall ISP / IT kantor.
* **Gunakan Subdomain / Virtual Host Nginx** pada Port 80 & 443 standar:
  - `police.domain.com` -> `/var/www/project-a/public`
  - `toko.domain.com` -> `/var/www/project-b/public`
* Sertifikat SSL Let's Encrypt / Certbot dapat dipasang bersamaan dalam 1 perintah:
  ```bash
  sudo certbot --nginx -d police.domain.com -d toko.domain.com
  ```

---

## ⏱️ 5. Bentrokan Cron Job Scheduler & Queue Worker

### 📌 Masalah yang Terjadi:
Cron Job dari Project A mengeksekusi perintah di folder Project B, atau queue worker memproses jobs milik aplikasi lain.

### 🛠️ Solusi Penanganan:

#### A. Format Cron Job (`crontab -e`) Harus Spesifik Direktori:
```bash
# SALAH (Bisa salah direktori kerja):
* * * * * php /var/www/project-a/artisan schedule:run >> /dev/null 2>&1

# BENAR (Eksplisit pindah folder terlebih dahulu):
* * * * * cd /var/www/project-a && php8.3 artisan schedule:run >> /dev/null 2>&1
* * * * * cd /var/www/project-b && php8.1 artisan schedule:run >> /dev/null 2>&1
```

#### B. Isolasi Supervisor Queue Worker (Jika Menggunakan Queue):
Buat file konfigurasi Supervisor terpisah di `/etc/supervisor/conf.d/`:

* `/etc/supervisor/conf.d/project-a.conf`:
  ```ini
  [program:project-a-worker]
  process_name=%(program_name)s_%(process_num)02d
  command=php8.3 /var/www/project-a/artisan queue:work --sleep=3 --tries=3
  autostart=true
  autorestart=true
  user=www-data
  numprocs=2
  ```

---

## 6. Masalah Izin Berkas (Permission Denied `www-data` vs `root`)

### 📌 Masalah yang Terjadi:
Saat Anda menjalankan `php artisan` atau `git pull` menggunakan user `root` di SSH, file log / cache baru dibuat dengan pemilik `root:root`. Nginx (`www-data`) tidak bisa menulis file tersebut sehingga muncul error **`500 Internal Server Error`** atau `Permission Denied`.

### 🛠️ Solusi Penanganan:
Setelah melakukan maintenance sebagai `root`, selalu kembalikan hak kepemilikan folder `storage` dan `bootstrap/cache` ke user `www-data`:

```bash
sudo chown -R www-data:www-data /var/www/project-a/storage /var/www/project-a/bootstrap/cache
sudo chmod -R 775 /var/www/project-a/storage /var/www/project-a/bootstrap/cache
```

---

## 🚑 7. Tabel Penyelamat Cepat (Emergency Troubleshooting Matrix)

| Gejala / Pesan Error | Penyebab Utama | Langkah Penanganan Cepat |
| :--- | :--- | :--- |
| **502 Bad Gateway** | Service `php-fpm` mati / crash | `sudo systemctl restart php8.3-fpm` |
| **504 Gateway Timeout** | Script PHP hanging / query DB terlalu lama | Cek `htop`, kill proses lambat, restart PHP-FPM & Nginx |
| **419 Page Expired** | Session Cookie bentrok / CSRF mismatch | Ubah `SESSION_COOKIE` di `.env` & `php artisan config:clear` |
| **SQLSTATE[28000] Access Denied** | Password/User MySQL salah di `.env` | Cek `.env` & pastikan GRANT PRIVILEGES user MySQL sudah sesuai |
| **Disk Space 100% Full** | File `laravel.log` atau Nginx `access.log` membengkak | Jalankan `df -h`, cari log besar & kosongkan dengan `echo "" > file.log` |
| **User Ter-logout Sendiri** | Nama `SESSION_COOKIE` antar project sama | Buat `SESSION_COOKIE` unik per project di `.env` |
| **Syntax Error / Unsupported Version** | Salah menggunakan versi binary PHP | Jalankan dengan binary versi spesifik (misal `php8.3 artisan ...`) |

---

> 💡 **Tips Emas:** *Selalu gunakan **Snapshot Backup** di dashboard provider VPS (seperti IDCloudHost/DigitalOcean) sebelum Anda melakukan update besar atau instalasi software baru di server.*
