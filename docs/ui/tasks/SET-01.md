# SET-01 — Satu halaman Pengaturan (akun + profil perwira)

**Prioritas:** 🟠 P1 · **Estimasi:** sedang · **Commit:** `feat(ui): SET-01 single settings page`
Baca dulu: `GEMINI.md`, `docs/design-system/GEMINI-UI-PLAYBOOK.md`.

## Masalah
Pengguna yang ingin "mengubah sesuatu" mencari **Pengaturan**, tetapi sekarang ada dua halaman terpisah dengan jalan masuk berbeda:
- `/profile` (`Pages/Profile/Edit.vue`, `ProfileController@edit`) — nama, email, password, hapus akun.
- `/dashboard/officer-profile` (`Pages/Officer/OfficerProfile.vue`, `OfficerSelfProfileController@dashboard`) — profil dinas perwira, tab Pengajuan dan Riwayat.

## Hasil yang diinginkan
Satu halaman **`/settings`** (judul "Pengaturan") dengan bagian:

| Bagian (`?tab=`) | Isi | Tampil untuk |
| :--- | :--- | :--- |
| `account` (default) | Isi `Profile/Edit.vue` apa adanya: informasi profil, ubah password, hapus akun | Semua user login |
| `officer` | Isi `OfficerProfile.vue` apa adanya: profil dinas, pengajuan, riwayat | `is_officer` (dan admin) |
| `biolink` | Kartu singkat: tautan publik `/@slug` + tombol **Kelola bio-link** ke `/dashboard/bio-links` (editor bio-link tetap halaman sendiri karena besar) | `is_officer` |

Tata letak: desktop = navigasi vertikal di kiri (3 item) + konten kanan; HP = baris tab horizontal di atas. Bagian aktif tersimpan di query `?tab=` agar bisa dibagikan dan tombol Kembali bekerja (`router.get(..., { preserveState: true, replace: true })` atau `history.replaceState`).

## Yang harus dilakukan
1. **Pecah konten jadi komponen** tanpa mengubah perilakunya:
   - `Pages/Profile/Edit.vue` → isi formnya ke `Components/Settings/AccountSettings.vue` (memakai partial yang sudah ada di `Pages/Profile/Partials/`).
   - `Pages/Officer/OfficerProfile.vue` → isi (header kartu profil + 3 tab) ke `Components/Settings/OfficerSettings.vue`. Hapus header halaman besar "PROFIL DINAS PERWIRA" karena judul halaman kini "Pengaturan".
2. **Controller:** `SettingsController@show` (route `GET /settings`, name `settings`, middleware `auth`) merender `Pages/Settings/Index.vue` dengan props gabungan:
   - props `ProfileController@edit` (`mustVerifyEmail`, `status`);
   - bila user officer: props yang sekarang dikirim `OfficerSelfProfileController@dashboard`. Pindahkan pembentukan props itu ke method yang bisa dipanggil dua tempat (mis. `OfficerSelfProfileController::pageProps(Request $r): array`), jangan menyalin kode.
   - `bioSlug` untuk kartu bio-link.
3. **Alihkan rute lama** (tautan dan bookmark lama tetap jalan):
   - `GET /profile` → `redirect()->route('settings', ['tab' => 'account'])` (nama route `profile.edit` tetap ada untuk redirect bawaan Breeze).
   - `GET /dashboard/officer-profile` → `redirect()->route('settings', ['tab' => 'officer'])`.
   - `PATCH /profile`, `DELETE /profile`, `PUT /password`, dan semua endpoint API officer **tidak diubah**. Pastikan setelah simpan/kesalahan validasi pengguna kembali ke `/settings?tab=account` (ubah `Redirect::route('profile.edit')` di `ProfileController@update` menjadi route `settings`).
4. **Menu akun** (`Components/UserAccountMenu.vue`): ganti item "Profil" dan "Profil Dinas Saya" dengan satu item **Pengaturan** (`/settings`, ikon `Settings`). Item "Bio-link saya" tetap. Cek juga `Layouts/AuthenticatedLayout.vue` yang menaut ke `profile.edit`.
5. **Teks lewat i18n** (`locales/id.json` + `en.json`, grup `settings`): Pengaturan/Settings, Akun/Account, Profil perwira/Officer profile, Bio-link, Kelola bio-link/Manage bio-link.
6. Hapus `Pages/Profile/Edit.vue` dan `Pages/Officer/OfficerProfile.vue` setelah tidak dirender lagi.

## Test
- Perbarui `tests/Feature/ProfileTest.php` dan `tests/Feature/Auth/PasswordUpdateTest.php`: `GET /profile` kini **302** ke `/settings?tab=account`; redirect setelah update mengarah ke `/settings?tab=account`.
- Test baru `tests/Feature/SettingsPageTest.php`: user biasa → 200, komponen `Settings/Index`, tanpa props officer; officer → 200 dengan props officer + `bioSlug`; tamu → redirect login; `/dashboard/officer-profile` → 302 ke `?tab=officer`.
- Seluruh `php artisan test` hijau.

## Jangan
- Jangan mengubah logika validasi, cooldown callsign, atau alur pengajuan pangkat — hanya memindahkan tampilan.
- Jangan memindahkan editor bio-link ke halaman ini.
- Commit hanya file yang terkait task ini; jangan `git add -A` / `git add .`.

## Cek
- `npm run lint:ui -- <file .vue yang diubah>` → 0 error; `npm run build` lulus.
- Manual: user biasa (hanya tab Akun), officer (3 tab), HP 375px (tab horizontal tidak meluber), verifikasi email & ubah password tetap jalan.

## Selesai jika
- [ ] Semua pengaturan diakses dari satu menu **Pengaturan**.
- [ ] Tautan lama `/profile` dan `/dashboard/officer-profile` mengalihkan ke tab yang benar.
- [ ] Baris SET-01 di `docs/ui/TASKS.md` diisi 🟣 + Dev Notes.
