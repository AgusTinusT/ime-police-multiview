# REC-01 — Rekomendasi website IME Police di Dashboard

**Prioritas:** 🟡 P2 · **Estimasi:** sedang · **Commit:** `feat(dashboard): REC-01 recommended-sites row`
Baca dulu: `GEMINI.md`, `docs/design-system/GEMINI-UI-PLAYBOOK.md`.

## Konteks
Platform ingin mempromosikan website roleplay IME Police lainnya (contoh pertama: **LSPD Handbook** di `https://ucup-meletup.site/`). Design concept ada di `public/images/recommended/lspd-background.png` dan `public/images/recommended/lspd-sprite.webp` — kartu lebar bergaya "movie poster" dengan latar kota malam.

Admin membuat dan mengatur entri, pengunjung melihat satu baris di dashboard.

## Yang dibuat

### 1. Database
Migration `database/migrations/<timestamp>_create_recommended_sites_table.php`:
```php
Schema::create('recommended_sites', function (Blueprint $t) {
    $t->id();
    $t->string('name', 60);               // "LSPD HANDBOOK" (judul besar)
    $t->string('badge', 40)->nullable();  // kata yang di-boks ("HANDBOOK")
    $t->string('poster_name', 80);        // "LOS SANTOS POLICE DEPARTMENT"
    $t->string('poster_subtitle', 60)->nullable(); // "Handbook Internal"
    $t->string('poster_image_url');        // URL gambar poster (kotak kiri)
    $t->string('background_image_url')->nullable(); // URL gambar latar kartu
    $t->string('url');                    // https://ucup-meletup.site/
    $t->string('cta_text', 40)->default('Kunjungi website');
    $t->text('description');
    $t->json('chips')->nullable();        // ["10-Codes","Penal Code","Patrol Report"]
    $t->boolean('is_featured')->default(false); // chip emas "REKOMENDASI"
    $t->boolean('is_active')->default(true);
    $t->unsignedInteger('sort_order')->default(0);
    $t->timestamps();
});
```

Seeder `database/seeders/RecommendedSiteSeeder.php` dengan 1 entri LSPD Handbook:
- name: "LSPD HANDBOOK", badge: "HANDBOOK"
- poster_name: "LOS SANTOS POLICE DEPARTMENT", poster_subtitle: "Handbook Internal"
- poster_image_url: "/images/recommended/lspd-sprite.webp"
- background_image_url: "/images/recommended/lspd-background.png"
- url: "https://ucup-meletup.site/"
- description: "Panduan lapangan untuk anggota Los Santos Police Department: rantai komando, 10-Codes, penal code, prosedur taktis, hingga pembuat laporan patroli dalam satu website."
- chips: `["10-Codes","Penal Code","Patrol Report"]`
- is_featured: true, is_active: true, sort_order: 0

Panggil seeder dari `DatabaseSeeder` dan jalankan `php artisan db:seed --class=RecommendedSiteSeeder` dalam langkah test.

### 2. Model
`app/Models/RecommendedSite.php` dengan `$fillable` sesuai kolom di atas, `$casts = ['chips' => 'array', 'is_featured' => 'boolean', 'is_active' => 'boolean']`.

### 3. API publik
`app/Http/Controllers/RecommendedSiteController.php`:
- `public function index()` → `RecommendedSite::where('is_active', true)->orderBy('sort_order')->orderBy('id')->get()` sebagai JSON (bungkus `{"data": [...]}`).
- `routes/web.php`: `Route::get('/api/v1/recommended-sites', [RecommendedSiteController::class, 'index'])->middleware('throttle:120,1');`

### 4. API admin (CRUD)
Dalam group `['auth', 'admin']` yang sudah ada:
```php
Route::prefix('api/v1/admin/recommended-sites')->group(function () {
    Route::get('/',                 [RecommendedSiteController::class, 'adminIndex']);
    Route::post('/',                [RecommendedSiteController::class, 'store']);
    Route::put('/{site}',           [RecommendedSiteController::class, 'update']);
    Route::patch('/{site}/toggle',  [RecommendedSiteController::class, 'toggleActive']);
    Route::delete('/{site}',        [RecommendedSiteController::class, 'destroy']);
});
```
Validasi field: `name|required|max:60`, `url|required|url`, `poster_image_url|required|string`, `background_image_url|nullable|string`, `description|required|max:600`, `chips|nullable|array`, `chips.*|string|max:30`. Admin bisa menerima URL eksternal **atau** path lokal `/images/...` (regex: `starts_with:http://,https://,/images/`).

