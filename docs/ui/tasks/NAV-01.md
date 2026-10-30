# NAV-01 — Susun ulang header, bottom nav mobile, dan footer

**Prioritas:** 🟠 P1 · **Estimasi:** kecil–sedang · **Commit:** `feat(ui): NAV-01 regroup header, mobile nav and footer`
Baca dulu: `GEMINI.md`, `docs/design-system/GEMINI-UI-PLAYBOOK.md`.

## Masalah
- Halaman **Pembaruan** (`/updates`) tidak tertaut di mana pun.
- Footer tidak memuat TacClips, Ranking, Pembaruan, dan Bio-link.
- Menu "Lainnya" di mobile mengulang 4 item yang sudah ada di bar bawah.
- Header desktop punya 6 menu; "Beranda" dobel dengan logo.

## Prinsip
1. Header & bottom nav = halaman yang sering dipakai, maks. 4 menu + akun.
2. Footer = peta lengkap semua halaman publik.
3. "Lainnya" (mobile) = semua yang tidak ada di bar bawah, **tanpa duplikat**.
4. Tidak ada halaman tanpa tautan.

## Yang harus dilakukan

### 1. Header desktop — `resources/js/Components/TacticalDashboardHeader.vue`
- Hapus menu teks **Beranda** (logo tetap tautan ke `/`; pastikan `aria-label="Beranda"` di logo).
- Urutan menu: **Multiview** · **TacClips** · **Perwira ▾** · **Clip Studio** (badge BETA tetap).
- **Perwira** = `DropdownMenu` shadcn-vue (`@/Components/ui/dropdown-menu`) berisi **Direktori perwira** (`/officers`) dan **Ranking** (`/ranking`). Menu Perwira tampil aktif bila URL diawali `/officers` atau `/ranking`.
- Pertahankan cara penanda menu aktif yang sudah ada.

### 2. Bottom nav mobile — `resources/js/Components/TacticalMobileBottomNav.vue`
- Bar bawah (5 slot): **Beranda** · **Multiview** · **TacClips** · **Perwira** (`/officers`, aktif juga untuk `/ranking`) · **Lainnya**.
- Isi sheet **Lainnya**, berurutan, tanpa item yang sudah ada di bar:
  - Clip Studio (`/clipper`)
  - Ranking (`/ranking`)
  - Bio-link saya (`/dashboard/bio-links`) — **hanya** bila `$page.props.auth.user?.is_officer` (pola sama dengan `UserAccountMenu.vue` baris ±138)
  - pemisah
  - Pembaruan (`/updates`), Tentang (`/about`), FAQ (`/faq`), Kirim masukan (`/feedback`)
- Hapus item Beranda, Multiview, TacClips, Perwira dari sheet.

### 3. Footer — `resources/js/Components/TacticalFooter.vue`
Tiga kolom tautan (desktop `grid-cols-3`, mobile `grid-cols-2`):

| Jelajahi | Untuk perwira | Bantuan |
| :--- | :--- | :--- |
| Multiview `/multiview` | Clip Studio `/clipper` | Tentang `/about` |
| TacClips `/action-clips` | Bio-link perwira `/dashboard/bio-links` | FAQ `/faq` |
| Direktori perwira `/officers` | | Pembaruan `/updates` |
| Ranking `/ranking` | | Kirim masukan `/feedback` |

- "Bio-link perwira" tampil untuk semua orang (tamu diarahkan ke login oleh middleware — itu benar).
- Tagline, disclaimer, dan copyright tetap.
- Di mobile, beri ruang bawah agar footer tidak tertutup bottom nav: `pb-[calc(4rem+env(safe-area-inset-bottom))] md:pb-*` (sesuaikan dengan tinggi bar yang ada).

### 4. Teks
- Semua label lewat i18n: tambah/ubah key di `resources/js/locales/id.json` **dan** `en.json` (grup `nav` dan `footer`; ikuti nama grup yang sudah ada). Jangan menulis teks Indonesia langsung di template.
- Label: Multiview, TacClips, Perwira / Officers, Direktori perwira / Officer directory, Ranking, Clip Studio, Lainnya / More, Bio-link saya / My bio-link, Pembaruan / Updates, Tentang / About, FAQ, Kirim masukan / Send feedback, Jelajahi / Explore, Untuk perwira / For officers, Bantuan / Help.

## Jangan
- Jangan mengubah `MultiviewHeader.vue`, `UserAccountMenu.vue`, atau halaman lain.
- Jangan menambah dependency.
- Commit **hanya** 5 file: tiga komponen di atas + `id.json` + `en.json`. Cek dengan `git status` sebelum `git add`; jangan `git add -A` / `git add .`.

## Cek
- `npm run lint:ui -- <3 komponen>` → 0 error; `npm run build` lulus.
- `php artisan test --filter=UiKit` tetap lulus.
- Manual: desktop ≥1024px (dropdown Perwira bisa dibuka dengan keyboard: Tab → Enter → panah), HP 375px (sheet Lainnya tanpa duplikat, footer tidak tertutup bar bawah).

## Selesai jika
- [ ] Setiap rute di tabel footer bisa dicapai dari footer; `/updates` kini tertaut.
- [ ] Header desktop 4 menu + akun; bottom nav 5 slot; sheet Lainnya tanpa duplikat.
- [ ] Baris NAV-01 di `docs/ui/TASKS.md` diisi 🟣 + Dev Notes.
