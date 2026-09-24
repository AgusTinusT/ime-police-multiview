# Document Arsitektur & Refactoring PoliceDashboard.vue

> **Versi Arsitektur:** 2.0 (Post-Refactoring & Modularization)  
> **Tanggal Pembaruan:** 24 September 2026  
> **Status:** Active Standard

---

## 📋 1. Latar Belakang & Tujuan Refactoring

Halaman `PoliceDashboard.vue` sebelumnya merupakan *monolithic file* dengan panjang lebih dari **4.400 baris kode**, menggabungkan seluruh UI template, pengelolaan state YouTube Player, Tactical Radio Channels, Roster Unit Offline, Slide-over Drawers, hingga Cinema Hub Swimlanes.

Refactoring ini dilakukan untuk:
1. **Meningkatkan Clean Code & Maintainability**: Memecah halaman raksasa menjadi komponen-komponen terisolasi yang modular.
2. **Pencegahan Duplikasi File**: Mencegah terciptanya file-file komponen baru yang memiliki fungsi sama tetapi beda nama (seperti kasus `StreamGridCard.vue` vs `TacticalStreamGrid.vue`).
3. **Enkapsulasi Logika Reusable**: Memindahkan seluruh logika YouTube Player API & Resilient Audio Controller ke dalam Composable Vue 3 (`useYouTubePlayer.js`).
4. **Peningkatan Performa & Kemudahan Debugging**: Memudahkan isolasi bug per komponen tanpa mengganggu stabilitas halaman utama.

---

## 🏗️ 2. Struktur Peta Komponen (Component Hierarchy)

