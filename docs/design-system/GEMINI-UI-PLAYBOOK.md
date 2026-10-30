# GEMINI UI PLAYBOOK — Cara Memigrasi Halaman ke Tactical UI Kit

Dokumen ini adalah **resep wajib** setiap kali kamu (agen coding) mengerjakan task `MIG-xx` atau membuat UI baru. Baca file ini + brief task saja. Rujukan token & komponen: [README.md](README.md) (buka hanya bagian yang perlu).

---

## A. Prinsip Migrasi

1. **Tampilan berubah, perilaku tidak.** Jangan mengubah logika, API call, props, emit, route, teks i18n, atau urutan konten. Hanya markup & kelas.
2. **Komponen dulu.** Setiap tombol, kartu, badge, input, modal, dropdown, tab, tabel, toast, dan konfirmasi **harus** memakai komponen dari `@/Components/ui/*` atau `@/Components/tactical/*`.
3. **Token saja.** Setelah migrasi, file tidak boleh berisi palet mentah (`slate-`, `gray-`, `blue-600`, `red-500`, …), hex di kelas, atau `text-[Npx]`. Ukur dengan `npm run lint:ui -- <file>`.
4. **Satu file besar = beberapa commit.** File > 800 baris dipecah per bagian (header, list, modal, …) sesuai brief; setiap bagian harus tetap jalan.
5. **Jangan sentuh `Components/ui/*`** kecuali brief memintanya. Kebutuhan gaya baru → token atau komponen `tactical/`.

---

## B. Langkah Kerja per Halaman

1. Baca brief `MIG-xx` → catat file target.
2. Jalankan `npm run lint:ui -- <file>` → simpan jumlah temuan awal (untuk Dev Notes).
3. Ambil screenshot sebelum (390px & 1280px) halaman terkait.
4. Ganti **struktur** dulu: container → `PageContainer`, judul → `PageHeader`/`SectionHeader`, kartu → `Card`.
5. Ganti **kontrol**: tombol → `Button`, badge → `Badge`/`LiveBadge`/`DeptBadge`, input → `Input`/`Select`/`Switch`…, modal → `Dialog`/`Sheet`, `confirm()/prompt()` → `AlertDialog`/`Dialog` dengan field alasan, toast lokal → `toast()` Sonner.
6. Ganti **kelas sisa** memakai tabel §C.
7. Ganti ikon emoji di tombol/menu → ikon `@lucide/vue` (lihat §E).
8. Tambahkan aksen taktis **hanya** bila brief menyebutnya (§F).
9. `npm run lint:ui -- <file>` harus **0 temuan**; `npm run build` sukses; test terkait (`php artisan test --filter=...`) hijau.
10. Screenshot sesudah (390px & 1280px), bandingkan: tata letak sama, tidak ada teks terpotong/overflow horizontal.
11. Commit `refactor(ui): MIG-xx <bagian>`; isi Dev Notes: temuan lint sebelum → sesudah, komponen yang dipakai, catatan visual.

---

## C. Tabel Konversi Kelas

| Lama | Baru |
| :--- | :--- |
| `bg-slate-950` | `bg-background` |
| `bg-slate-900`, `bg-slate-900/xx` | `bg-card` (atau `bg-card/80` untuk overlay) |
| `bg-slate-800`, `hover:bg-slate-800` | `bg-muted` / `hover:bg-accent` |
| `border-slate-800`, `border-white/5`, `border-white/10` | `border-border` |
| `border-slate-700` | `border-input` |
| `text-white`, `text-slate-100`, `text-slate-200` | `text-foreground` |
| `text-slate-300` | `text-foreground/80` |
| `text-slate-400`, `text-slate-500`, `text-gray-400` | `text-muted-foreground` |
| `bg-blue-600 hover:bg-blue-500 text-white` (tombol) | `<Button>` |
| `text-blue-400`, `text-cyan-400` (link/aksen) | `text-primary` / `text-info` |
| `bg-red-600`, `text-red-400`, `rose-*` (bahaya) | `<Button variant="destructive">` / `text-destructive` |
| merah untuk siaran LIVE | `<LiveBadge>` / `text-live` / `bg-live` |
| `emerald-*`, `green-*` (status ok) | `text-success` / `bg-success/15` / `<Badge variant="success">` |
| `amber-*`, `yellow-*` (pending) | `text-warning` / `bg-warning/15` / `<Badge variant="warning">` |
| warna departemen (LSPD biru, BCSO amber, SASP slate, SAPR hijau) | `<DeptBadge :dept="...">` / `text-dept-lspd` … |
| `text-[8px]`…`text-[11px]` | `text-xs` |
| `text-[12px]`, `text-[13px]` | `text-xs` / `text-sm` |
| `font-black` | `font-extrabold` |
| `rounded-2xl`, `rounded-xl` (kartu) | otomatis dari `Card` / `rounded-xl` |
| `rounded-lg` (kontrol) | otomatis dari komponen / `rounded-md` |
| `shadow-lg`, `shadow-xl` pada kartu | hapus (Card sudah punya `shadow-xs`) |
| `animate-pulse`, `animate-ping` | `motion-safe:animate-pulse` / `motion-safe:animate-ping` |
| `max-w-screen-2xl mx-auto px-4 sm:px-6 lg:px-8` | `<PageContainer>` |

