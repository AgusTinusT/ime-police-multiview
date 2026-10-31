# BIO-01 — Promosi fitur bio-link officer (A + D + C)

**Prioritas:** 🟠 P1 · **Estimasi:** sedang · **Commit:** `feat(biolink): BIO-01 public promo page, empty state, visitor nudge`
Baca dulu: `GEMINI.md`, `docs/biolink/SIMPLE-LINKS.md`.

## Konteks
Fitur bio-link butuh verifikasi officer. Visitor baru melihat `/@slug` seseorang tapi tidak diajak ke mana pun. Officer yang buka Settings tapi belum terikat ke profil dinas hanya melihat pesan "Akun Anda belum terhubung" tanpa gambaran apa yang bisa mereka dapat. User yang tidak sempat mencoba cenderung tidak tertarik.

Pecah menjadi tiga titik: (A) halaman cerita, (C) ajakan halus di `/@slug`, (D) state kosong yang bergambar di Settings.

## A. Halaman promo `/bio-link`

### Route & controller
`routes/web.php` (public, tanpa auth): `Route::get('/bio-link', [BioLinkPromoController::class, 'show'])->name('biolink.promo');`
Controller baru `app/Http/Controllers/BioLinkPromoController.php` memanggil `Inertia::render('BioLinkPromo', [...])` dengan props:
- `themes` = `config('biolinks.theme_presets', [])`
- `exampleSlug` = slug yang di-seed (`eidoertachta`) kalau ada di DB, else `null`
- `isOfficer` = `$request->user()?->is_officer ?? false`
- `isAuthenticated` = `$request->user() !== null`

### Halaman `resources/js/Pages/BioLinkPromo.vue`
Pakai `TacticalLayout` + `PageContainer`. Alur konten (urut dari atas):
1. **Hero.** Judul `font-heading text-4xl md:text-5xl font-extrabold`: "Satu tautan untuk semua yang kamu buat." Subjudul `text-lg text-muted-foreground`: "Bagikan `/@kamu` di streammu — dukungan, komunitas, dan rekan patroli di satu halaman."
2. **Dua CTA utama berdampingan:**
   - Kiri (variant default): "Saya perwira — mulai verifikasi" → `/dashboard` kalau belum login (dengan redirect balik), atau `/settings?tab=officer` kalau sudah officer, atau buka `OfficerClaimModal` kalau login tapi belum officer. Reuse komponen `OfficerClaimModal.vue` yang sudah ada (lihat `ClipperPricing.vue:860`).
   - Kanan (variant outline): "Lihat contoh" → gulir mulus ke bagian galeri; kalau `exampleSlug` ada, tombol kecil tambahan "Buka `/@{exampleSlug}`" di bawahnya.
3. **Baris 3 kolom "Apa saja yang bisa kamu taruh"** dengan ikon lucide + 1 baris:
   - `Coins` — Donasi (Saweria, Trakteer, BagiBagi, Tako, Sociabuzz)
   - `Megaphone` — Media sosial (Instagram, TikTok, YouTube, Discord)
   - `Users` — Rekan patroli officer lain
4. **Galeri tema.** Pakai `config('biolinks.theme_presets')`: grid `grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4`. Setiap sel: nama tema, deskripsi, dan **thumbnail** dari `BioLinkView` yang di-render dengan data dummy (lihat `Pages/Dev/BioThemes.vue` sebagai referensi — pakai pola yang sama, hanya satu komponen `BioLinkView` dengan `theme-key` berbeda tiap sel, bungkus di `div.relative.h-96.overflow-hidden.rounded-xl` + `pointer-events-none` supaya tidak bisa diklik). Kalau sudah ada `Pages/Dev/BioThemes.vue` yang berbagi data dummy — ekstrak dummy itu ke `resources/js/lib/bioExampleData.js` dan impor di kedua halaman.
5. **Section "Kenapa harus verifikasi?"** — kartu dengan ikon `ShieldCheck`. Isi dengan 3 poin ramah (bukan menakut-nakuti):
   - "Hanya kamu yang bisa mengubah bio di `/@kamu` — tidak ada yang bisa mengaku."
   - "Lambang perwira resmi tampil di halaman, menandakan ini akun asli."
   - "Daftar rekan patroli otomatis tertaut ke officer lain, bukan link acak."
