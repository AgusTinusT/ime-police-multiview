# Perencanaan Consolidate Multi-Project VPS (IDCloudHost)

Dokumen ini berisi analisis, arsitektur skema, estimasi biaya, serta langkah konfigurasi untuk menggabungkan beberapa project (misalnya dari Railway) ke dalam **1 Virtual Private Server (VPS) IDCloudHost**.

---

## 📊 1. Ringkasan Perbandingan & Biaya

### Kondisi Awal vs Target Konsolidasi

| Parameter          | Ex-Existing (Railway)                                            | Target Konsolidasi (IDCloudHost VPS)                                   |
| :----------------- | :--------------------------------------------------------------- | :--------------------------------------------------------------------- |
| **Model Hosting**  | PaaS (Per-service billing)                                       | IaaS / VPS (Flat rate billing)                                         |
| **Spesifikasi**    | 0.5 vCPU & 0.5 GB RAM (MySQL)<br>0.5 vCPU & 0.5 GB RAM (Laravel) | **2 vCPU, 2 GB RAM, 20 GB NVMe SSD**                                   |
| **Sistem Operasi** | Isolated Container                                               | Ubuntu 24.04 LTS                                                       |
| **Estimasi Biaya** | **$5 - $10 / bulan** (~Rp 150.000+ untuk 2 project)              | **~Rp 60.000 – Rp 85.000 / bulan** _($4.5 – $5.5)_ (Untuk 2-4 Project) |
| **Penghematan**    | -                                                                | **Hemat ~50% – 60% per bulan**                                         |

---

## 🏗️ 2. Skema Arsitektur Multi-Project & Staging

Berikut adalah skema alur traffic, reverse proxy, serta pembagian database & storage dalam 1 VPS (termasuk Environment Staging):

```mermaid
graph TD
    UserID[3 Users - Indonesia] -->|Fast Latency <20ms| CF[Cloudflare CDN & DNS]
    UserDE[1 User - Berlin, Germany] -->|Cached Assets / Latency ~170ms| CF

    CF -->|SSL / Port 443| Nginx[Nginx Reverse Proxy / Web Server]

    subgraph VPS IDCloudHost (Ubuntu 24.04 LTS - 2 vCPU / 2 GB RAM / 20 GB SSD)
        Nginx -->|police.domain.com| P1[Project 1: Production<br>/var/www/ime-police-multiview]
        Nginx -->|staging.domain.com| P1_STG[Project 1: Staging<br>/var/www/ime-police-staging]
        Nginx -->|app2.domain.com| P2[Project 2: Secondary App<br>/var/www/project-dua]

        P1 --> DB1[(MySQL: db_ime_police)]
        P1_STG --> DB1_STG[(MySQL: db_ime_police_staging)]
        P2 --> DB2[(MySQL: db_project_dua)]
    end
```

---

## 💻 3. Alokasi Resource & Budget RAM (2 GB RAM + 2 GB Swap)

Agar VPS dengan **2 GB RAM** tidak mengalami crash atau _Out of Memory (OOM)_ saat memproses multiple Laravel apps & `npm run build`:

```
+-----------------------------------------------------------------------+
|                       TOTAL RAM VPS: 2,048 MB                         |
+-----------------------------------------------------------------------+
|  [Ubuntu OS & System Services]  : ~300 MB                             |
|  [MySQL 8.0 Server (Shared)]    : ~350 MB                             |
|  [Nginx Web Server (Shared)]    : ~30 MB                              |
|  [PHP 8.3 FPM Pool (Multi-App)] : ~120 MB (4-6 workers)               |
|  [Buffer & Cache / Idle RAM]    : ~1,248 MB (Sisa Aman)               |
+-----------------------------------------------------------------------+
|  [SWAP MEMORY ON DISK]          : 2,048 MB (Safety Net saat Build)    |
+-----------------------------------------------------------------------+
```

---

## 🌐 4. Optimasi Multi-Region (3 User Indonesia, 1 User Berlin)

1. **Lokasi Server**: VPS IDCloudHost berada di Data Center Jakarta.
    - **Pengguna Indonesia**: Latensi sangat rendah (**< 20 ms**).
    - **Pengguna Berlin**: Latensi langsung sekitar **160–190 ms** (tetap responsif untuk web app Laravel/Vue).
2. **Penggunaan Cloudflare CDN (Gratis)**:
    - Mengintegrasikan DNS ke Cloudflare.
    - Static Assets (JS, CSS, Images, Fonts) diproses oleh Edge Server Cloudflare terdekat di Eropa untuk pengguna di Berlin.

---

## 🛠️ 5. Panduan Step-by-Step Setup Multi-Project

### Langkah 1: Aktivasi Swap Memory (Wajib)

Jalankan di terminal VPS via SSH:

```bash
sudo fallocate -l 2G /swapfile
sudo chmod 600 /swapfile
sudo mkswap /swapfile
sudo swapon /swapfile
echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab
```

---

### Langkah 2: Struktur Directory Multi-Project & Staging

Setiap project dipisahkan folder-nya di dalam `/var/www/`:

