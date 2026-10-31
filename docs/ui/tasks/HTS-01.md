# HTS-01 — Status "Hiatus" untuk officer yang jarang live

**Prioritas:** 🟡 P2 · **Estimasi:** sedang · **Commit:** `feat(officer): HTS-01 hiatus badge and dashboard filter`
Baca dulu: `GEMINI.md`.

## Masalah
Ranking Dashboard menampilkan officer berdasarkan `monthly_duty_minutes`. Officer yang mengumpulkan banyak jam di awal bulan lalu hilang selama 2 minggu tetap di podium walau komunitas sudah tidak bisa menonton mereka. Pengunjung baru salah paham: "kenapa yang di atas malah offline?"

Platform ingin **mendukung yang rutin live** tanpa menghukum yang sedang istirahat.

## Keputusan desain (jangan diubah)
- **Tidak** mengubah `is_active` officer. Hiatus adalah **status visual yang dihitung**, bukan field database.
- **Ranking (`/ranking`) tetap menampilkan semua officer**, dengan badge "Hiatus" abu-abu di samping nama.
- **Dashboard Top 10 melompati** officer hiatus — baris 1–10 selalu diisi officer aktif, berurutan dari jam dinas tertinggi.
- **Officer yang sedang live sekarang tidak pernah dianggap hiatus**, apa pun `last_duty_at`-nya.

## Yang dibuat

### 1. Config
`config/police.php` (buat baru bila belum ada):
```php
<?php
return [
    // Officer dianggap hiatus bila last_duty_at lebih lama dari ini & tidak sedang live.
    'hiatus_days' => (int) env('POLICE_HIATUS_DAYS', 14),
];
```

### 2. Helper di model Officer
`app/Models/Officer.php`:
```php
public function getIsHiatusAttribute(): bool
{
    if ($this->relationLoaded('activeStream') ? $this->activeStream : $this->activeStream()->exists()) {
        return false;
    }
    if (!$this->last_duty_at) {
        // Officer baru / belum pernah live: hiatus bila dibuat > hiatus_days hari lalu.
        return $this->created_at && $this->created_at->diffInDays(now()) >= config('police.hiatus_days', 14);
    }
    return $this->last_duty_at->diffInDays(now()) >= config('police.hiatus_days', 14);
}
```
Tambahkan `'is_hiatus'` ke `$appends` **hanya** di payload tempat kita butuh (jangan global, karena menambah query setiap serialisasi officer).

Daripada pakai `$appends` global, pakai `->append('is_hiatus')` eksplisit di controller yang mengirim payload (lebih aman untuk performa).

### 3. Backend
- `app/Http/Controllers/PoliceCommandController.php`:
  - Di `getMonthlyLeaderboard()` builder, saat memetakan tiap officer, tambahkan `'is_hiatus' => $officer->is_hiatus` ke payload.
  - **Khusus untuk Dashboard** (field `top_streamers` + `ranks_four_to_six` + `all_top`): filter `->filter(fn($o) => !$o['is_hiatus'])` **sebelum** ambil `->take(10)`. Jadi 10 teratas tidak mengandung hiatus.
  - Untuk halaman Ranking yang memanggil `getMonthlyLeaderboard(50)` (lihat `rankings*()` method): **jangan** memfilter. Hiatus tetap tampil, cukup dengan `is_hiatus = true` di payload.
  - Payload live-stream (`/streams`, `/sync`): tambahkan `is_hiatus` juga untuk konsistensi, meski live yang `is_hiatus=false` selalu.

### 4. UI
- Komponen baru `resources/js/Components/tactical/HiatusBadge.vue`: `Badge` kecil dengan tulisan "Hiatus", `variant="outline"`, kelas `text-muted-foreground border-muted-foreground/30 bg-muted/40 font-mono text-[10px] uppercase tracking-wider`. Props `label` opsional (default "Hiatus").
- Pasang di tempat yang menampilkan officer dengan `is_hiatus = true`:
  - `resources/js/Pages/Ranking.vue`: di sebelah nama officer, di tabel desktop & kartu mobile. Avatar diberi `class="opacity-60"` saat hiatus.
  - `resources/js/Pages/OfficerDirectory.vue`: badge di kartu officer. Avatar `opacity-60`.
  - `resources/js/Pages/Dashboard.vue`: baris "Patroli per departemen" (CatalogRow) — pasang badge jika hiatus. Baris Top 10 tidak perlu badge karena sudah difilter keluar.
- Jangan menyembunyikan avatar sepenuhnya; cukup redupkan.

### 5. Teks
i18n di `locales/id.json` + `en.json`, grup `officer`:
- `hiatus_badge`: "Hiatus" / "Hiatus"
- `hiatus_tooltip`: "Tidak live > {days} hari" / "Not live for > {days} days"
Tooltip ditampilkan via `title=""` di badge.

## Test
`tests/Feature/Officer/HiatusStatusTest.php`:
1. Officer tanpa `last_duty_at` tapi `created_at` 1 hari lalu → `is_hiatus = false`.
2. Officer `last_duty_at` 20 hari lalu & tidak live → `is_hiatus = true`.
3. Officer `last_duty_at` 20 hari lalu **tapi sedang live** → `is_hiatus = false`.
4. Officer `last_duty_at` 3 hari lalu → `is_hiatus = false`.
5. `GET /` (Dashboard) → `monthlyLeaderboard.all_top` tidak memuat officer hiatus; `GET /ranking` → tetap memuat, dengan `is_hiatus=true`.
6. Ubah `config(['police.hiatus_days' => 7])` → test ulang #2 dengan `last_duty_at` 10 hari lalu → true.

## Jangan
- Jangan mengubah `is_active` atau menambahkan kolom database baru. Hiatus murni dihitung.
- Jangan menyembunyikan officer hiatus dari direktori atau pencarian; hanya dari Top 10 Dashboard.
- Jangan menambahkan polling baru untuk hiatus. Dihitung on-demand saat payload dibangun.
- Commit hanya file yang terkait task.

## Cek
- `npm run lint:ui -- <file .vue yang diubah>` → 0 error; `npm run build` lulus.
- Seluruh `php artisan test` hijau.

## Selesai jika
- [ ] Dashboard Top 10 tidak memuat officer hiatus; posisi 1–10 selalu terisi officer aktif.
- [ ] Halaman Ranking tetap menampilkan semua officer dengan badge "Hiatus" + avatar redup.
- [ ] Direktori perwira menandai officer hiatus dengan badge yang sama.
- [ ] Ambang hari dibaca dari `config/police.php` (bisa diubah lewat env).
- [ ] Baris HTS-01 di `docs/ui/TASKS.md` diisi 🟣 + Dev Notes.
