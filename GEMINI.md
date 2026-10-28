# GEMINI.md — Konteks Proyek untuk Agen Coding

Police Duty Multiview (SASP MotionLife RP): agregator live stream YouTube perwira polisi RP + Direktori Perwira + fitur **Officer Tactical Bio-Link Hub** (klaim profil officer, halaman `/@{slug}`, moderasi admin).

## ⚡ Aturan Kerja (WAJIB — demi kecepatan)

1. **Kerjakan SATU task per sesi.** Baca hanya:
   - file ini (`GEMINI.md`), dan
   - brief task: `docs/biolink/tasks/<ID>.md`.
2. **JANGAN membaca** `OFFICER_TACTICAL_BIO_LINKS_PRD.md` atau `OFFICER_TACTICAL_BIO_LINKS_TASKS.md` secara utuh. Buka bagian PRD **hanya** jika brief menyebut nomor bagiannya, dan baca bagian itu saja.
3. Buka hanya file yang disebut di brief. Jangan menjelajah seluruh repo.
4. Selama bekerja, jalankan test yang relevan saja: `php artisan test --filter=<NamaTest>`. Jalankan suite penuh (`php artisan test`) **sekali** di akhir, lalu `npm run build` **hanya** jika ada perubahan `.vue`/`.js`.
5. Selesai satu task → satu commit: `fix(biolink): B-xx <ringkasan>` atau `feat(biolink): T-xx <ringkasan>`. Stage file secara eksplisit (jangan `git add -A`).
6. Di `OFFICER_TACTICAL_BIO_LINKS_TASKS.md`, ubah **hanya baris task tersebut**: status → `🟣 Ready for Review` dan isi kolom **Dev Notes** (1–2 kalimat). **Jangan** mengisi ✅ Done, jangan mengubah Review Notes, jangan mencentang checklist yang tidak dikerjakan, jangan menghapus item checklist.
7. Jika brief tidak bisa dipenuhi sebagian, tulis alasannya di Dev Notes — jangan diam-diam diganti.
8. **Jangan commit** `vite.config.js` (berisi IP LAN lokal) dan jangan mengubah file di luar cakupan brief.

## Stack

- PHP 8.3, **Laravel 13**, Inertia.js 2 + **Vue 3** (`<script setup>`), Tailwind 3, Vite.
- DB produksi MySQL; **test memakai SQLite in-memory**, `CACHE_STORE=array`, `QUEUE_CONNECTION=sync` (lihat `phpunit.xml`).
- Produksi: `CACHE_STORE=database`, `QUEUE_CONNECTION=database`, `SESSION_DRIVER=database`.
- Lingkungan dev: Windows + Laragon. Perintah shell memakai sintaks POSIX/Git Bash.

## Peta File Penting

| Area | Lokasi |
| :--- | :--- |
| Rute (web + JSON `api/v1`) | `routes/web.php` — blok bio-link ada di **akhir file** |
| Middleware alias | `bootstrap/app.php` (`admin`, `officer`, `not_banned`, `can_trim`) |
| Bio-link officer | `app/Http/Controllers/OfficerBioLinkController.php` |
| Halaman publik & `/go` | `app/Http/Controllers/PublicBioLinkController.php` |
| Klaim officer | `app/Http/Controllers/OfficerClaimController.php` |
| Bind/unbind & slug | `app/Http/Controllers/OfficerBindingController.php`, `app/Services/OfficerBindingService.php` |
| Moderasi admin | `app/Http/Controllers/AdminBioModerationController.php` |
| Data officer (admin) | `app/Http/Controllers/OfficerManagementController.php` |
| Service | `app/Services/` (`AuditLogger`, `BioProfileService`, `NameSkeleton`, `YouTubeChannelVerifier`) |
| Konfigurasi bio-link | `config/biolinks.php` |
| Halaman Vue | `resources/js/Pages/Officer/BioLinksDashboard.vue`, `Pages/Public/BioLink.vue`, `Pages/Admin/OfficerManagement.vue` |
| Komponen admin | `resources/js/Components/Admin/` (`OfficerClaimsTab`, `OfficerBindingModal`, `BioModerationTab`) |
| Test bio-link | `tests/Feature/OfficerTacticalBioLinkTest.php` (akan dipecah ke `tests/Feature/BioLink/` di T-10) |

## Konvensi Kode (ikuti pola yang ada)

- **Respons JSON**: `response()->json(['message' => '<pesan bahasa Indonesia>', ...], <status>)`. Error validasi: `$request->validate([...])` atau `ValidationException::withMessages([...])` → 422. Aksi dilarang → 403. Profil suspended → **423**.
- **Audit**: setiap aksi sensitif memanggil `AuditLogger::log($action, $subjectType, $subjectId, $before, $after, $reason)`. Nama action format `objek.aksi` (mis. `officer.bind`, `bio_link.donation_changed`).
- **Cache halaman publik**: setelah mengubah data yang tampil di `/@{slug}`, panggil `BioProfileService::invalidatePublicCache($slug)`.
- **Status officer** diturunkan dari relasi: `$user->officer` / `$user->isOfficer()`. **Jangan** menulis `users.role = 'officer'`.
- **Model relasi Officer**: `agency()` (kolom `agency_code`, `agency_name`, `badge_logo_url`), `rankRelation()` (kolom `rank_title`, `level`), `division()` (kolom `division_name`, `division_code`). Teks lama `department`/`rank` dipakai sebagai fallback.
- Transaksi + `lockForUpdate()` untuk operasi yang bisa balapan (bind, callsign unik).
- Frontend: `axios` untuk API JSON, emit `toast` untuk notifikasi, konfirmasi aksi destruktif (minimal `prompt()`/`confirm()` dengan alasan).
- Komentar singkat seperlunya; jangan menambah komentar penjelasan panjang.

## Test

- Test baru: `tests/Feature/BioLink/<Area>Test.php`, `use RefreshDatabase`, buat data langsung via `Officer::create([...])` / `User::factory()` (lihat contoh di file test yang ada).
- HTTP ke YouTube **wajib** di-fake: `Http::fake([...])`.
- Jika perilaku bergantung pada cache store `database`, set di test: `config(['cache.default' => 'database'])`.
- Perintah: `php artisan test --filter=NamaTest`.

## Definisi Selesai (per task)

- Semua checklist di brief terpenuhi.
- Test baru untuk jalur sukses **dan** jalur ditolak.
- `php artisan test` hijau; `npm run build` sukses bila ada perubahan frontend.
- Satu commit, baris task di task board → `🟣 Ready for Review` + Dev Notes.