Kasus abu-abu (gradient hero, overlay video, warna grafik): gunakan token (`from-background via-background/70 to-transparent`, `fill-chart-1`). Jika benar-benar tidak ada padanan, tulis di Dev Notes dan biarkan — **jangan** menambah token sendiri.

---

## D. Konversi Komponen (sebelum → sesudah)

Import selalu dari folder komponen:
```js
import { Button } from '@/Components/ui/button'
import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter } from '@/Components/ui/card'
import { Badge } from '@/Components/ui/badge'
import { LiveBadge, DeptBadge, PageHeader, PageContainer } from '@/Components/tactical'
import { toast } from 'vue-sonner'
```

**Tombol & link Inertia**
```vue
<!-- sebelum -->
<a href="/multiview" class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-500 text-white text-xs font-mono">Buka Multiview</a>
<!-- sesudah -->
<Button as-child size="sm"><Link href="/multiview"><MonitorPlay class="size-4" /> Buka Multiview</Link></Button>
```

**Kartu**
```vue
<!-- sebelum -->
<div class="bg-slate-900 border border-slate-800 rounded-xl p-5">
  <h3 class="text-sm font-bold text-white">Statistik</h3>
  <p class="text-[11px] text-slate-400">7 hari terakhir</p>
  ...
</div>
<!-- sesudah -->
<Card>
  <CardHeader>
    <CardTitle>Statistik</CardTitle>
    <CardDescription>7 hari terakhir</CardDescription>
  </CardHeader>
  <CardContent>...</CardContent>
</Card>
```

**Badge status**
```vue
<span class="px-2 py-0.5 rounded bg-emerald-500/10 text-emerald-400 text-[10px]">APPROVED</span>
→ <Badge variant="success">APPROVED</Badge>

<span class="bg-red-600 text-white text-[9px] animate-pulse">LIVE</span>
→ <LiveBadge :viewers="stream.viewers_count" />
```

**Modal**
```vue
<!-- sebelum: div fixed inset-0 + v-if="showModal" + tombol tutup manual -->
<!-- sesudah -->
<Dialog v-model:open="showModal">
  <DialogContent class="sm:max-w-lg">
    <DialogHeader>
      <DialogTitle>Klaim Profil Perwira</DialogTitle>
      <DialogDescription>Verifikasi kepemilikan channel YouTube.</DialogDescription>
    </DialogHeader>
    ...
    <DialogFooter><Button @click="submit">Kirim</Button></DialogFooter>
  </DialogContent>
</Dialog>
```
Drawer/bottom-sheet di HP → `Sheet` (`side="bottom"` / `"right"`).

**Konfirmasi & alasan (ganti `confirm()` / `prompt()`)**
```vue
<AlertDialog v-model:open="confirmDelete">
  <AlertDialogContent>
    <AlertDialogHeader>
      <AlertDialogTitle>Hapus tautan ini?</AlertDialogTitle>
      <AlertDialogDescription>Tindakan ini tidak dapat dibatalkan.</AlertDialogDescription>
    </AlertDialogHeader>
    <AlertDialogFooter>
      <AlertDialogCancel>Batal</AlertDialogCancel>
      <AlertDialogAction @click="destroy">Hapus</AlertDialogAction>
    </AlertDialogFooter>
  </AlertDialogContent>
</AlertDialog>
```
Bila butuh alasan (ban, suspend, reject): pakai `Dialog` + `Textarea` wajib + tombol nonaktif sampai alasan ≥ 3 karakter.

