# ✂️ Spesifikasi & Peta Pengembangan Fitur Tactical Video Trimmer

Dokumen ini berisi spesifikasi teknis, arsitektur kode, kebutuhan sistem, kelemahan, serta roadmap pengembangan kelanjutan untuk fitur **Tactical Video Trimmer** pada aplikasi **IME Police Multiview**.

> **Status Fitur**: 🧪 **Beta Test / Active Development** (v1.0-Beta)  
> **Metode Utama**: *Direct Stream Copy* (`-c copy` via `yt-dlp` & `FFmpeg`)  
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

| Layer | Berkas Terlibat | Fungsi Utama |
| :--- | :--- | :--- |
| **Database Migration** | [`2026_09_19_000001_create_video_clips_table.php`](file:///c:/Development/laragon/www/ime-police-multiview/database/migrations/2026_09_19_000001_create_video_clips_table.php) | Membuat skema tabel `video_clips` (`youtube_url`, `start_time`, `end_time`, `duration_seconds`, `file_path`, `status`, `error_message`). |
| **Eloquent Model** | [`app/Models/VideoClip.php`](file:///c:/Development/laragon/www/ime-police-multiview/app/Models/VideoClip.php) | Model data klip dan relasi `belongsTo(User::class)`. |
| **User Model** | [`app/Models/User.php`](file:///c:/Development/laragon/www/ime-police-multiview/app/Models/User.php) | Menambahkan method `canTrimVideo()` dan relasi `hasMany(VideoClip::class)`. |
| **Middleware Auth** | [`app/Http/Middleware/CanTrimVideoMiddleware.php`](file:///c:/Development/laragon/www/ime-police-multiview/app/Http/Middleware/CanTrimVideoMiddleware.php) | Proteksi endpoint API pemotong video dari user biasa / guest. |
| **Inertia Middleware** | [`app/Http/Middleware/HandleInertiaRequests.php`](file:///c:/Development/laragon/www/ime-police-multiview/app/Http/Middleware/HandleInertiaRequests.php) | Membagikan status `can_trim_video` ke shared props Inertia `$page.props.auth.user`. |
| **App Bootstrap** | [`bootstrap/app.php`](file:///c:/Development/laragon/www/ime-police-multiview/bootstrap/app.php) | Mendaftarkan alias middleware `'can_trim'`. |
| **Controller API** | [`app/Http/Controllers/VideoClipController.php`](file:///c:/Development/laragon/www/ime-police-multiview/app/Http/Controllers/VideoClipController.php) | Validasi timestamp, pembatasan max 10 menit (600s), pembersihan URL YouTube, pemicuan Job, dan manajemen hapus/list. |
| **Queue Job Engine** | [`app/Jobs/ProcessVideoClipJob.php`](file:///c:/Development/laragon/www/ime-police-multiview/app/Jobs/ProcessVideoClipJob.php) | Eksekusi otomatis `yt-dlp` `--download-sections` dengan `--force-keyframes-at-cuts` & `--hls-use-mpegts`. |
| **Routes** | [`routes/web.php`](file:///c:/Development/laragon/www/ime-police-multiview/routes/web.php) | Mendaftarkan endpoint `/api/v1/clips` (GET, POST, DELETE). |
| **Frontend Modal Component** | [`resources/js/Components/VideoClipperModal.vue`](file:///c:/Development/laragon/www/ime-police-multiview/resources/js/Components/VideoClipperModal.vue) | Komponen UI modal pemotong video, penanda durasi, histori klip, HTML5 video preview, dan tombol download. |
| **Main View Pages** | [`PoliceDashboard.vue`](file:///c:/Development/laragon/www/ime-police-multiview/resources/js/Pages/PoliceDashboard.vue) & [`Dashboard.vue`](file:///c:/Development/laragon/www/ime-police-multiview/resources/js/Pages/Dashboard.vue) | Integrasi tombol potong stream & listener modal global. |
| **Global Layout Host** | [`resources/js/Layouts/TacticalLayout.vue`](file:///c:/Development/laragon/www/ime-police-multiview/resources/js/Layouts/TacticalLayout.vue) | Host global modal pemotong video agar bisa dibuka dari halaman mana saja. |

---

## ⚡ 3. Kebutuhan & Prasyarat Sistem (System Requirements)

### A. Environment Backend & Server
1. **PHP**: Version 8.2 / 8.3+.
2. **Laravel**: Version 11 / 12.
3. **Database**: MySQL / MariaDB (tabel `video_clips`).
4. **Binary Dependensi**:
   - **`yt-dlp`** (Command Line Video Downloader).
   - **`FFmpeg`** (Media Stream Processor).
5. **Path Binary (Otomatis)**:
   - *Lokal (Windows/Laragon)*: Terpasang di `storage/app/bin/yt-dlp.exe` & `storage/app/bin/ffmpeg.exe`.
   - *Produksi (Ubuntu VPS)*: Terpasang di `/usr/local/bin/yt-dlp` & `/usr/bin/ffmpeg`.

### B. Storage & Permissions
- Folder penyimpanan file `.mp4`: `storage/app/public/clips/`.
- Permintaan URL publik: `php artisan storage:link` (`public/storage/clips/`).

---

## ⚠️ 4. Kelemahan & Keterbatasan Fitur Saat Ini (Known Limitations)

1. **Sensitivitas Keyframe (GOP Alignment)**:
   - Karena menggunakan *Direct Stream Copy* (`-c copy`) demi pemotongan super cepat (tanpa render ulang), titik potong bergantung pada I-Frame terdekat dari YouTube.
   - Pada kasus tertentu, detik-detik awal video mungkin mengalami *audio-sync delay* tipis atau gambar sedikit berkedip di awal.
2. **Lag pada Live Stream yang Sedang Tayang**:
   - Untuk stream yang **sedang tayang LIVE saat itu juga**, segmen 5–10 detik terakhir kadang belum sepenuhnya di-*flush* oleh server YouTube.
3. **Penumpukan File di Server (Disk Usage)**:
   - File klip `.mp4` yang dihasilkan langsung tersimpan di VPS. Jika tidak ada pembersihan berkala, penyimpanan SSD VPS bisa cepat penuh.
4. **Perubahan Algoritma YouTube**:
   - YouTube sewaktu-waktu dapat memperbarui sistem enkripsi atau player-nya, sehingga `yt-dlp` harus di-update secara berkala (`yt-dlp -U`).

---

## 🚀 5. Potensi Pengembangan Kelanjutan (Feature Roadmap)

Berikut adalah ide dan fitur potensi pengembangan yang bisa diterapkan pada tahap rilis berikutnya:

### 🎯 Phase 1: Visual Timeline & Interactive Seek Preview
- **Interactive Range Slider**: Menambahkan slider garis waktu (*range slider*) interaktif dengan 2 handle (Start & End) agar pengguna bisa menggeser waktu potong secara visual tanpa mengetik angka manual.
- **Thumbnail Hover Preview**: Menampilkan thumbnail preview gambar video ketika kursor diarahkan ke slider waktu.

### 🎥 Phase 2: Frame-Accurate Dual Mode Trimming
- **Opsi Fast Mode vs HQ Accurate Mode**:
  - *Fast Mode (`-c copy`)*: Pemotongan super cepat dalam 3–5 detik.
  - *HQ Accurate Mode (`-c:v libx264`)*: Memotong hingga presisi milidetik dengan merender ulang frame.

### 🏷️ Phase 3: Automated Incident Watermarking & Overlay
- **Taktis Timestamp & Logo Overlay**: Menambahkan watermark otomatis berupa logo SASP/LSPD, Callsign Perwira, dan tanggal kejadian pada bagian pojok klip video.

### ☁️ Phase 4: Auto-Cloud Storage Upload (S3 / Cloudinary / GDrive)
- Mengunggah file `.mp4` yang selesai dipotong langsung ke Cloud Storage publik (seperti AWS S3 atau Cloudinary) dan menghapus file lokal di VPS untuk menghemat ruang disk VPS.

### 📢 Phase 5: Direct Dispatch Sharing (Discord Webhook Integration)
- Tombol 1-Click **"Bagikan ke Discord"** untuk langsung mengirimkan klip kejadian taktis ke channel Discord SASP Command Center / Emergency Dispatch.

### 🧹 Phase 6: Automated Storage Cleanup Cronjob
- Menambahkan *scheduled task* Laravel (`php artisan schedule:run`) yang otomatis menghapus klip video berusia lebih dari 3 hari.

---

_Dokumen ini diperbarui untuk pengembangan SASP Police Duty Multiview — IME Roleplay Community._
