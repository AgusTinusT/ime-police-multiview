# MIG-04 — Direktori Perwira

**Prioritas:** 🟡 P2 · **Bergantung:** MIG-01 · **Resep:** `GEMINI-UI-PLAYBOOK.md`
**Commit dipecah:** `refactor(ui): MIG-04a directory list and filters` → `refactor(ui): MIG-04b officer dossier`

## File
- 04a: `Pages/OfficerDirectory.vue` (1.868 baris — kerjakan per bagian: header & filter → grid/list → modal)
- 04b: `Components/OfficerDossierTab.vue`, `Components/CreateOfficerImpressionModal.vue`

## Catatan khusus
- `PageHeader eyebrow="SAN ANDREAS DIRECTORY"` + **aksen taktis** `TacticalFrame variant="tag"` di eyebrow (sudah bawaan PageHeader).
- Filter departemen → `Tabs` atau `ToggleGroup`-like dengan `Button variant="outline"`; pencarian → `Input` dengan ikon `Search`.
- Kartu officer → `Card` + `OfficerAvatar` + `DeptBadge` + rank `Badge variant="secondary"` + `LiveBadge` bila live; link bio `/@slug` → `Button variant="link"`.
- Dossier → `Sheet side="right"` (desktop) / `side="bottom"` (HP) bila sebelumnya panel geser; isi memakai `Card`, `Separator`, `Table` untuk sertifikasi.
- Modal impression → `Dialog` + form shadcn.

## Selesai jika
- [ ] File lolos `lint:ui`; filter, pencarian, dossier, modal berfungsi sama.
- [ ] Build & test hijau.
