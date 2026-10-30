# GEMINI.md — Konteks Proyek untuk Agen Coding

Police Duty Multiview (SASP MotionLife RP): agregator live stream YouTube perwira polisi RP + Direktori Perwira + fitur **Officer Tactical Bio-Link Hub** (klaim profil officer, halaman `/@{slug}`, moderasi admin).

## ⚡ Aturan Kerja (WAJIB — demi kecepatan)

1. **Kerjakan SATU task per sesi.** Baca hanya:
   - file ini (`GEMINI.md`), dan
   - brief task: `docs/biolink/tasks/<ID>.md` (bio-link) **atau** `docs/ui/tasks/<ID>.md` (UI Kit `DS-xx`, Hero Slider `HS-xx`, migrasi halaman `MIG-xx`).
   - **Untuk setiap task yang menyentuh `.vue`** (HS, MIG, atau UI baru): baca juga `docs/design-system/GEMINI-UI-PLAYBOOK.md`.
2. **JANGAN membaca** `OFFICER_TACTICAL_BIO_LINKS_PRD.md`, `docs/hero-slider/PRD.md`, atau task board (`OFFICER_TACTICAL_BIO_LINKS_TASKS.md`, `docs/ui/TASKS.md`) secara utuh. Buka bagian PRD **hanya** jika brief menyebut nomor bagiannya, dan baca bagian itu saja.
3. Buka hanya file yang disebut di brief. Jangan menjelajah seluruh repo.
4. Selama bekerja, jalankan test yang relevan saja: `php artisan test --filter=<NamaTest>`. Jalankan suite penuh (`php artisan test`) **sekali** di akhir, lalu `npm run build` **hanya** jika ada perubahan `.vue`/`.js`.
5. Selesai satu task (atau sub-task bila brief memecahnya, mis. MIG-07a) → satu commit: `fix(biolink): B-xx …`, `feat(biolink): T-xx …`, `feat(ui): DS-xx …`, `feat(hero): HS-xx …`, atau `refactor(ui): MIG-xx …`. Stage file secara eksplisit (jangan `git add -A`). Jangan memasukkan perubahan task lain ke commit yang sama.
6. Di task board yang sesuai (`OFFICER_TACTICAL_BIO_LINKS_TASKS.md` atau `docs/ui/TASKS.md`), ubah **hanya baris task tersebut**: status → `🟣 Ready for Review` dan isi kolom **Dev Notes** (1–2 kalimat). **Jangan** mengisi ✅ Done, jangan mengubah Review Notes, jangan mencentang checklist yang tidak dikerjakan, jangan menghapus item checklist.
7. Jika brief tidak bisa dipenuhi sebagian, tulis alasannya di Dev Notes — jangan diam-diam diganti.
8. **Jangan commit** `vite.config.js` (berisi IP LAN lokal) dan jangan mengubah file di luar cakupan brief.

## Stack

- PHP 8.3, **Laravel 13**, Inertia.js 2 + **Vue 3** (`<script setup>`, **JavaScript**, bukan TypeScript), Vite. Tailwind 3.4 → **Tailwind 4** setelah DS-00. UI: **shadcn-vue** (setelah DS-01).
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
| Test bio-link | `tests/Feature/BioLink/*Test.php` (+ trait `Concerns/CreatesBioLinkFixtures`) |
| Komponen UI | `resources/js/Components/ui/` (shadcn-vue, hasil CLI) · `resources/js/Components/tactical/` (khas proyek) · `resources/js/lib/utils.js` (`cn`) · `components.json` |
| Gaya global & token | `resources/css/app.css` |

## Konvensi Kode (ikuti pola yang ada)

- **Respons JSON**: `response()->json(['message' => '<pesan bahasa Indonesia>', ...], <status>)`. Error validasi: `$request->validate([...])` atau `ValidationException::withMessages([...])` → 422. Aksi dilarang → 403. Profil suspended → **423**.
- **Audit**: setiap aksi sensitif memanggil `AuditLogger::log($action, $subjectType, $subjectId, $before, $after, $reason)`. Nama action format `objek.aksi` (mis. `officer.bind`, `bio_link.donation_changed`).
- **Cache halaman publik**: setelah mengubah data yang tampil di `/@{slug}`, panggil `BioProfileService::invalidatePublicCache($slug)`.
- **Status officer** diturunkan dari relasi: `$user->officer` / `$user->isOfficer()`. **Jangan** menulis `users.role = 'officer'`.
- **Model relasi Officer**: `agency()` (kolom `agency_code`, `agency_name`, `badge_logo_url`), `rankRelation()` (kolom `rank_title`, `level`), `division()` (kolom `division_name`, `division_code`). Teks lama `department`/`rank` dipakai sebagai fallback.
- Transaksi + `lockForUpdate()` untuk operasi yang bisa balapan (bind, callsign unik).
- Frontend: `axios` untuk API JSON. Notifikasi → `toast()` dari `vue-sonner`; konfirmasi aksi destruktif → `AlertDialog` / `Dialog` + alasan (setelah DS-02; **jangan** menambah `confirm()`/`prompt()` baru).
- Komentar singkat seperlunya; jangan menambah komentar penjelasan panjang.

## UI — Tactical UI Kit = shadcn-vue + aksen taktis (wajib untuk `.vue` baru/yang diubah)

Resep lengkap: **`docs/design-system/GEMINI-UI-PLAYBOOK.md`**. Rujukan token/komponen: `docs/design-system/README.md`. Ringkasnya:
- **Dark-only** (`<html class="dark">`).
- **Komponen dulu:** `@/Components/ui/<nama>` (shadcn-vue: `Button`, `Badge`, `Card*`, `Input`, `Select`, `Switch`, `Dialog`, `AlertDialog`, `Sheet`, `DropdownMenu`, `Tooltip`, `Tabs`, `Table`, `Avatar`, `Skeleton`, `Alert`, `Carousel`, …) dan `@/Components/tactical` (`PageContainer`, `PageHeader`, `SectionHeader`, `TacticalFrame`, `LiveBadge`, `DeptBadge`, `StatTile`, `OfficerAvatar`, `EmptyState`). Tambah komponen shadcn hanya lewat `npx shadcn-vue@latest add <nama>`; **jangan** mengedit massal `Components/ui/*`.
- **Token saja:** `bg-background`, `bg-card`, `bg-muted`, `text-foreground`, `text-muted-foreground`, `border-border`, `border-input`, `text-primary`, `text-destructive`, `bg-success/15`, `text-warning`, `text-info`, `bg-live`, `text-dept-lspd|bcso|sasp|sapr`. **Dilarang** palet mentah (`slate-`, `gray-`, `blue-600`, `red-500`, …), hex di kelas, `text-[Npx]` (minimum `text-xs`), `font-black`.
- **Aksen taktis** hanya lewat `<TacticalFrame>`, hanya di tempat yang disebut brief, maks. 2 per layar. Jangan menulis kelas `tactical-frame` atau `clip-path` sudut langsung (augmented-ui **tidak** dipakai).
- Ikon: `@lucide/vue` (bukan `lucide-vue-next`). Tombol ikon wajib `aria-label`. Animasi dekoratif `motion-safe:`.
- Gabung kelas kondisional dengan `cn()` dari `@/lib/utils`.
- Cek: `npm run lint:ui -- <file>` = 0 temuan untuk file yang kamu ubah (tersedia setelah DS-03).
- Migrasi halaman: **tampilan berubah, perilaku tidak**. Jangan merapikan file di luar cakupan brief.

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
