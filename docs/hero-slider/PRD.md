# PRD: Dashboard Hero Slider

**Status:** Approved untuk dikerjakan · **Tanggal:** 28 Oktober 2026  
**Keputusan:** dark-only (mengikuti [Tactical UI Kit](../design-system/README.md)), slide **dikelola admin saja**.

## 1. Tujuan
Hero di dashboard (`resources/js/Pages/Dashboard.vue`, section "HERO SPOTLIGHT") saat ini statis. Admin membutuhkan ruang promosi berganti otomatis untuk:
- memperkenalkan fitur baru (mis. Bio-Link Officer, Clipper),
- mengumumkan event komunitas / operasi besar,
- menyorot officer yang sedang LIVE,
- mengajak aksi (mis. "Klaim profil perwiramu").

**Ukuran keberhasilan:** admin bisa membuat/menjadwalkan slide tanpa deploy; hero tetap cepat (LCP mobile < 2,5 s); klik CTA terukur.

## 2. Di Luar Cakupan
- Officer/member mengusulkan slide (admin saja).
- Light mode.
- Video sebagai latar slide (v2).
- A/B testing.

## 3. Jenis Slide

| `slide_type` | Isi | Sumber data |
| :--- | :--- | :--- |
| `feature` | Promosi fitur baru | Diisi admin |
| `event` | Event/pengumuman komunitas | Diisi admin |
| `cta` | Ajakan aksi (daftar, klaim profil, dll.) | Diisi admin |
| `live_spotlight` | Officer LIVE unggulan | **Otomatis** dari stream aktif (viewer terbanyak). Admin hanya mengatur posisi & label; slide disembunyikan bila tidak ada yang live. |

Maksimal **5 slide** tampil sekaligus (setelah filter jadwal & audiens). Slide `live_spotlight` maksimal satu.

## 4. Data Slide (tabel `hero_slides`)

| Kolom | Tipe | Keterangan |
| :--- | :--- | :--- |
| `id` | bigint | |
| `slide_type` | enum(`feature`,`event`,`cta`,`live_spotlight`) | default `feature` |
| `title` | string(80) | wajib kecuali `live_spotlight` (otomatis nama officer) |
| `subtitle` | string(200) nullable | |
| `badge_label` | string(24) nullable | mis. "FITUR BARU", "EVENT" |
| `accent` | enum(`primary`,`info`,`success`,`warning`,`danger`,`dept-lspd`,`dept-bcso`,`dept-sasp`,`dept-sapr`) | warna aksen dari token UI Kit |
| `image_desktop_path` | string nullable | wajib kecuali `live_spotlight` (pakai thumbnail stream) |
| `image_mobile_path` | string nullable | fallback ke desktop |
| `image_alt` | string(150) nullable | wajib bila ada gambar |
| `cta_primary_label` / `cta_primary_url` | string(30) / string(500) nullable | |
| `cta_secondary_label` / `cta_secondary_url` | string(30) / string(500) nullable | |
| `audience` | enum(`all`,`guest`,`member`,`officer`) | default `all`. `member` = login non-officer; `officer` = user terikat officer |
| `starts_at` / `ends_at` | timestamp nullable | jadwal tayang |
| `sort_order` | int | urutan |
| `is_active` | boolean | default true |
| `created_by` / `updated_by` | FK users nullable | |
| `timestamps` | | |

Tabel agregat klik: `hero_slide_stats` (`hero_slide_id`, `stat_date`, `impressions`, `clicks`, unique `hero_slide_id+stat_date`).

## 5. Aturan Konten & Validasi
- **Gambar:** jpg/png/webp, maks. 2 MB. Desktop min. 1600×600 (rasio ±8:3), mobile min. 1080×1080. Server me-*re-encode* ke **WebP** via GD (hapus metadata), simpan di disk `public` folder `hero/` dengan nama acak.
- **URL CTA:** path relatif internal (`/multiview`, `/@slug`) **atau** `https://`. `javascript:`/`data:` ditolak.
- Judul maks. 80 karakter, subjudul 200 — agar tetap terbaca di HP.
- `ends_at` > `starts_at`.

