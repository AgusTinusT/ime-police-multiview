# MIG-05 — Clips, Clipper Studio, Pricing

**Prioritas:** 🟡 P2 · **Bergantung:** MIG-01 · **Resep:** `GEMINI-UI-PLAYBOOK.md`
**Commit dipecah:** `refactor(ui): MIG-05a clips gallery` → `refactor(ui): MIG-05b clipper studio` → `refactor(ui): MIG-05c pricing and guide`

## File
- 05a: `Pages/Clips.vue`, `Components/CommunityClipsTab.vue`, `Components/CreateActionClipModal.vue`
- 05b: `Pages/Clipper.vue` (3.148 baris — per bagian: header/kuota → input URL → trimmer & preview → daftar hasil → modal), `Components/VideoClipperModal.vue`, `Components/DualRangeTrimmer.vue`, `Components/TacticalClipperBetaBanner.vue`
- 05c: `Pages/ClipperPricing.vue`, `Pages/PoliceClipperGuide.vue`, `Components/ClipperClaimModal.vue`

## Catatan khusus
- **Jangan** mengubah logika trim/encode, polling status, upload, kuota, atau `DualRangeTrimmer` (hanya warna track/handle → `bg-primary`, `bg-muted`).
- Status job (queued/processing/done/failed) → `Badge` (`secondary`/`info`/`success`/`destructive`) + `Progress`.
- Kartu paket pricing → `Card`; paket unggulan boleh **aksen taktis** `TacticalFrame variant="panel" accent="primary"` (1 saja).
- Beta banner → `Alert`.
- Modal → `Dialog`; konfirmasi hapus klip → `AlertDialog`.

## Selesai jika
- [ ] File lolos `lint:ui`.
- [ ] Uji manual: buat klip dari URL, trim, unduh, hapus; halaman pricing & klaim officer dari kartu Officer — berfungsi sama.
- [ ] Build & test hijau (`--filter=VideoClip` + BioLink).
