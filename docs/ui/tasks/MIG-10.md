# MIG-10 — Pembersihan & penegakan

**Prioritas:** 🟡 P2 · **Bergantung:** MIG-01…MIG-09 · **Commit:** `chore(ui): MIG-10 remove legacy ui and enforce lint`

## Yang harus dilakukan
1. `npm run lint:ui -- --all --report` → harus **0 error** di seluruh `resources/js` (kecuali `Components/ui/` yang memang diabaikan). Sisa temuan → perbaiki di file terkait (commit terpisah `refactor(ui): MIG-10 fix <file>`).
2. Hapus `resources/js/Components/Base/*` setelah tidak ada import (`grep -rn "Components/Base" resources/js`).
3. Hapus kelas lama di `resources/css/app.css`: `.tactical-card`, `.tactical-card-hover`, `.tactical-badge*`, `.btn-tactical-*` (pastikan tidak dipakai: `grep -rn "tactical-card\|tactical-badge\|btn-tactical" resources/js`).
4. Hapus token lama `tactical.*`/`dept.*` hasil migrasi DS-00 bila tidak dipakai.
5. Tambahkan `npm run lint:ui -- --all` ke skrip pemeriksaan sebelum deploy (mis. `"check": "npm run lint:ui -- --all && npm run build"` di `package.json`) dan catat di Checklist Rilis.
6. Perbarui `docs/design-system/README.md` §1: hapus baris `Components/Base`.

## Selesai jika
- [ ] `lint:ui --all` = 0 error; tidak ada folder `Components/Base`, tidak ada kelas legacy.
- [ ] Build & seluruh test hijau; screenshot 7 halaman utama (390 & 1280) dilampirkan di Dev Notes.
