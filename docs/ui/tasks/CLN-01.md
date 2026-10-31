# CLN-01 — Hapus fitur Watchlist dan TAC Channel

**Prioritas:** 🟡 P2 · **Estimasi:** sedang · **Commit:** `chore: CLN-01 remove watchlist and tac channel`
Baca dulu: `GEMINI.md`.

## Konteks
Keduanya tidak dipakai lagi:
- **Watchlist** — dulu untuk menambahkan kanal YouTube non-officer. Sekarang semua tampilan hanya dari officer resmi.
- **TAC Channel** — dulu untuk watch party. Fitur squad sudah menggantikannya.

Tujuan: hapus total — kode, data, tabel, endpoint, UI, dan teks.

## Yang dihapus — Watchlist

### Backend
- `app/Models/UserWatchlist.php` — hapus file.
- `app/Http/Controllers/UserWatchlistController.php` — hapus file.
- `app/Models/User.php` — hapus method `watchlists()` dan import `UserWatchlist`.
- `app/Http/Controllers/UserManagementController.php` — hapus `withCount('watchlists')` + `with('watchlists')` dan tampilan `watchlists_count` di payload admin.
- `routes/web.php` — hapus blok `prefix('api/v1/user/watchlist')` (±baris 137–144) beserta `use App\Http\Controllers\UserWatchlistController`.

### Database
- Migration baru: `database/migrations/2026_10_31_000001_drop_user_watchlists_table.php` berisi `Schema::dropIfExists('user_watchlists')`. `down()` boleh kosong karena tabel tidak akan dipulihkan.
- **Jangan** hapus migration lama `2026_09_15_000001_create_user_watchlists_table.php` (sejarah migrasi tetap).

### Seeder
- `database/seeders/UserSeeder.php` — hapus pengisian watchlist bila ada.

### UI (Vue + i18n)
Hapus semua jejak `watchlist`, `isWatchlisted`, `toggleWatchlist`, `useWatchlist`, dsb. di file-file berikut:
- `resources/js/Components/MultiviewHeader.vue`
- `resources/js/Components/TacticalDockControlBar.vue`
- `resources/js/Components/TacticalStreamGrid.vue`
- `resources/js/Components/Drawers/QuickAddDrawer.vue` — jika seluruh drawer memang khusus watchlist, hapus **file** dan semua pemakaiannya.
- `resources/js/Components/AuthModal.vue` — hapus baris copy watchlist.
- `resources/js/Composables/useAnalytics.js` — hapus event watchlist.
- `resources/js/Pages/OfficerDirectory.vue` — hapus kolom/tombol watchlist.
- `resources/js/Pages/Admin/OfficerManagement.vue` — hapus kolom "Watchlist count" di tabel user bila ada.
- `resources/js/Pages/About.vue` — hapus kalimat tentang watchlist.
- `resources/js/Data/faqs.id.js` + `faqs.en.js` — hapus entri FAQ tentang watchlist.
- `resources/js/Data/releases.id.js` — **jangan** hapus entri rilis watchlist (sejarah rilis tetap). Cukup tambahkan entri rilis baru di atas: "Fitur Watchlist dihentikan — gunakan Squad untuk mengelompokkan officer favorit."
- `resources/js/locales/id.json` + `en.json` — hapus semua key `watchlist.*`.

## Yang dihapus — TAC Channel

### Backend
- `app/Models/TacChannel.php` — hapus.
- `app/Http/Controllers/TacChannelController.php` — hapus.
- `app/Http/Controllers/PoliceCommandController.php` — hapus method/pemanggilan terkait TAC (cari `TacChannel`, `tac_channel`, `tacBanner`, dsb.). Pastikan tidak ada import yatim.
- `routes/web.php` — hapus blok `prefix('tac')` (±baris 90–96) + import `use App\Http\Controllers\TacChannelController`.

### Database
- Migration baru: `database/migrations/2026_10_31_000002_drop_tac_channels_table.php` → `Schema::dropIfExists('tac_channels')`.

### UI (Vue + i18n)
- `resources/js/Composables/useTacChannelManager.js` — hapus.
- `resources/js/Components/TacChannelNotificationToast.vue` — hapus.
- `resources/js/Components/FocusModePlayer.vue` — hapus logika TAC.
- `resources/js/Components/OfficerDetailModal.vue` — hapus tombol/aksi TAC.
- `resources/js/Components/TacticalStreamGrid.vue` + `TacticalStreamSlimles.vue` — hapus overlay/toggle TAC.
- `resources/js/Components/CatalogCard.vue` — hapus badge/aksi TAC bila ada.
- `resources/js/Components/MultiviewHeader.vue` — hapus menu TAC.
- `resources/js/Pages/Multiview.vue` — hapus state TAC + import `useTacChannelManager`.
- `resources/js/Pages/Dashboard.vue` — hapus baris/kartu TAC.
- `resources/js/Pages/OfficerDirectory.vue` — hapus aksi TAC.
- `resources/js/Composables/useAnalytics.js` — hapus event TAC.
- `resources/js/Data/faqs.id.js` + `faqs.en.js` — hapus entri FAQ TAC.
- `resources/js/Data/releases.id.js` — tambah entri rilis "Fitur TAC Channel dihentikan — Squad menggantikan watch party."
- `resources/js/locales/id.json` + `en.json` — hapus semua key `tac.*` dan `tac_channel.*`.

## Jangan
- Jangan menghapus fitur **Squad** — itu yang menggantikan TAC.
- Jangan menghapus fitur **TacClips** / `/action-clips` — itu beda ("Tactical Clips", bukan TAC Channel). Di kode namanya `VideoClip` / `TacClip`, bukan `TacChannel`.
- Jangan menghapus migrasi lama (biarkan sejarah). Hanya buat migrasi `drop` baru.
- Jangan `git add -A` / `git add .` — commit hanya file terkait task.

## Cek
- `php artisan migrate` di DB lokal → tabel `user_watchlists` dan `tac_channels` hilang.
- `grep -rn "watchlist\|TacChannel\|tac_channel" app resources/js routes database tests` → **kosong** (kecuali di `releases.id.js` sebagai sejarah).
- `npm run lint:ui -- <file .vue yang diubah>` → 0 error.
- `npm run build` lulus.
- Seluruh `php artisan test` hijau.

## Selesai jika
- [ ] Tabel `user_watchlists` dan `tac_channels` di-drop lewat migrasi baru.
- [ ] Semua rute, controller, model, komponen, composable, i18n, FAQ terkait dua fitur ini hilang.
- [ ] Halaman Multiview, Dashboard, Direktori, dan Admin tetap jalan tanpa error di console.
- [ ] Baris CLN-01 di `docs/ui/TASKS.md` diisi 🟣 + Dev Notes dengan daftar file dihapus/dibersihkan.