```bash
/var/www/
├── ime-police-multiview/      # Production (Police Multiview)
├── ime-police-staging/        # Staging / Development (Police Multiview)
└── project-dua/               # Project 2 (App Lain)
```

---

### Langkah 3: Setup Database MySQL Multi-Schema

Buat database & user terpisah di MySQL untuk isolasi keamanan:

```sql
sudo mysql

-- Production Multiview
CREATE DATABASE db_ime_police CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'user_police'@'localhost' IDENTIFIED BY 'PasswordPolice123!';
GRANT ALL PRIVILEGES ON db_ime_police.* TO 'user_police'@'localhost';

-- Staging Multiview
CREATE DATABASE db_ime_police_staging CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'user_police_stg'@'localhost' IDENTIFIED BY 'PasswordPoliceStg123!';
GRANT ALL PRIVILEGES ON db_ime_police_staging.* TO 'user_police_stg'@'localhost';

-- Project 2
CREATE DATABASE db_project_dua CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'user_project2'@'localhost' IDENTIFIED BY 'PasswordProject2_123!';
GRANT ALL PRIVILEGES ON db_project_dua.* TO 'user_project2'@'localhost';

FLUSH PRIVILEGES;
EXIT;
```

---

### Langkah 4: Konfigurasi Virtual Host Nginx

#### File Config Production (`/etc/nginx/sites-available/ime-police`)

