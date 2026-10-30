# PRV-01 — Officer bisa menyembunyikan foto profil YouTube

**Prioritas:** 🟠 P1 · **Estimasi:** sedang · **Commit:** `feat(officer): PRV-01 avatar hide toggle`
Baca dulu: `GEMINI.md`, `docs/design-system/GEMINI-UI-PLAYBOOK.md`.

## Masalah
Avatar officer diisi dari foto profil YouTube. Beberapa officer memakai wajah asli dan ingin menyembunyikannya di seluruh situs tanpa mengubah akun YouTube mereka.

## Yang dibuat

### 1. Database
Migration baru: `officers.avatar_hidden` boolean, default `false`, after `avatar_url`.

### 2. Model & Response
- `App\Models\Officer`: tambahkan `avatar_hidden` ke `$fillable` dan `$casts` (boolean).
- Setiap tempat yang mengirim `avatar_url` ke frontend harus mengirim `null` ketika `avatar_hidden = true`. Dibuat accessor baru:
  ```php
  public function getPublicAvatarUrlAttribute(): ?string
  {
      return $this->avatar_hidden ? null : $this->avatar_url;
  }
  ```
  Ganti setiap `->avatar_url` pada array/payload yang keluar ke klien dengan `->public_avatar_url`:
  - `app/Http/Controllers/PublicBioLinkController.php` (`avatar_url` di officer & recommended_officer)
  - `app/Http/Controllers/PoliceCommandController.php` (`buildOfficerPayload`, leaderboard mapping, Top 10 patroli)
  - `app/Http/Controllers/OfficerDirectoryController.php` (bila ada)
  - `app/Http/Controllers/SquadController.php` (`officers.*.avatar_url`)
  - `app/Services/HeroSlideService.php` (live spotlight)
  - **Jangan** ganti di tempat admin (`Admin/OfficerManagement`), admin tetap melihat foto asli.
  - **Jangan** ganti pada payload sync job (`YouTubeScraperService`) — itu internal, bukan ke klien.
- Scraper tetap menyimpan `avatar_url` yang asli dari YouTube; hanya tampilan publik yang pakai accessor.

### 3. UI toggle (Settings → Profil perwira)
Tambahkan **Card baru "Foto profil"** di `Components/Settings/OfficerSettings.vue`, sebelum "Hapus akun"/section pengaturan terakhir. Berisi:
- `OfficerAvatar` dengan foto saat ini (atau fallback kalau sedang disembunyikan). Ukuran `lg`.
- Pesan singkat:
  > "Foto diambil dari saluran YouTube Anda. Beberapa perwira memakai wajah asli di saluran mereka; Anda bisa menyembunyikan foto ini dari halaman publik, direktori, ranking, bio-link, dan Multiview. Foto akan diganti simbol default."
- `Switch` "Sembunyikan foto profil saya" terikat ke `avatar_hidden`.
- Teks di bawah switch berubah sesuai status: "Tampil di halaman publik." atau "Disembunyikan — simbol default dipakai di semua halaman."

### 4. API (officer sendiri)
Tambahkan endpoint `PATCH /api/v1/officer/avatar-visibility` di route group yang sudah ada (`auth`, `verified`, `officer`, `not_banned`), dengan throttle `throttle:officer-profile-save` (sudah terdaftar di `AppServiceProvider`).

Controller: `OfficerSelfProfileController@updateAvatarVisibility(Request $r)`:
- Validasi `avatar_hidden: required|boolean`.
- Update officer milik user. Admin lewat endpoint berbeda (lihat 5).
- Log perubahan ke audit log (ikuti pola `AuditLogger::log` yang ada untuk perubahan profil).
- Return `{ avatar_hidden: bool, message: "Foto profil disembunyikan/ditampilkan" }`.

### 5. Admin bisa menyembunyikan foto officer mana saja (moderasi)
Field `avatar_hidden` ditambahkan ke validasi `OfficerManagementController@update`:
```php
'avatar_hidden' => 'sometimes|boolean',
```
dan diisi ke `$validated` lalu `$officer->update($validated)`. Admin bisa menyembunyikan foto walau officer sendiri tidak mengaktifkannya (penegakan privasi/moderasi), dan sebaliknya.

Di `resources/js/Pages/Admin/OfficerManagement.vue` form edit (sekitar baris 412/1112 — cari field `avatar_url`): tambahkan `avatar_hidden: false` ke state `form`, muat dari `officer.avatar_hidden` di `openEdit*` handler, lalu tambahkan baris baru di template, tepat di bawah input `avatar_url`:
- `Switch` + label **"Sembunyikan foto profil officer ini"**.
- Pesan kecil di bawah: "Foto diganti simbol default di halaman publik, direktori, ranking, bio-link, dan Multiview. Admin tetap melihat foto asli."
- Panel admin **tetap menampilkan `officer.avatar_url` asli** (`<img>` di `OfficerManagement.vue` baris ±1737 dan `OfficerDetailSheet.vue` baris ±111). Jangan diubah.
- Audit log: `AuditLogger::log('officer.avatar_hidden_changed', ...)` setiap perubahan oleh admin, dengan `before`/`after` dan nama admin.

Admin yang **tidak** memakai endpoint `/api/v1/officer/avatar-visibility`; dia memakai PUT officer yang sudah ada. Officer memakai endpoint PATCH yang di 4.

### 6. Props untuk SettingsController
Kirim `officer.avatar_hidden` di `OfficerSelfProfileController::pageProps` agar form di Settings tahu status awal.

### 7. Teks
Semua label lewat i18n (`locales/id.json` + `en.json`, grup `settings` untuk officer dan `admin` untuk admin).

## Test
`tests/Feature/Officer/AvatarVisibilityTest.php` (baru):
1. Officer PATCH endpoint → sukses, `avatar_hidden` tersimpan.
2. User non-officer PATCH → 403.
3. Setelah `avatar_hidden=true`, `GET /@slug` (bio-link publik) → `officer.avatar_url` adalah `null`.
4. Setelah `avatar_hidden=true`, direktori perwira (API/response) → avatar `null`.
5. `GET /dashboard` → leaderboard officer tersebut `avatar_url` `null`.
6. Admin melihat officer di `/admin/officers` → tetap melihat `avatar_url` asli (test boleh asersi property Officer, bukan HTTP).
7. **Admin PUT officer** dengan `avatar_hidden=true` → sukses, kolom tersimpan, audit log terbuat.
8. **Admin non-admin** (user biasa) PUT officer → 403 (tidak bisa menegakkan privasi orang lain).

## Jangan
- Jangan menghapus kolom `avatar_url` — data asli disimpan.
- Jangan mengubah `OfficerAvatar.vue`. Komponen itu sudah menampilkan fallback inisial saat `src` kosong.
- Commit hanya file terkait task; jangan `git add -A` / `git add .`.

## Cek
- `npm run lint:ui -- <file .vue yang diubah>` → 0 error; `npm run build` lulus.
- `php artisan test` seluruhnya hijau.

## Selesai jika
- [ ] Officer bisa menyalakan/mematikan toggle di Pengaturan → Profil perwira.
- [ ] Admin bisa menyalakan/mematikan toggle di form edit officer di `/admin/officers`.
- [ ] Foto profil hilang di halaman publik, direktori, ranking, bio-link, Multiview saat disembunyikan — baik dari aksi officer sendiri maupun admin.
- [ ] Admin tetap melihat foto asli.
- [ ] Perubahan oleh officer **atau** admin tercatat di audit log.
- [ ] Baris PRV-01 di `docs/ui/TASKS.md` diisi 🟣 + Dev Notes.
