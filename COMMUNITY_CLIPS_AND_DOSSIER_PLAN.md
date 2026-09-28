# Rencana Pengembangan: Community Tactical Dossier & Action Clips Hub

Dokumen ini berisi rencana arsitektur teknis, perancangan antarmuka (UI/UX), skema database, dan alur implementasi untuk fitur **Kesan Komunitas Perwira (*Officer Impressions & Lore*)**, **Kumpulan Aksi Taktis (*Action Clips & Shorts Showcase*)**, serta **Pembaruan Halaman Mode Fokus ala YouTube Taktis**.

---

## 📌 1. Ringkasan Eksekutif & Tujuan Fitur

Fitur ini bertujuan merubah antarmuka **Mode Fokus Multiview** dari sekadar pemutar siaran tunggal menjadi **Pusat Komunitas & Showcase Aksi Roleplay (RP)** yang kaya informasi dan interaktif.

### 💡 Tiga Pilar Utama:
1. **Pilar A: Kesan & Lore Komunitas (*Officer Impressions & Dossier*)**
   * Penonton dapat membaca dan menuliskan ulasan, pesan, maupun rekap gaya roleplay perwira (misalnya: gaya mengemudi saat kejaran, lelucon khas, integritas RP).
   * Menampilkan biodata taktis lengkap (Callsign, Pangkat, Departemen, Sertifikasi, Zona Patroli).

2. **Pilar B: Kumpulan Aksi & Klip Taktis (*Community Action Shorts & Highlights*)**
   * Penonton dapat menandai dan membagikan momen-momen aksi penting dari siaran perwira.
   * Klip dikategorikan secara otomatis:
     * 🏎️ **Kejaran Taktis (10-80 Pursuit)**
     * 💥 **Baku Tembak (10-99 Shootout)**
     * 😂 **Momen Lucu / Funny RP**
     * 🚓 **Penghentian & Penangkapan Taktis**
     * 🚁 **Operasi Khusus (Air/Water/Tactical)**

3. **Pilar C: Desain Mode Fokus ala YouTube Watch Page**
   * Menyusun ulang bagian bawah pemutar video utama dengan tata letak ala YouTube (*Title Header, Channel Bar, YouTube-Style Action Buttons, Expandable Description Box, dan Tab Komunitas*).

---

## 📊 2. Analisis Kebutuhan Penyimpanan & Bandwidth (Storage Architecture)

Untuk menjaga performa server VPS tetap ringan dan **bebas biaya storage tinggi**, sistem menggunakan **Pendekatan Hibrida (0 MB Server Disk Default)**:

| Aspek | Opsi A: YouTube Embed Bookmark (Default) | Opsi B: Unduh MP4 Fisik di VPS |
| :--- | :--- | :--- |
| **Metode Storage** | Menyimpan ID YouTube & Timestamp (`start_time`, `end_time`) di MySQL | Mengunduh file `.mp4` via `yt-dlp` & `ffmpeg` ke disk VPS |
| **Ukuran Data per Klip** | **~0,5 KB** (hanya 1 baris teks di MySQL) | **~15 MB – 35 MB** per file video 1080p |
| **Kapasitas 1.000 Klip** | **~0,5 MB** | **~15 GB – 25 GB** |
| **Kapasitas 10.000 Klip** | **~5 MB** | **~150 GB – 250 GB** |
| **Beban Bandwidth VPS** | **0 GB** (100% ditanggung CDN YouTube) | Menguras kuota egress VPS saat diputar |
| **Rekomendasi Penggunaan** | **Digunakan untuk Galeri Klip & Shorts Komunitas Utama** | **Digunakan hanya saat user klik "Download File MP4"** |

> [!TIP]
> Dengan menggunakan **Opsi A (YouTube Embed Bookmark)** sebagai sistem utama galeri klip, platform dapat menampung puluhan ribu klip komunitas dengan total penggunaan disk VPS kurang dari **10 MB**! Pembersihan otomatis (*auto-cleanup cron job*) akan diterapkan untuk menghapus file fisik MP4 sementara yang berumur > 7 hari.

---

## 🗄️ 3. Perancangan Skema Database (Database Schema)

### A. Tabel `officer_impressions` (Kesan & Catatan Komunitas)
```sql
CREATE TABLE officer_impressions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    officer_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NULL, -- NULL jika ditulis anonim
    author_name VARCHAR(100) NOT NULL,
    impression_type ENUM('COMMUNITY_NOTE', 'REPUTATION', 'FUNNY_MOMENT', 'TACTICAL_RATING') DEFAULT 'COMMUNITY_NOTE',
    content TEXT NOT NULL,
    likes_count INT UNSIGNED DEFAULT 0,
    is_approved BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (officer_id) REFERENCES officers(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);
```