```nginx
server {
    listen 80;
    server_name police.domainanda.com;
    root /var/www/ime-police-multiview/public;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

#### File Config Staging (`/etc/nginx/sites-available/ime-police-staging`)

```nginx
server {
    listen 80;
    server_name staging-police.domainanda.com;
    root /var/www/ime-police-staging/public;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

#### Aktifkan Config Nginx:

```bash
sudo ln -s /etc/nginx/sites-available/ime-police /etc/nginx/sites-enabled/
sudo ln -s /etc/nginx/sites-available/ime-police-staging /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

---

### Langkah 5: SSL Gratis (Certbot Let's Encrypt)

Pasang SSL HTTPS otomatis untuk seluruh domain:

```bash
sudo certbot --nginx -d police.domainanda.com -d staging-police.domainanda.com -d app2.domainanda.com
```

---

## 🧪 6. Skema & Penerapan Environment Staging

Penerapan environment **Staging** di VPS sangat disarankan untuk menguji coba fitur baru sebelum dirilis ke Production:

1. **Keuntungan Staging di 1 VPS**:
    - **0 Biaya Tambahan**: Memanfaatkan kapasitas VPS yang ada.
    - **RAM Hemat**: Saat tidak diuji coba, Staging mengonsumsi 0% CPU dan < 15MB RAM.
2. **Alur Kerja (Workflow)**:
    - **Update Fitur Baru**: Push kode ke branch `staging` di Git.
    - **Pull di Server**: `cd /var/www/ime-police-staging && git pull origin staging && npm run build`.
    - **Uji Coba**: Buka `https://staging-police.domainanda.com` untuk verifikasi.
    - **Rilis ke Production**: Jika fitur aman, merge branch `staging` ke `main`, lalu `git pull` di `/var/www/ime-police-multiview`.

---

## 💾 7. Strategi Backup Berkala (Manual & Instant)

Untuk pemula, ada 3 metode backup manual yang paling aman dan mudah dilakukan:

### Opsi A: Backup Instant 1-Klik via Dashboard IDCloudHost (Super Aman)

- Buka **Console IDCloudHost** -> **Compute** -> **Virtual Machine** -> Pilih VM Anda.
- Masuk ke tab **Snapshots** -> Klik **+ Create Snapshot**.
- **Keunggulan**: Mengambil foto utuh (_full image backup_) seluruh isi VPS (OS, Nginx, MySQL, semua file) dalam hitungan detik. Jika Anda salah ketik command di server, Anda bisa melakukan **Restore Snapshot** dalam 2 menit.

---

### Opsi B: Manual Backup Script 1-Command (`bash /root/manual-backup.sh`)

Anda dapat membuat script backup sederhana di VPS. Saat ingin backup sebelum update besar, cukup jalankan `bash /root/manual-backup.sh` via SSH.

1. Buat file script:
    ```bash
    sudo nano /root/manual-backup.sh
    ```
2. Isi script berikut:

    ```bash
    #!/bin/bash
    BACKUP_DIR="/root/backups/$(date +%Y-%m-%d_%H%M%S)"
    mkdir -p $BACKUP_DIR

    echo "==> Backing up MySQL Databases..."
    mysqldump -u root db_ime_police | gzip > $BACKUP_DIR/db_ime_police.sql.gz
    mysqldump -u root db_project_dua | gzip > $BACKUP_DIR/db_project_dua.sql.gz

    echo "==> Backing up Uploaded Storage Files..."
    tar -czf $BACKUP_DIR/storage_police.tar.gz /var/www/ime-police-multiview/storage/app/public

    echo "==> Backup Complete! Files stored in: $BACKUP_DIR"
    ls -lh $BACKUP_DIR
    ```

3. Beri izin eksekusi:
    ```bash
    sudo chmod +x /root/manual-backup.sh
    ```

---

### Opsi C: Download File Backup ke Laptop (via SCP / FileZilla)

Jika ingin mengunduh hasil backup `.sql.gz` ke laptop lokal Anda:

Gunakan **PowerShell** di laptop:

```bash
scp root@IP_VPS_ANDA:/root/backups/2026-09-16_150000/db_ime_police.sql.gz C:\Users\Username\Downloads\
```

Atau gunakan aplikasi GUI seperti **FileZilla** / **WinSCP** via protokol SFTP (Port 22).

---

## 💡 8. Tips & Best Practices Pemula Mengelola VPS Multi-Project

Jika ini pertama kalinya Anda mengelola VPS untuk banyak project sekaligus, ikuti panduan praktis berikut agar server tetap aman, stabil, dan mudah dirawat:

### A. Isolasi Worker PHP-FPM (Agar Crash 1 Project Tidak Mengganggu Project Lain)

Secara default, semua project menggunakan pool PHP-FPM yang sama (`www.conf`). Agar Project 2 (4 user aktif) **bebas crash** saat Project 1 (Multiview) dibanjiri pengunjung, buatkan pool terpisah:

1. Salin konfigurasi pool default:
    ```bash
    sudo cp /etc/php/8.3/fpm/pool.d/www.conf /etc/php/8.3/fpm/pool.d/project2.conf
    ```
2. Edit `/etc/php/8.3/fpm/pool.d/project2.conf`:
    ```ini
    [project2]
    user = www-data
    group = www-data
    listen = /var/run/php/php8.3-fpm-project2.sock
    pm = dynamic
    pm.max_children = 5
    pm.start_servers = 2
    pm.min_spare_servers = 1
    pm.max_spare_servers = 3
    ```
3. Di file Nginx Project 2 (`/etc/nginx/sites-available/project-dua`), ganti `fastcgi_pass`:
    ```nginx
    fastcgi_pass unix:/var/run/php/php8.3-fpm-project2.sock;
    ```
4. Reload PHP FPM: `sudo systemctl reload php8.3-fpm`

---

### B. Cheatsheet Perintah Monitoring Server Harian

Jalankan perintah ini di SSH untuk memantau kesehatan VPS secara berkala:

| Kebutuhan             | Perintah Terminal             | Kegunaan                                                      |
| :-------------------- | :---------------------------- | :------------------------------------------------------------ |
| **Cek RAM & Swap**    | `free -h`                     | Melihat sisa RAM bebas & Swap yang terpakai                   |
| **Cek Sisa Disk SSD** | `df -h`                       | Memastikan ruang penyimpanan 20GB tidak penuh                 |
| **Cek CPU & Proses**  | `htop` atau `top`             | Melihat aplikasi mana yang paling banyak memakan CPU          |
| **Cek Service Aktif** | `sudo systemctl status nginx` | Memastikan Nginx, PHP, dan MySQL berstatus `active (running)` |

---

### C. Penyelamat Saat Terjadi Masalah (Emergency Recovery Checklist)

Jika salah satu project lambat atau tidak bisa dibuka:

1. **Restart Service Utama (1-Klik Recovery)**:
    ```bash
    sudo systemctl restart nginx
    sudo systemctl restart php8.3-fpm
    sudo systemctl restart mysql
    ```
2. **Melihat Log Error Nginx (Global Error)**:
    ```bash
    tail -n 50 -f /var/log/nginx/error.log
    ```
3. **Melihat Log Error Laravel (Application Error)**:
    ```bash
    tail -n 50 -f /var/www/ime-police-multiview/storage/logs/laravel.log
    ```

---

### D. Keamanan Dasar VPS Pemula (Security Hardening)

1. **Aktifkan UFW Firewall (Hanya Buka Port Penting)**:
    ```bash
    sudo ufw allow 22/tcp    # SSH
    sudo ufw allow 80/tcp    # HTTP
    sudo ufw allow 443/tcp   # HTTPS
    sudo ufw enable
    ```
2. **Pisahkan User & Password Database**:
    - **JANGAN** gunakan user `root` MySQL di file `.env` aplikasi Anda.
    - Buat user MySQL terpisah untuk setiap database project.

---

## 📌 Kesimpulan

Dengan skema konsolidasi ke **VPS IDCloudHost (2 vCPU / 2 GB RAM)** ini:

- Seluruh project terintegrasi dalam 1 server dengan biaya flat **~Rp 60.000 - Rp 85.000 / bulan**.
- Resource server sangat memadai untuk menampung hingga 4 project web aplikasi kecil-menengah + 1 Environment Staging.
- Akses pengguna dari Indonesia maupun Berlin dijamin cepat dan teroptimasi menggunakan Cloudflare CDN.
- Dengan menerapkan **Pemisahan Pool PHP-FPM**, **Snapshot IDCloudHost**, serta **Script Manual Backup**, pengelolaan server menjadi sangat aman, terisolasi, dan mudah ditangani bahkan untuk pemula.
