# ✂️ Spesifikasi & Peta Pengembangan Fitur Tactical Video Trimmer

Dokumen ini berisi spesifikasi teknis, arsitektur kode, kebutuhan sistem, analisis kelebihan & kekurangan, matriks kendala & solusi (troubleshooting log), serta roadmap pengembangan untuk fitur **Tactical Video Trimmer** pada aplikasi **IME Police Multiview**.

> **Status Fitur**: 🧪 **Beta Test / Active Development** (v1.0-Beta)  
> **Metode Utama**: _Direct Stream Copy_ (`-c copy` via `yt-dlp` & `FFmpeg`)  
> **Batasan Utama**: Maksimal durasi **10 Menit (600 detik)** per klip.

---

## 📌 1. Peta Akses & Pengamanan (Authorization & Access Map)

Fitur ini dirancang khusus untuk penggunaan internal perwira & dispatcher terotorisasi.

### A. Hak Akses (Role Permission)

- **Persyaratan**: User harus terautentikasi (`auth`) dan memiliki kolom `role` bernilai `'admin'` atau `'clipper'`.
- **Mekanisme Pengecekan**:
    - Backend: [`CanTrimVideoMiddleware.php`](file:///c:/Development/laragon/www/ime-police-multiview/app/Http/Middleware/CanTrimVideoMiddleware.php) (Menolak dengan HTTP 403 jika role tidak sesuai).
    - Model: [`User::canTrimVideo()`](file:///c:/Development/laragon/www/ime-police-multiview/app/Models/User.php).
    - Frontend: Dynamic computed `canTrimVideo` pada Vue komponen.

### B. Titik Akses UI (Entry Points)

1. **User Account Dropdown Menu** ([`UserAccountMenu.vue`](file:///c:/Development/laragon/www/ime-police-multiview/resources/js/Components/UserAccountMenu.vue)):
   Menu **`✂️ Tactical Video Trimmer`** di dalam popover avatar profil kanan atas.
2. **Main Header Navigation Bar** ([`PoliceDashboard.vue`](file:///c:/Development/laragon/www/ime-police-multiview/resources/js/Pages/PoliceDashboard.vue)):
   Tombol merah **`Potong Stream`** di baris atas header navigasi utama.
3. **Card Stream Control** ([`Dashboard.vue`](file:///c:/Development/laragon/www/ime-police-multiview/resources/js/Pages/Dashboard.vue)):
   Ikon gunting pada setiap kartu CCTV stream aktif untuk mengambil URL stream secara instan (1-Click).
4. **Global Event Bus**:
   Pemicu custom event JavaScript `window.dispatchEvent(new CustomEvent('open-clipper-modal', { detail: { url } }))`.

---

## 📁 2. Peta Berkas & Arsitektur Kode (Code Architecture Map)

| Layer                        | Berkas Terlibat                                                                                                                                                                | Fungsi Utama                                                                                                                                                             |
| :--------------------------- | :----------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | :----------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Database Migration**       | [`2026_09_19_000001_create_video_clips_table.php`](file:///c:/Development/laragon/www/ime-police-multiview/database/migrations/2026_09_19_000001_create_video_clips_table.php) | Membuat skema tabel `video_clips` (`youtube_url`, `start_time`, `end_time`, `duration_seconds`, `file_path`, `status`, `error_message`).                                 |
| **Eloquent Model**           | [`app/Models/VideoClip.php`](file:///c:/Development/laragon/www/ime-police-multiview/app/Models/VideoClip.php)                                                                 | Model data klip dan relasi `belongsTo(User::class)`.                                                                                                                     |
| **User Model**               | [`app/Models/User.php`](file:///c:/Development/laragon/www/ime-police-multiview/app/Models/User.php)                                                                           | Menambahkan method `canTrimVideo()` dan relasi `hasMany(VideoClip::class)`.                                                                                              |
| **Middleware Auth**          | [`app/Http/Middleware/CanTrimVideoMiddleware.php`](file:///c:/Development/laragon/www/ime-police-multiview/app/Http/Middleware/CanTrimVideoMiddleware.php)                     | Proteksi endpoint API pemotong video dari user biasa / guest.                                                                                                            |
| **Inertia Middleware**       | [`app/Http/Middleware/HandleInertiaRequests.php`](file:///c:/Development/laragon/www/ime-police-multiview/app/Http/Middleware/HandleInertiaRequests.php)                       | Membagikan status `can_trim_video` ke shared props Inertia `$page.props.auth.user`.                                                                                      |
| **App Bootstrap**            | [`bootstrap/app.php`](file:///c:/Development/laragon/www/ime-police-multiview/bootstrap/app.php)                                                                               | Mendaftarkan alias middleware `'can_trim'`.                                                                                                                              |
| **Controller API**           | [`app/Http/Controllers/VideoClipController.php`](file:///c:/Development/laragon/www/ime-police-multiview/app/Http/Controllers/VideoClipController.php)                         | Validasi timestamp, pembatasan max 10 menit (600s), pembersihan URL YouTube, pemicuan Job asinkron, dan manajemen hapus/list/download.                                   |
| **Queue Job Engine**         | [`app/Jobs/ProcessVideoClipJob.php`](file:///c:/Development/laragon/www/ime-police-multiview/app/Jobs/ProcessVideoClipJob.php)                                                 | Eksekusi otomatis `yt-dlp` `--download-sections` dengan `--force-keyframes-at-cuts`, `--force-ipv4`, `Deno JS Engine`, `cookies.txt`, dan FFmpeg `--postprocessor-args`. |
| **Routes**                   | [`routes/web.php`](file:///c:/Development/laragon/www/ime-police-multiview/routes/web.php)                                                                                     | Mendaftarkan endpoint `/api/v1/clips` (GET, POST, DELETE, GET /{id}/download).                                                                                           |
| **Frontend Modal Component** | [`resources/js/Components/VideoClipperModal.vue`](file:///c:/Development/laragon/www/ime-police-multiview/resources/js/Components/VideoClipperModal.vue)                       | Komponen UI modal pemotong video, penanda durasi, histori klip, HTML5 video preview, auto-polling 5 detik, dan tombol download.                                          |

---

## ⚡ 3. Kebutuhan & Prasyarat Sistem (System Requirements)

### A. Environment Backend & Server

1. **PHP**: Version 8.2 / 8.3+.
2. **Laravel**: Version 11 / 12 (`QUEUE_CONNECTION=database`).
3. **Database**: MySQL / MariaDB (tabel `video_clips` & `jobs`).
4. **Binary & Runtime Dependensi**:
    - **`yt-dlp`**: Command Line Video Downloader (Build Nightly direkomendasikan).
    - **`FFmpeg`**: Media Stream Processor.
    - **`Deno`**: JavaScript Engine untuk menyelesaikan tantangan n-sig decipher YouTube (`curl -fsSL https://deno.land/install.sh | sh`).
5. **Supervisor Daemon**:
    - Program Worker: `police-clipper-worker` (`numprocs=1`).
6. **Autentikasi YouTube**:
    - Berkas Cookie: `storage/app/cookies.txt` (Milik user `www-data:www-data`).

---

## ⚖️ 4. Kelebihan & Kekurangan Fitur (Pros & Cons Analysis)

### 🟢 Kelebihan Fitur (Advantages)

1. **Pemotongan Super Cepat (3–6 Detik)**:
   Menggunakan _Direct Stream Copy_ (`-c copy`), sehingga server memotong video tanpa melakukan render ulang CPU yang berat.
2. **Kualitas Tinggi Full HD 1080p (60 FPS)**:
   Mampu mengambil stream video `bv*` (1080p) dan audio `ba` secara terpisah dari YouTube lalu mengombinasikannya dengan lancar.
3. **Non-Blocking User Experience (Asinkron Queue)**:
   Saat user mengklik tombol potong, backend merespons dalam `< 50ms`. User dapat melanjutkan aktivitas tanpa perlu menunggu proses unduh di layar.
4. **Kebal dari Pembatasan Bot YouTube (Rate Limit 429)**:
   Dilengkapi routing `--force-ipv4`, jeda request `--sleep-requests 1.5`, `Deno JS Engine`, serta dukungan berkas `storage/app/cookies.txt`.
5. **Pemutaran & Unduhan Mulus di Browser**:
   Menggunakan `-movflags +faststart` dan `-avoid_negative_ts make_zero` untuk memastikan video dapat diputar & diunduh langsung via browser tanpa masalah audio mati di tengah jalan.

### 🔴 Kekurangan & Keterbatasan Fitur (Known Limitations)

1. **Ketergantungan pada Algoritma YouTube**:
   YouTube secara berkala memperbarui enkripsi player. `yt-dlp` di VPS wajib diperbarui secara berkala (`yt-dlp --update-to nightly`).
2. **Sensitivitas Keyframe (GOP Alignment)**:
   Karena menggunakan `-c copy` demi kecepatan, titik potong bergantung pada I-Frame terdekat dari YouTube. Potongan video mungkin meleset 0.5–1 detik dari timestamp persis yang dimasukkan.
3. **Risiko Penumpukan Disk SSD VPS**:
   Klip video `.mp4` disimpan di SSD VPS. Tanpa pembersihan berkala, penyimpanan SSD 20 GB bisa penuh (Disolusikan dengan cronjob pembersihan file > 2 hari).
4. **Ketergantungan pada Berkas Cookies Admin**:
   Jika sesi akun YouTube pada file `cookies.txt` di-logout atau kadaluarsa (biasanya > 6–12 bulan), berkas `cookies.txt` perlu diekspor ulang oleh Admin.

---

## 🛠️ 5. Knowledge Base Kendala & Solusi Troubleshooting (Troubleshooting Log)

Berikut adalah daftar kendala nyata yang ditemukan selama pengujian beserta solusi penanganannya:

| Kendala / Error Log                                                            | Penyebab Utama                                                                                                       | Solusi Penanganan                                                                                                                                                                                                         |
| :----------------------------------------------------------------------------- | :------------------------------------------------------------------------------------------------------------------- | :------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| **HTTP Error 429: Too Many Requests**                                          | Alamat IPv6 VPS Datacenter terkena _rate limit_ sementara dari YouTube karena request berulang.                      | 1. Gunakan `--force-ipv4` dan `--sleep-requests 1.5` di `yt-dlp`.<br>2. Upload berkas `cookies.txt` ke `storage/app/cookies.txt`.<br>3. Cooldown 10–15 menit.                                                             |
| **WARNING: n challenge solving failed / ERROR: The page needs to be reloaded** | YouTube mewajibkan JavaScript Engine (Deno) untuk mengeksekusi script cipher n-sig.                                  | 1. Pasang Deno JS Engine: `curl -fsSL https://deno.land/install.sh \| sh && sudo cp /root/.deno/bin/deno /usr/local/bin/deno`.<br>2. Gunakan `--extractor-args "youtube:player_client=android_creator,web_creator,mweb"`. |
| **Audio Hilang/Mati Setelah Detik 10–20 Saat Diunduh Browser**                 | Pergeseran timestamp PTS/DTS audio & video dari YouTube (`-c copy`) serta posisi header `moov` di akhir berkas file. | 1. Tambahkan postprocessor FFmpeg: `-movflags +faststart -avoid_negative_ts make_zero`.<br>2. Gunakan endpoint download khusus (`GET /api/v1/clips/{id}/download`) dengan `Content-Disposition: attachment`.              |
| **PermissionError: Permission denied (`storage/app/cookies.txt`)**             | Berkas `cookies.txt` dibuat oleh user `root`, sehingga user web `www-data` tidak memiliki izin tulis.                | Jalankan perintah kepemilikan di VPS:<br>`sudo chown -R www-data:www-data /var/www/ime-police-multiview/storage`<br>`sudo chmod -R 775 /var/www/ime-police-multiview/storage`                                             |
| **Route [clips.download] not defined (Error 500)**                             | Route Cache pada server VPS belum diperbarui sehingga Laravel melempar `RouteNotFoundException`.                     | 1. Gunakan `url('/api/v1/clips/' . $clip->id . '/download')` di Controller.<br>2. Jalankan `php artisan route:clear` di VPS.                                                                                              |
| **Proses Clipping Hangg / Timeout pada Video > 1 Menit**                       | Penggunaan `dispatchSync()` memaksa proses berjalan di dalam HTTP request PHP-FPM hingga 504 Timeout.                | Gunakan asinkron queue **`ProcessVideoClipJob::dispatch($clip)`** dan jalankan Supervisor Worker (`numprocs=1`).                                                                                                          |

---

## 🚀 6. Potensi Pengembangan Kelanjutan (Feature Roadmap)

1. **Interactive Range Slider & Visual Timeline**: Slider 2 handle (Start & End) untuk memilih detik potong secara visual.
2. **HQ Accurate Mode (`-c:v libx264`)**: Opsi pilihan antara _Fast Mode (-c copy)_ vs _Accurate Mode (Re-encoding)_ untuk presisi milidetik.
3. **Automated Incident Watermarking**: Overlay otomatis logo SASP/LSPD, Callsign Perwira, dan tanggal kejadian pada pojok klip.
4. **Auto-Cloud Storage Upload (S3 / Cloudinary)**: Mengunggah file `.mp4` ke Cloud Storage publik dan langsung menghapus file lokal di VPS untuk menghemat SSD.
5. **Direct Dispatch Sharing (Discord Webhook)**: Tombol 1-Click _"Bagikan ke Discord"_ untuk mengirim klip kejadian ke channel Discord SASP Command Center.

---

_Dokumen ini diperbarui untuk pengembangan SASP Police Duty Multiview — IME Roleplay Community._
