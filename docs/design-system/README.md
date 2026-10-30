# Tactical UI Kit — Design System

**Dasar:** [shadcn-vue](https://www.shadcn-vue.com) (MIT) — gaya `new-york`, mode JavaScript, di atas **Tailwind CSS v4**.
**Karakter:** aksen taktis "command center" (sudut terpotong + garis tipis, terinspirasi [augmented-ui](https://augmented-ui.com)) diimplementasikan sendiri dengan CSS `clip-path` di `TacticalFrame` — **terbatas** pada elemen unggulan. *(augmented-ui tidak dipasang: 167 KB / 17 KB gzip render-blocking untuk 3 bentuk saja — keputusan review DS-01.)*
**Mode:** **dark-only.**
**Untuk agen coding:** cara menerapkan ke halaman ada di [GEMINI-UI-PLAYBOOK.md](GEMINI-UI-PLAYBOOK.md).

> Mengapa shadcn-vue? Komponennya disalin ke repo sebagai file `.vue` biasa (milik kita, bisa diubah), aksesibel (dibangun di atas Reka UI), tema diatur lewat CSS variable, dan dipakai oleh starter kit resmi Laravel + Vue. Polanya seragam sehingga hasil kerja AI konsisten.

---

## 1. Struktur Folder

| Path | Isi | Siapa yang mengubah |
| :--- | :--- | :--- |
| `components.json` | Konfigurasi shadcn-vue (style `new-york`, `typescript: false`, alias) | DS-01 saja |
| `resources/js/lib/utils.js` | Helper `cn()` (clsx + tailwind-merge) | DS-01 saja |
| `resources/js/Components/ui/<nama>/` | Komponen shadcn-vue hasil CLI (`button`, `card`, `dialog`, …) | **Hanya lewat CLI** `npx shadcn-vue@latest add <nama>`; perubahan gaya global lewat token, bukan edit massal |
| `resources/js/Components/tactical/` | Komponen khas proyek: `TacticalFrame`, `LiveBadge`, `DeptBadge`, `StatTile`, `PageHeader`, `PageContainer`, `OfficerAvatar`, `SectionHeader`, `EmptyState` | DS-02, lalu ditambah bila pola baru muncul di ≥2 halaman |
| `resources/css/app.css` | Import Tailwind, token (`:root`), `@theme inline`, aksen taktis | DS-00/DS-01 |

---

## 2. Token (CSS variable)

Didefinisikan di `resources/css/app.css` pada `:root`; `<html class="dark">` dipasang permanen di `app.blade.php` agar varian `dark:` di komponen shadcn ikut aktif. Nilai memakai hex dari palet yang sudah dipakai proyek (tampilan lama tidak berubah drastis).

### 2.1 Token standar shadcn-vue

| Variabel | Kelas | Nilai | Peran |
| :--- | :--- | :--- | :--- |
| `--background` / `--foreground` | `bg-background` / `text-foreground` | `#020617` / `#f1f5f9` | Latar halaman / teks utama |
| `--card` / `--card-foreground` | `bg-card` / `text-card-foreground` | `#0f172a` / `#f1f5f9` | Kartu, panel |
| `--popover` / `--popover-foreground` | `bg-popover` | `#0f172a` / `#f1f5f9` | Dropdown, popover, tooltip |
| `--primary` / `--primary-foreground` | `bg-primary` / `text-primary-foreground` | `#2563eb` / `#ffffff` | Aksi utama, link, fokus |
| `--secondary` / `--secondary-foreground` | `bg-secondary` | `#1e293b` / `#e2e8f0` | Tombol sekunder |
| `--muted` / `--muted-foreground` | `bg-muted` / `text-muted-foreground` | `#1e293b` / `#94a3b8` | Area redup / teks sekunder |
| `--accent` / `--accent-foreground` | `bg-accent` | `#1e293b` / `#f1f5f9` | Hover item menu/list |
| `--destructive` | `bg-destructive` / `text-destructive` | `#ef4444` | Hapus, error, banned |
| `--border` / `--input` / `--ring` | `border-border` / `border-input` / `ring-ring` | `#1e293b` / `#334155` / `#3b82f6` | Garis, border input, fokus |
| `--radius` | `rounded-md` (= radius−2px), `rounded-lg` (= radius), `rounded-xl` | `0.5rem` | Skala sudut shadcn |
| `--chart-1…5` | `fill-chart-1` … | `#3b82f6`, `#10b981`, `#f59e0b`, `#06b6d4`, `#ef4444` | Grafik |
| `--sidebar*` | `bg-sidebar` … | sama dengan `card` / `primary` / `accent` / `border` | Komponen Sidebar admin |

### 2.2 Token tambahan proyek (pola "custom color" shadcn)

| Variabel | Kelas | Nilai | Peran |
| :--- | :--- | :--- | :--- |
| `--success` / `--success-foreground` | `bg-success`, `text-success` | `#10b981` / `#022c22` | Berhasil, approved, online |
| `--warning` / `--warning-foreground` | `bg-warning`, `text-warning` | `#f59e0b` / `#451a03` | Pending, peringatan |
| `--info` / `--info-foreground` | `bg-info`, `text-info` | `#06b6d4` / `#083344` | Informasi |
| `--live` / `--live-foreground` | `bg-live`, `text-live` | `#ef4444` / `#ffffff` | **Hanya** indikator siaran LIVE |
| `--dept-lspd` · `--dept-bcso` · `--dept-sasp` · `--dept-sapr` | `bg-dept-lspd`, `text-dept-sasp`, … | `#2563eb` · `#d97706` · `#475569` · `#059669` | Identitas departemen |
| `--hud-line` | `border-hud` | `#3b82f6` @ 40% | Garis aksen taktis |

Semua didaftarkan di `@theme inline { --color-success: var(--success); … }` sehingga opacity modifier berlaku (`bg-success/15`).

### 2.3 Tipografi

| Peran | Kelas | Font |
| :--- | :--- | :--- |
| Judul hero | `font-heading text-4xl lg:text-6xl font-bold tracking-tight` | Rajdhani |
| Judul halaman | `font-heading text-3xl font-bold` | Rajdhani |
| Judul section | `font-heading text-2xl font-semibold` | Rajdhani |
| Judul kartu | `text-base font-semibold` | Plus Jakarta Sans |
| Teks | `text-sm` (default), `text-base` (paragraf penting) | Plus Jakarta Sans |
| Pendukung | `text-xs text-muted-foreground` | Plus Jakarta Sans |
| Label taktis | `font-mono text-xs uppercase tracking-wider` (komponen `<Label>` tidak perlu diubah) | JetBrains Mono |

**Ukuran minimum 12px (`text-xs`).** Tidak ada `text-[Npx]`. `font-black` → `font-extrabold`.

### 2.4 Jarak & Layout
- Container: `<PageContainer>` = `mx-auto w-full max-w-screen-2xl px-4 sm:px-6 lg:px-8`.
- Jarak section: `py-8 lg:py-12`; jarak antar-elemen dalam kartu: `gap-4`; grid kartu: `gap-4 lg:gap-6`.
- Area sentuh minimal 40px (`size-10` / `h-10`).

---

## 3. Komponen

### 3.1 Komponen shadcn-vue yang dipakai

| Kebutuhan | Komponen (`@/Components/ui/...`) |
| :--- | :--- |
| Tombol | `Button` (variant: `default`, `secondary`, `outline`, `ghost`, `destructive`, `link`; size: `sm`, `default`, `lg`, `icon`) |
| Badge/label | `Badge` (variant: `default`, `secondary`, `outline`, `destructive` + varian proyek, lihat §3.3) |
| Kartu | `Card`, `CardHeader`, `CardTitle`, `CardDescription`, `CardContent`, `CardFooter` |
| Form | `Input`, `Textarea`, `Label`, `Select`, `NativeSelect`, `Checkbox`, `Switch`, `RadioGroup`, `Field`, `Form` |
| Overlay | `Dialog` (modal), `AlertDialog` (konfirmasi hapus), `Sheet` (drawer samping/bawah), `Popover`, `DropdownMenu`, `Tooltip`, `HoverCard` |
| Navigasi | `Tabs`, `Breadcrumb`, `Pagination`, `NavigationMenu`, `Sidebar` (admin) |
| Data | `Table`, `Data Table` (TanStack), `Avatar`, `Skeleton`, `Progress`, `ScrollArea`, `Separator`, `Chart` |
| Umpan balik | `Sonner` (toast), `Alert`, `Empty`, `Spinner` |
| Media | `Carousel` (Embla) — dipakai Hero Slider, `AspectRatio` |

Ikon: **`@lucide/vue`** (penerus resmi `lucide-vue-next`; jangan pasang keduanya). Emoji sebagai ikon UI diganti bertahap; emoji di konten (mis. judul buatan user) dibiarkan.

### 3.2 Komponen taktis proyek (`@/Components/tactical/...`)

| Komponen | Props | Fungsi |
| :--- | :--- | :--- |
| `PageContainer` | — | Container standar |
| `PageHeader` | `title`, `description`, `eyebrow` (label mono kecil), slot `actions` | Kepala halaman seragam |
| `SectionHeader` | `title`, `description`, slot `actions` | Kepala section |
| `TacticalFrame` | `variant`: `panel` · `hero` · `tag`; `accent` (token warna) | Bingkai sudut terpotong (clip-path) (§4) |
| `LiveBadge` | `viewers?` | Badge LIVE merah berdenyut (`motion-safe`) |
| `DeptBadge` | `dept`: LSPD · BCSO · SASP · SAPR | Badge warna departemen |
| `StatTile` | `label`, `value`, `trend?`, `icon?` | Angka ringkas (dashboard/admin) |
| `OfficerAvatar` | `src`, `name`, `live`, `size` | `Avatar` + cincin LIVE |
| `EmptyState` | `icon`, `title`, `description`, slot `action` | Membungkus `Empty` dengan gaya proyek |

### 3.3 Varian tambahan
Tambahkan langsung di file varian komponen (`badge/index.js`, `button/index.js`) — satu kali di DS-02, bukan per halaman:
- `Badge`: `success`, `warning`, `info`, `live`.
- `Button`: tidak ada varian baru; aksi berbahaya = `destructive`.

---

## 4. Aksen Taktis (clip-path sendiri)

Tujuannya memberi rasa *command center* tanpa membuat UI ramai.

**Boleh dipakai di:** slide hero, `PageHeader` halaman utama (Dashboard, Multiview, Direktori), kartu officer unggulan/spotlight, `StatTile` di dashboard, header dialog penting (klaim, kill-switch).
**Jangan dipakai di:** tombol, input, tabel, item list biasa, badge kecil, setiap kartu di grid.
**Batas:** maksimal **2 `TacticalFrame` per layar**.

Selalu lewat `<TacticalFrame>` — jangan menulis `clip-path` sudut atau kelas `.tactical-frame` langsung di halaman.

| `variant` | Bentuk | Ukuran potongan (`--tf-cut`) | Garis | Contoh pemakaian |
| :--- | :--- | :---: | :---: | :--- |
| `panel` | sudut kiri-atas & kanan-bawah terpotong | 12px | 1px warna aksen | Kartu spotlight, StatTile |
| `hero` | sama, potongan lebih besar | 24px | 1px warna aksen | Slide hero |
| `tag` | sama, kecil | 6px | tanpa garis (latar `aksen/15`) | Label eyebrow di PageHeader |

**Teknik (tanpa library):** elemen luar diberi `clip-path` poligon 6 titik dengan latar = warna aksen; elemen dalam diberi poligon yang sama dengan inset 1px dan latar `bg-card` → menghasilkan garis tipis yang ikut terpotong. Warna dari token: `--tf-color: var(--color-<accent>)`. CSS-nya ±30 baris di `app.css` (`@utility tactical-frame …`). Dibungkus `@supports (clip-path: polygon(0 0))` — tanpa dukungan, tampil sebagai kartu biasa.

**Detail HUD tambahan** (utility di `app.css`, opsional): `.hud-grid` (latar grid tipis 4% opacity), `.hud-corners` (sudut siku di 4 pojok). Dipakai hanya di hero & PageHeader utama.

---

## 5. Aturan

| ✅ Lakukan | ❌ Jangan |
| :--- | :--- |
| Komponen `ui/` atau `tactical/` | Menulis ulang tombol/kartu/modal dengan `div` + kelas |
| Token (`bg-card`, `text-muted-foreground`, `border-border`, `text-destructive`, `bg-success/15`) | Palet mentah (`bg-slate-900`, `text-red-500`) atau hex di kelas |
| `text-xs` minimum | `text-[10px]`, `text-[9px]` |
| `cn()` untuk menggabung kelas kondisional | Template string kelas panjang |
| `AlertDialog` untuk konfirmasi hapus/ban | `confirm()` / `prompt()` bawaan browser (diganti saat migrasi) |
| `toast()` dari Sonner | Implementasi toast sendiri per halaman |
| `motion-safe:` untuk animasi dekoratif | `animate-pulse` tanpa pengaman |
| `aria-label` pada tombol ikon | Tombol ikon tanpa label |

Penegakan: `npm run lint:ui` (DS-03) untuk file yang berubah, serta `npm run check` (`npm run lint:ui -- --all && npm run build`) sebagai pemeriksaan wajib sebelum rilis/deploy (MIG-10).

---

## 6. Referensi
- Dokumentasi komponen & contoh: [shadcn-vue.com/docs/components](https://www.shadcn-vue.com/docs/components)
- Tema & custom color: [shadcn-vue.com/docs/theming](https://www.shadcn-vue.com/docs/theming)
- Contoh struktur Inertia + shadcn-vue (TypeScript, terjemahkan ke JS): [laravel/vue-starter-kit](https://github.com/laravel/vue-starter-kit) (MIT)
- Inspirasi bentuk sudut: [augmented-ui.com](https://augmented-ui.com) (tidak dipasang sebagai dependensi)