6. **FAQ singkat 4 item** dalam `Accordion` shadcn (impor dari `@/Components/ui/accordion`):
   - "Apakah saya harus bayar?" / "Tidak."
   - "Berapa lama proses verifikasi?" / "Sesudah token YouTube dicocokkan, otomatis sekitar beberapa menit."
   - "Apa yang terjadi kalau saya pensiun?" / "Halaman kamu ditandai 'Belum diklaim' dan link dimatikan sementara."
   - "Bisakah saya ganti slug `/@`?" / "Admin yang menyetujui, maksimal sekali per 90 hari."
7. **CTA penutup.** Satu banner lebar dengan ulang kedua tombol dari langkah 2.

### Teks (i18n)
`locales/id.json` + `en.json` grup `biolink_promo` — gunakan key `hero_title`, `hero_subtitle`, `cta_officer`, `cta_example`, `features_title`, `themes_title`, `verify_title`, `verify_points` (array), `faq_title`, `faq` (array of {q,a}), `footer_cta_title`.

## C. Ajakan halus di `/@slug`

Di `resources/js/Pages/Public/BioLink.vue` (bukan `BioLinkView.vue` — itu dipakai pratinjau Settings juga; polisi di sisi halaman publik saja), tambahkan **blok CTA di bawah footer halaman** yang muncul **hanya untuk**:
- Tamu (belum login) **atau**
- User login dengan `page.props.auth.user?.is_officer === false`

Isinya kartu kecil:
- Teks `text-sm`: "Suka tampilan ini? Kamu juga bisa punya bio-link resmi di `/@kamu`."
- Tombol `size="sm"` variant default: "Pelajari →" → `/bio-link` (halaman A).
- Tombol teks kecil "Sembunyikan" yang menyimpan `localStorage['biolink_promo_dismissed'] = '1'`; blok tidak muncul lagi di sesi berikutnya di perangkat tersebut.

Jangan muncul untuk officer yang sudah terikat (`is_officer === true`) — mereka tidak butuh ajakan.

## D. Empty state tab Bio-link di Settings

Di `resources/js/Pages/Settings/Index.vue`, bagian saat `activeTab === 'biolink'` tapi tidak ada `bioOfficer`:
Ganti kartu polos lama yang isinya hanya pesan jadi:
1. **Judul & paragraf** yang kini sudah ada tetap.
2. **Mini galeri 3 tema** menggunakan komponen yang sama dengan halaman A (bisa impor `BioExampleGallery.vue` yang diekstrak dari A).
3. **Tombol primary**: "Pelajari & mulai verifikasi" → `/bio-link`. Tombol sekunder "Hubungi Admin SASP" → `mailto:` kalau config `biolinks.admin_email` ada, else `null` dan tombol tidak ditampilkan.

## Test
`tests/Feature/BioLinkPromoTest.php`:
1. `GET /bio-link` sebagai tamu → 200, komponen `BioLinkPromo`, props `isOfficer=false`, `isAuthenticated=false`.
2. Login sebagai officer → prop `isOfficer=true`.
3. Login user biasa → prop `isOfficer=false`, `isAuthenticated=true`.
4. Prop `exampleSlug` berisi slug officer ter-bind pertama; kalau tidak ada officer ter-bind, `null`.

`tests/Feature/BioLink/PublicPageTest.php` (perbarui yang sudah ada atau tambahkan):
5. `/@slug` sebagai tamu → markup CTA promo tampil (cek string "Suka tampilan ini?" di HTML).
6. `/@slug` sebagai officer → CTA promo tidak tampil.

## Jangan
- Jangan membangun playground sungguhan (editor stateless) — di luar scope BIO-01.
- Jangan menambah dependency npm.
- Jangan menyentuh editor di `/settings?tab=biolink` saat `bioOfficer` ada (tidak ada perubahan untuk officer yang sudah terikat).
- Commit hanya file yang terkait task; **jangan** `git add -A`.

## Cek
- `npm run lint:ui -- <file .vue yang diubah>` → 0 error.
- `npm run build` lulus.
- Seluruh `php artisan test` hijau.
- Manual: `/bio-link` buka tanpa login, scroll ke galeri, buka FAQ, klik tombol officer → modal klaim muncul (atau diarahkan). `/@eidoertachta` sebagai tamu menampilkan CTA promo di bawah; sembunyikan → tidak muncul setelah refresh.

## Selesai jika
- [ ] `/bio-link` jadi halaman promo lengkap dengan galeri tema.
- [ ] `/@slug` menampilkan CTA promo bersyarat.
- [ ] Settings tab Bio-link kosong punya galeri + CTA.
- [ ] Baris BIO-01 di `docs/ui/TASKS.md` diisi 🟣 + Dev Notes.