### 5. Komponen publik
`resources/js/Components/Recommended/RecommendedSiteCard.vue`:
- Prop: `site` (object sesuai schema).
- Layout mengikuti `highlight-section.html` yang tersedia di luar repo sebagai referensi (desain sudah ada di `public/images/recommended/`). Ringkasnya:
  - Container `relative overflow-hidden rounded-2xl border` dengan `background: url($site.background_image_url) right center / cover` kalau diisi, fallback `bg-card`.
  - Grid dua kolom di `md+`, tumpuk vertikal di mobile.
  - Kiri: "poster" tinggi ±320px, lebar ±214px (desktop), border `border-amber-500/40`, teks kecil "WEBSITE PILIHAN" + gambar poster (dari `poster_image_url`, pakai `object-cover` di dalam kotak bulat atau crop sesuai gambar) + nama poster.
  - Kanan: judul besar `font-heading text-4xl md:text-5xl font-extrabold` dengan `badge` dibungkus border 3px putih (`<span class="border-2 border-foreground px-3 py-1">`).
  - Chip-chip berisi string `chips` + chip emas ("REKOMENDASI") kalau `is_featured`.
  - Paragraf deskripsi dibatasi `max-w-xl`, warna `text-muted-foreground`.
  - Tombol CTA merah → tautan ke `site.url` dengan `target="_blank" rel="noopener noreferrer"`, `cta_text`-nya dinamis.
- Mobile: susun poster di tengah atas, lalu badan di bawah, tombol CTA full-width.
- Jangan pakai `arbitrary-text-size` (`text-[11px]`) — gunakan kelas Tailwind resmi.

### 6. Dashboard integrasi
Di `resources/js/Pages/Dashboard.vue`:
- Fetch `/api/v1/recommended-sites` di `onMounted()` dengan `axios`, simpan ke `ref recommendedSites`.
- Tampilkan di dalam `CatalogRow` baru berjudul "Website rekomendasi" dengan subjudul "Jelajahi platform roleplay IME Police lainnya."
- Jika hanya 1 entri aktif: tampilkan 1 `RecommendedSiteCard` lebar penuh. Jika lebih dari 1: susun di `grid grid-cols-1 lg:grid-cols-2 gap-4` (dua kartu per baris di desktop).
- Letakkan baris ini **di bawah** TacClips dan di atas Squad (atau sesuai urutan yang sudah ada; cek `Dashboard.vue` dan sisipkan di posisi yang masuk akal).

### 7. UI admin
Tab baru atau section di halaman admin yang sudah ada (lihat `Pages/Admin/OfficerManagement.vue` untuk pola tab + CRUD). Lebih mudah: **tab baru "Rekomendasi website"** di sidebar `AdminLayout`.
Buat `resources/js/Components/Admin/RecommendedSitesTab.vue`:
- Daftar entri (table): poster thumbnail kecil, nama, URL, chip count, status (switch is_active), aksi (edit, hapus).
- Dialog add/edit dengan field: name, badge, poster_name, poster_subtitle, poster_image_url, background_image_url, url, cta_text, description (textarea), chips (comma-separated input yang dipecah ke array), is_featured (switch).
- Preview langsung: render `RecommendedSiteCard` dengan data form (opsional, kalau ada waktu).

### 8. i18n
`locales/id.json` + `en.json`, grup `dashboard`:
- `recommended_sites_title`: "Website rekomendasi" / "Recommended websites"
- `recommended_sites_subtitle`: "Jelajahi platform roleplay IME Police lainnya." / "Explore other IME Police roleplay platforms."
- `visit_site`: "Kunjungi website" / "Visit website"

## Test
`tests/Feature/RecommendedSitesTest.php` (baru):
1. `GET /api/v1/recommended-sites` → 200, hanya mengembalikan `is_active = true`, berurut `sort_order` lalu `id`.
2. Admin bisa POST entri baru → 201.
3. User biasa POST → 403.
4. Admin PATCH `/{id}/toggle` → `is_active` berbalik.
5. Admin DELETE → 204.
6. Validasi: URL tidak valid → 422; chip bukan array → 422.

## Jangan
- Jangan membuat sistem upload file. Admin memasukkan URL (eksternal atau `/images/...` yang sudah ada).
- Jangan menampilkan baris rekomendasi di Multiview atau halaman lain, hanya Dashboard.
- Jangan membuat chip editor fancy; input teks "comma-separated" sudah cukup.
- Commit hanya file terkait task; **jangan** `git add -A`.

## Cek
- `npm run lint:ui -- <file .vue yang diubah>` → 0 error; `npm run build` lulus.
- Seluruh `php artisan test` hijau.
- Visual: `/` (Dashboard) menampilkan satu kartu LSPD Handbook dari seeder, mobile 375px tetap terbaca.

## Selesai jika
- [ ] Admin bisa CRUD entri dari tab "Rekomendasi website".
- [ ] Dashboard menampilkan baris baru di antara TacClips dan Squad.
- [ ] Entri LSPD Handbook muncul via seeder.
- [ ] Baris REC-01 di `docs/ui/TASKS.md` diisi 🟣 + Dev Notes.