## 6. Perilaku di Dashboard
- Data slide dikirim sebagai **prop Inertia** dari controller dashboard (bukan request terpisah), sudah difilter: aktif, dalam jadwal, sesuai audiens user, maks. 5, urut `sort_order`. Di-cache 60 detik per audiens; cache dihapus saat admin mengubah slide.
- Jika tidak ada slide → tampil hero statis yang sekarang (fallback).
- **Autoplay 7 detik**, berhenti saat hover, saat fokus keyboard di dalam hero, dan saat tab browser tidak aktif.
- Navigasi: tombol prev/next, titik indikator + progress bar, swipe di HP, tombol panah keyboard.
- **Performa:** gambar slide pertama `loading="eager" fetchpriority="high"` + `<link rel="preload">`; slide lain `loading="lazy"`. Tinggi hero tetap (tidak ada layout shift). Carousel memakai komponen `Carousel` dari Tactical UI Kit (shadcn-vue, berbasis Embla) + plugin autoplay — tidak ada library carousel lain.
- **Aksesibilitas:** `role="region"` + `aria-roledescription="carousel"`, tiap slide `aria-roledescription="slide"` + `aria-label="2 dari 5"`, tombol pause/play terlihat, `prefers-reduced-motion` → tanpa autoplay & tanpa animasi geser.
- **Telemetri:** impression dihitung saat slide benar-benar tampil ≥1 detik (sekali per sesi per slide), klik CTA lewat `navigator.sendBeacon` ke endpoint publik ber-throttle. Disimpan agregat harian, tanpa data pribadi.

## 7. Panel Admin
Tab baru **"Hero Slides"** di `/admin/officers` (pola sama seperti tab Announcements):
- Daftar slide: thumbnail, judul, tipe, audiens, jadwal (badge *Terjadwal / Tayang / Berakhir*), status, impressions/klik 7 hari.
- Form tambah/ubah: semua field §4, unggah gambar desktop & mobile dengan pratinjau.
- **Pratinjau langsung** desktop & mobile memakai komponen hero yang sama.
- Drag & drop urutan, toggle aktif, duplikat, hapus (konfirmasi).
- Semua aksi tercatat di `audit_logs` (`hero_slide.created/updated/deleted/reordered`).

## 8. API

| Method | Path | Akses |
| :--- | :--- | :--- |
| GET | `/api/v1/admin/hero-slides` | admin |
| POST | `/api/v1/admin/hero-slides` (multipart) | admin |
| POST | `/api/v1/admin/hero-slides/{slide}` (multipart, `_method=PUT`) | admin |
| DELETE | `/api/v1/admin/hero-slides/{slide}` | admin |
| PATCH | `/api/v1/admin/hero-slides/{slide}/toggle` | admin |
| POST | `/api/v1/admin/hero-slides/reorder` | admin |
| POST | `/api/v1/hero-slides/{slide}/event` `{type: impression|click}` | publik, `throttle:60,1` |

## 9. Kriteria Penerimaan
- [x] Admin dapat membuat, menjadwalkan, mengurutkan, menonaktifkan slide tanpa deploy.
- [x] Slide di luar jadwal / audiens tidak tampil; maks. 5 slide.
- [x] Slide `live_spotlight` otomatis berisi officer live dengan viewer terbanyak dan hilang bila tidak ada yang live.
- [x] Tanpa slide aktif → hero statis lama tetap tampil.
- [x] Autoplay berhenti saat hover/fokus/tab tidak aktif; reduced motion dihormati; navigasi keyboard & swipe berfungsi.
- [x] Lighthouse mobile dashboard: LCP < 2,5 s, CLS < 0,1.
- [x] Seluruh UI hero & panel admin memakai Tactical UI Kit (lolos `npm run lint:ui`).
- [x] Impression & klik tercatat per hari per slide.