Halaman [`PoliceDashboard.vue`](file:///c:/Development/laragon/www/ime-police-multiview/resources/js/Pages/PoliceDashboard.vue) sekarang bertindak sebagai **Orchestrator Page Container** yang ringan. Pengeluaran UI dibagi ke dalam sub-komponen berikut:

```
PoliceDashboard.vue (Orchestrator Container)
├── TacticalDashboardHeader.vue      --> Top Bar, Branding Logo, Clock, User Account Menu, Quick Action
├── TacticalFilterToolbar.vue       --> Department Tabs (ALL, PERSONAL, LSPD, BCSO, SASP, SAPR, TAC), Layout Switcher, Data Saver Toggle
├── TacticalStreamGrid.vue          --> Cinema Hub Swimlanes, Grid Layout (Auto, 1x2, 2x2, 3x3, 4x4), Focus Mode Lead Stream
├── TacticalRosterTab.vue           --> Tab 10-7 Offline Officer Roster, Search, Roster Card
├── TacticalDrawers.vue             --> Slide-Over Drawers Container (Watchlist, TAC Radio Manager, Quick Add, Officer Form)
├── TacticalChatDrawer.vue          --> Dedicated YouTube Live Chat Drawer per Video Stream
├── TacChannelToolbar.vue           --> Floating/Embedded Tactical Channel Indicator
├── AnnouncementBanner.vue          --> Promo Banner & Broadcast Notice Carousel
└── TacticalFooter.vue              --> Footer Branding, System Version, Quick Links
```

### Detail Tanggung Jawab Komponen:

| Nama Komponen | Lokasi File | Deskripsi & Tanggung Jawab |
| :--- | :--- | :--- |
| **`TacticalDashboardHeader.vue`** | `resources/js/Components/` | Header atas: Menampilkan logo SASP/IME, status live indikator, jam taktis, search bar, tombol Quick Add Live, tombol Clipper, dan User Account Menu. |
| **`TacticalFilterToolbar.vue`** | `resources/js/Components/` | Toolbar navigasi: Tab filter departemen (LSPD, BCSO, SASP, SAPR, Personal, TAC 1-10), tombol ganti layout (Grid 2x2, Focus, dsb.), dan toggle Data Saver. |
| **`TacticalStreamGrid.vue`** | `resources/js/Components/` | Area pemutar stream: Mengatur tampilan Cinema Hub Swimlanes (Hero Spotlight, Trending, Department Rows), tampilan Multi-Grid, dan Focus Mode Lead Video. |
| **`TacticalRosterTab.vue`** | `resources/js/Components/` | Tampilan Roster Unit 10-7: Memuat kartu unit offline, filter pencarian roster, serta aksi kirim pesan / pendaftaran unit. |
| **`TacticalDrawers.vue`** | `resources/js/Components/` | Pengelola Slide-Over Drawers: Menyediakan modal Quick Add Live Stream, modal form officer/streamer, manager Tactical Radio Channels, dan Watchlist Drawer. |
| **`TacticalChatDrawer.vue`** | `resources/js/Components/` | Side drawer khusus yang memuat iframe live chat YouTube dari stream yang dipilih pengguna. |

---

## ⚙️ 3. Peta Composables (Business Logic & State Isolation)

Seluruh logika bisnis pemutar video dan kontrol audio diekstrak dari `PoliceDashboard.vue` ke dalam composable independen:

### `useYouTubePlayer.js`
* **Lokasi File**: [`resources/js/Composables/useYouTubePlayer.js`](file:///c:/Development/laragon/www/ime-police-multiview/resources/js/Composables/useYouTubePlayer.js)
* **Tanggung Jawab**:
  1. **YouTube IFrame API Loader**: Mengurus *handshake* dan registrasi otomatis script `https://www.youtube.com/iframe_api`.
  2. **Resilient Audio Engine (`controlPlayerAudio`)**: Mengombinasikan pesan instan HTML5 `postMessage` ke iframe dengan API `YT.Player` untuk memastikan un-mute/mute berjalan seketika tanpa *latency*.
  3. **Strict Single-Audio Policy (`toggleAudio`, `muteAll`)**: Memastikan hanya ada **1 stream audio aktif** pada satu waktu di seluruh tampilan dashboard.
  4. **Data Saver & Quality Controller (`applyQualityToPlayer`)**: Otomatis menurunkan bitrate/kualitas video (144p/360p) saat Data Saver aktif dan menaikkan ke HD untuk Lead Video di Focus Mode.
  5. **Utility Helpers**: Menyediakan fungsi popup `openSubscribePopup`, pemutar paksa `ensureVideoPlaying`, dan kontrol `toggleBrowserFullscreen`.

---

## 🗑️ 4. Komponen Deprecated & Hapus File Duplikat

Untuk menjaga kebersihan repository dan mencegah kebingungan pengembang/AI di masa mendatang, file berikut **telah dihapuskan**:

* ❌ **`resources/js/Components/StreamGridCard.vue`**  
  *Status:* **DELETED / REMOVED**  
  *Alasan:* Merupakan komponen versi lama yang sudah tidak dipakai. Seluruh UI grid card telah disatukan dan dioptimalkan di dalam [`TacticalStreamGrid.vue`](file:///c:/Development/laragon/www/ime-police-multiview/resources/js/Components/TacticalStreamGrid.vue).

---

## 🚫 5. Aturan Mencegah Duplikasi File (Anti-Duplication Rules)

Setiap pengembang atau AI Assistant yang mengerjakan proyek ini **WAJIB** mematuhi aturan berikut:

1. **Periksa Registri Komponen Terlebih Dahulu**  
   Sebelum membuat file `.vue` baru di `resources/js/Components/`, periksa tabel registri di dokumen ini. **DILARANG** membuat file baru dengan nama mirip (misalnya `StreamGrid.vue`, `DashboardHeader.vue`, `RosterCard.vue`) jika komponen tersebut sudah diwakili oleh komponen taktis (`Tactical*.vue`).

2. **Gunakan Konvensi Penamaan Berawalan `Tactical`**  
   Seluruh komponen utama UI dashboard taktis menggunakan awalan `Tactical` (misal: `TacticalDashboardHeader.vue`, `TacticalStreamGrid.vue`, `TacticalRosterTab.vue`).

3. **Logika Reusable Harus di Directory `Composables/`**  
   Jika ada logika JavaScript yang mencakup manipulasi DOM, WebSocket, YouTube API, atau LocalStorage yang digunakan di lebih dari satu tempat, tempatkan di `resources/js/Composables/use<FeatureName>.js`. Jangan menulis ulang fungsi yang sama di dalam file `.vue`.

4. **Hapus File Lama Setelah Refactoring Selesai**  
   Jika sebuah komponen lama sudah sepenuhnya digantikan oleh komponen baru, **segera hapus file lama tersebut** dan pastikan proyek berhasil di-build (`npm run build`) tanpa error.

---

## 🔍 6. Cara Memverifikasi Hasil Refactoring

Gunakan perintah build berikut untuk memastikan seluruh import file dan arsitektur berjalan tanpa kendala:

```bash
npm run build
```

Hasil build harus menunjukkan **0 error** dan menghasilkan chunk tersusun rapi di `public/build/assets/`.
