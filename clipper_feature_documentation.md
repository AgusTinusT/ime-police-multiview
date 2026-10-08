# Dokumentasi Pembaruan Fitur ClipStudio & Officer Delegation

Dokumen ini berisi rincian arsitektur, manifest perubahan file, aturan otorisasi, serta rencana pengembangan (roadmap) integrasi sistem pembayaran untuk fitur **ClipStudio (Tactical Video Trimmer & Media Hub)** pada platform **IME Police Multiview**.

---

## 1. Ringkasan Eksekutif

Fitur **ClipStudio** dirancang sebagai salah satu daya jual utama platform untuk menarik registrasi pengguna baru sekaligus mempermudah tugas dokumentasi kedinasan (Streamer RP/Faction) dan pembuatan konten media (YouTube/TikTok/Reels).

### Solusi Utama Yang Diimplementasikan:
1. **Model Freemium Terbuka untuk Umum**: Halaman `/clipper` dapat diakses oleh semua orang (Guest Teaser), reguler member (3 klip/hari), dan tier khusus.
2. **Desentralisasi Hak Penunjukan Officer (Officer Delegation)**: Mengurangi beban verifikasi manual developer/admin dengan memberikan hak kepada setiap Officer & Admin untuk menunjuk hingga **2 akun member** secara langsung ke kategori Clipper via email.
3. **Matriks 3-Tingkat Tier**:
   - **Free Member**: 3 Klip / Hari (Durasi maks 1 Menit).
   - **Creator Tier (IME Clipper)**: 10 Klip / Hari (Durasi maks 5 Menit).
   - **Ultimate Tier (Official Officer & VIP Supporter)**: Unlimited Klip (Durasi maks 10 Menit + Hak 2 Slot Penunjukan Officer).

---

## 2. Manifest Perubahan File (File Changes Manifest)

Berikut adalah daftar lengkap file yang dibuat dan diubah dalam implementasi pembaruan ini:

### 🗄️ Database & Migrasi
- [`database/migrations/2026_10_08_000001_create_clipper_requests_table.php`](file:///c:/Development/laragon/www/ime-police-multiview/database/migrations/2026_10_08_000001_create_clipper_requests_table.php): Membuat tabel antrean pengajuan role clipper (`clipper_requests`).
- [`database/migrations/2026_10_08_000002_add_clipper_fields_to_users_and_requests.php`](file:///c:/Development/laragon/www/ime-police-multiview/database/migrations/2026_10_08_000002_add_clipper_fields_to_users_and_requests.php): Menambahkan kolom `clipper_tier` pada tabel `users` serta `request_type` & `channel_url` pada `clipper_requests`.
- [`database/migrations/2026_10_08_000003_create_clipper_delegations_table.php`](file:///c:/Development/laragon/www/ime-police-multiview/database/migrations/2026_10_08_000003_create_clipper_delegations_table.php): Membuat tabel `clipper_delegations` untuk mencatat penunjukan 2 slot per officer.

### 🧠 Backend Models & Helpers
- [`app/Models/User.php`](file:///c:/Development/laragon/www/ime-police-multiview/app/Models/User.php): Menambahkan relasi penunjukan, method `isAdmin()`, `isOfficer()`, `canDelegateClipper()`, `canTrimVideo()`, `getClipperTier()`, `getClipperTierName()`, `getDailyClipsLimit()`, dan `getMaxClipDurationSec()`.
- [`app/Models/ClipperRequest.php`](file:///c:/Development/laragon/www/ime-police-multiview/app/Models/ClipperRequest.php): Model data pengajuan role clipper.
- [`app/Models/ClipperDelegation.php`](file:///c:/Development/laragon/www/ime-police-multiview/app/Models/ClipperDelegation.php): Model data penunjukan slot officer.

### ⚙️ Backend Controllers
- [`app/Http/Controllers/VideoClipController.php`](file:///c:/Development/laragon/www/ime-police-multiview/app/Http/Controllers/VideoClipController.php): Penanganan pemotongan video dinamis berbasis tier (durasi & kuota harian).
- [`app/Http/Controllers/ClipperRequestController.php`](file:///c:/Development/laragon/www/ime-police-multiview/app/Http/Controllers/ClipperRequestController.php): Penanganan formulir pengajuan 3 kategori (`officer`, `creator`, `vip`) dan persetujuan admin.
- [`app/Http/Controllers/ClipperDelegationController.php`](file:///c:/Development/laragon/www/ime-police-multiview/app/Http/Controllers/ClipperDelegationController.php): API endpoint penunjukan 2 slot per officer (`index`, `assign`, `revoke`).
- [`app/Http/Controllers/UserManagementController.php`](file:///c:/Development/laragon/www/ime-police-multiview/app/Http/Controllers/UserManagementController.php): Penanganan CRUD akun member & penyesuaian role (`officer`, `clipper`, `member`, `admin`) dan `clipper_tier` di admin dashboard.

### 🛣️ Routing & Middleware
- [`app/Http/Middleware/HandleInertiaRequests.php`](file:///c:/Development/laragon/www/ime-police-multiview/app/Http/Middleware/HandleInertiaRequests.php): Membagikan properti `is_officer`, `can_delegate_clipper`, `clipper_tier`, `daily_clips_limit`, dll., ke Inertia auth user state.
- [`routes/web.php`](file:///c:/Development/laragon/www/ime-police-multiview/routes/web.php): Pendaftaran API endpoints `/api/v1/clipper-delegations` & `/api/v1/clipper-requests`.

### 🖥️ Frontend Views & Components
- [`resources/js/Pages/Clipper.vue`](file:///c:/Development/laragon/www/ime-police-multiview/resources/js/Pages/Clipper.vue): Halaman utama ClipStudio (`max-w-screen-2xl`), Matriks Benefit Tier, Modal Pengajuan Hak Spesial, dan Modal Kelola Penunjukan Officer (2 Slot).
- [`resources/js/Pages/Admin/OfficerManagement.vue`](file:///c:/Development/laragon/www/ime-police-multiview/resources/js/Pages/Admin/OfficerManagement.vue): Dashboard Admin Dispatcher untuk mengelola user accounts, menambahkan opsi role `officer` dan dropdown `clipper_tier`, serta badge visual.
- [`resources/js/Components/UserAccountMenu.vue`](file:///c:/Development/laragon/www/ime-police-multiview/resources/js/Components/UserAccountMenu.vue): Pembaruan navigasi menu profil (bebas emoji, menggunakan badge teks & icon SVG).

---

## 3. Matriks Matriks Perbandingan Tier & Benefit

| Fitur / Benefit | Free Member | Creator Tier (IME Clipper) | Ultimate Tier (Officer / VIP) |
| :--- | :---: | :---: | :---: |
| **Akses Pengguna** | Publik / Guest & Member | Editor & Creator Konten | Officer Kedinasan & VIP |
| **Kuota Pemotongan Hari Ini** | 3 Klip / Hari | 10 Klip / Hari | **UNLIMITED** |
| **Durasi Maksimal per Klip** | 1 Menit (60 detik) | 5 Menit (300 detik) | **10 Menit (600 detik)** |
| **Format Output** | MP4 720p, 360p, MP3 | MP4 1080p, MP3, GIF | Lossless MP4, MP3, GIF |
| **DVR Live Stream HLS Cut** | ❌ (Terbatas) | ✅ Aktif | ✅ Prioritas Tinggi |
| **Server Queue Priority** | Standard | High Priority | **Server Top Priority** |
| **Hak Penunjukan Clipper (2 Slot)** | ❌ | ❌ | **✅ Memiliki 2 Slot Penunjukan** |

---

## 4. Mekanisme Penunjukan Officer (Officer Delegation Rules)

1. **Otorisasi Terkunci**: Hanya pengguna dengan `role === 'officer'` atau `role === 'admin'` yang berhak menunjuk. Akun ber-role `clipper` tidak dapat menunjuk pengguna lain.
2. **Kuota 2 Akun**: Setiap Officer memiliki batas **maksimal 2 slot terpakai**.
3. **Sistem Pencabutan (Revoke)**: Kapan saja Officer mencabut penunjukan pengguna, slot akan kembali menjadi `0/2` atau `1/2` dan pengguna target otomatis kembali ke status **Free Member**.
4. **Instan Tanpa Review Admin**: Penunjukan oleh Officer langsung mempromosikan target pengguna ke status **Clipper (Creator/Ultimate Tier)** secara seketika.

---

## 5. Rencana Pengembangan Masa Depan (Future Monetization Roadmap)

Fitur ClipStudio telah dipersiapkan agar mudah diintegrasikan dengan modul monetisasi di masa depan:

### A. Integrasi Payment Gateway (Midtrans / Xendit / TriPay)
- **VIP Supporter Auto-Upgrade**: Pengguna dapat membeli paket VIP Supporter (misal: Rp 25.000 / bulan) untuk otomatis membuka **Ultimate Tier (Unlimited Klip 10 Menit)** via Webhook Payment Gateway tanpa perlu pengajuan manual.

### B. Top-up Kuota Klip Tambahan (Pay-Per-Clip)
- Pengguna Free Member dapat membeli kuota instan (misal: Rp 5.000 untuk 10 klip tambahan hari itu).

### C. Discord Bot & Notifikasi Webhook
- Notifikasi otomatis ke Channel Discord IME Police ketika Officer menunjuk Clipper baru atau ketika pengajuan VIP disetujui.

---
*Dokumen ini dibuat otomatis sebagai panduan pemeliharaan dan pengembangan fitur ClipStudio pada IME Police Multiview Platform.*