### B. Tabel `community_clips` (Kumpulan Aksi & Short Bookmark)
```sql
CREATE TABLE community_clips (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    officer_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    video_id VARCHAR(50) NOT NULL, -- YouTube Video ID
    title VARCHAR(255) NOT NULL,
    action_category ENUM('PURSUIT_1080', 'SHOOTOUT_1099', 'FUNNY', 'ARREST', 'TACTICAL_OPS') DEFAULT 'PURSUIT_1080',
    start_seconds INT UNSIGNED NOT NULL, -- Contoh: 615 (detik 10:15)
    end_seconds INT UNSIGNED NOT NULL,   -- Contoh: 645 (detik 10:45)
    duration_seconds INT UNSIGNED NOT NULL,
    views_count INT UNSIGNED DEFAULT 0,
    likes_count INT UNSIGNED DEFAULT 0,
    thumbnail_url VARCHAR(550) NULL,
    is_featured BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (officer_id) REFERENCES officers(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);
```

---

## 🎨 4. Perancangan Antarmuka (UI/UX Mockup Focus Mode)

```
+-------------------------------------------------------------------------+
|                                                                         |
|                       PEMUTAR VIDEO UTAMA (1080p HD)                    |
|                        `yt-bodycam-PRIMARY_STREAM`                      |
|                                                                         |
+-------------------------------------------------------------------------+

1. [TITLE & BADGES HEADER]
   🔴 LIVE 10-8  •  [IME RP] PATROL HIGHWAY PURSUIT #LSPD #PATROL
   👁️ 240 Penonton  •  Durasi Patroli: 01:45:20

2. [OFFICER / CHANNEL ACTION BAR (Ala YouTube Watch Page)]
   [Avatar]  Officer John Doe (SLT-102)          [🔊 Audio ON]  [📻 Radio TAC]
             Senior Lead Trooper • SASP #402      [📌 Prioritas]  [✂️ Tandai Aksi]
                                                  [↗️ Buka di YouTube]

3. [TAB SECTOR BELOW VIDEO PLAYER]
   =======================================================================
   [ 📋 Kesan & Dossier ]   [ 🎬 Kumpulan Aksi (Clips) ]   [ 📺 Deskripsi ]
   =======================================================================

   TAB 1: 📋 KESAN & LORE KOMUNITAS
   -----------------------------------------------------------------------
   • Dossier Taktis: Callsign, Departemen, Pangkat, Sertifikasi, Patrol Zone.
   • Ulasan & Kesan Pengguna: Daftar catatan kesan dari penonton.
   • Form "+ Tambah Kesan Tentang Perwira Ini".

   TAB 2: 🎬 KUMPULAN AKSI & SHORTS SHOWCASE
   -----------------------------------------------------------------------
   • Filter Tag: [Semua] [🏎️ Kejaran 10-80] [💥 Baku Tembak 10-99] [😂 Lucu]
   • Grid Cards Klip Momen: Thumbnail + Judul Aksi + Pembuat + Tombol Putar.
   • Tombol Modal "+ Tandai Momen Aksi Baru".

   TAB 3: 📺 DESKRIPSI STREAMER & YOUTUBE DETAILS
   -----------------------------------------------------------------------
   • Deskripsi resmi siaran dari YouTube + Link aktif + Sosial media.
```

---

## 🛠️ 5. Komponen Frontend & Backend yang Akan Dibangun / Diperbarui

### Backend Laravel (`app/`):
* `database/migrations/2026_09_28_000001_create_officer_impressions_table.php`
* `database/migrations/2026_09_28_000002_create_community_clips_table.php`
* `app/Models/OfficerImpression.php`
* `app/Models/CommunityClip.php`
* `app/Http/Controllers/OfficerImpressionController.php` (Store, Fetch, Like)
* `app/Http/Controllers/CommunityClipController.php` (Store, Fetch, Like, Filter by category/officer)

### Frontend Vue.js (`resources/js/`):
* `resources/js/Components/OfficerDossierTab.vue` (Komponen Kesan & Lore Perwira)
* `resources/js/Components/CommunityClipsTab.vue` (Galeri Klip & Showcase Aksi Ter-Kategori)
* `resources/js/Components/CreateActionClipModal.vue` (Modal Cepat Penandaan Momen Aksi)
* `resources/js/Components/TacticalStreamGrid.vue` (Integrasi Pembaruan Mode Fokus ala YouTube)

---

## 📋 6. Checklist Langkah Implementasi

- [ ] **Langkah 1**: Buat file migrasi database `officer_impressions` & `community_clips`.
- [ ] **Langkah 2**: Jalankan `php artisan migrate` untuk memperbarui skema database.
- [ ] **Langkah 3**: Buat Eloquent Model (`OfficerImpression`, `CommunityClip`) & Controller terkait.
- [ ] **Langkah 4**: Daftarkan API routes pada `routes/api.php` (`/api/officer-impressions`, `/api/community-clips`).
- [ ] **Langkah 5**: Buat sub-komponen Vue `OfficerDossierTab.vue` & `CommunityClipsTab.vue`.
- [ ] **Langkah 6**: Perbarui layout Mode Fokus di `TacticalStreamGrid.vue` dengan YouTube Watch Page Style.
- [ ] **Langkah 7**: Uji coba pembuatan klip bookmark, penambahan ulasan kesan perwira, dan pemutaran klip.
- [ ] **Langkah 8**: Jalankan `npm run build` dan verifikasi seluruh fungsi.

---

*Dokumen ini dibuat secara otomatis pada 28 September 2026.*