**Form field**
```vue
<div class="grid gap-2">
  <Label for="callsign">Callsign</Label>
  <Input id="callsign" v-model="form.callsign" />
  <p v-if="errors.callsign" class="text-xs text-destructive">{{ errors.callsign }}</p>
</div>
```
Select: `<Select v-model="form.dept"><SelectTrigger><SelectValue placeholder="Pilih" /></SelectTrigger><SelectContent><SelectItem value="LSPD">LSPD</SelectItem>…</SelectContent></Select>`. Toggle boolean → `Switch` (`v-model`).

**Toast**
```js
// sebelum: showToast('Tersimpan', 'success') / emit('toast', ...)
toast.success('Tersimpan')      // toast.error(...), toast.info(...)
```
`<Toaster />` sudah dipasang sekali di layout (DS-02). Jika komponen masih menerima/emit `toast` dari parent, ganti di kedua sisi dalam task yang sama.

**Tabs**
```vue
<Tabs v-model="activeTab">
  <TabsList><TabsTrigger value="claims">Klaim</TabsTrigger><TabsTrigger value="moderation">Moderasi</TabsTrigger></TabsList>
  <TabsContent value="claims">...</TabsContent>
</Tabs>
```

**Tabel** → `Table`, `TableHeader`, `TableRow`, `TableHead`, `TableBody`, `TableCell`. Tabel dengan sort/filter/paginasi kompleks → `Data Table` hanya bila brief memintanya.

**Avatar officer** → `<OfficerAvatar :src :name :live />`.

---

## E. Ikon
- Paket: `@lucide/vue`. Import per ikon: `import { Radio, ShieldCheck, Trash2 } from '@lucide/vue'`.
- Ukuran: `class="size-4"` (di tombol/menu), `size-5` (judul kartu).
- Padanan umum: 🔴/📡 LIVE → `Radio`; 🛡️ → `ShieldCheck`; 🔗 → `Link2`; 🗑️ → `Trash2`; ✏️ → `Pencil`; 👁️ → `Eye`; ⚙️ → `Settings`; 📋 → `ClipboardList`; 🎬 → `Clapperboard`; 👤 → `User`; 🔍 → `Search`; ⬅️/← → `ArrowLeft`; ✅ → `CheckCircle2`; ⚠️ → `TriangleAlert`; ➕ → `Plus`; ✕ → `X`.
- Tombol berisi ikon saja wajib `aria-label` (atau `<span class="sr-only">`).

---

## F. Aksen Taktis (hanya bila brief menyebut)
```vue
<TacticalFrame variant="panel" accent="primary">
  <StatTile label="Officer Live" :value="liveCount" />
</TacticalFrame>
```
Maksimal 2 per layar. Tidak pada tombol/input/tabel/badge/item list.

---

## G. Pola Halaman Standar

```vue
<template>
  <TacticalLayout>
    <PageContainer class="py-8 lg:py-12 space-y-8">
      <PageHeader eyebrow="SASP COMMAND" title="Direktori Perwira" description="...">
        <template #actions><Button variant="outline" size="sm">…</Button></template>
      </PageHeader>

      <section class="space-y-4">
        <SectionHeader title="Perwira Live" />
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <Card v-for="...">…</Card>
        </div>
      </section>
    </PageContainer>
  </TacticalLayout>
</template>
```

Status kosong → `<EmptyState>`; loading → `Skeleton` dengan bentuk konten.

---

## H. Checklist Sebelum Commit
- [ ] Tidak ada perubahan logika/API/teks (diff hanya template & import).
- [ ] `npm run lint:ui -- <file>` = 0 temuan.
- [ ] Semua modal bisa ditutup dengan Esc, fokus kembali ke pemicu.
- [ ] Tidak ada `confirm()`/`prompt()` tersisa di file.
- [ ] Tidak ada overflow horizontal di 390px.
- [ ] `npm run build` sukses, test terkait hijau.
- [ ] Dev Notes: lint sebelum→sesudah, komponen dipakai, screenshot dicek.
