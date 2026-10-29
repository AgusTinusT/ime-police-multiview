# MIG-09 — Halaman konten (About, FAQ, Feedback, Updates)

**Prioritas:** 🟢 P3 · **Bergantung:** MIG-01 · **Resep:** `GEMINI-UI-PLAYBOOK.md`
**Commit:** `refactor(ui): MIG-09 content pages`

## File
`Pages/About.vue`, `Pages/Faq.vue`, `Pages/Feedback.vue`, `Pages/Updates.vue`

## Catatan khusus
- FAQ → `Accordion` (`npx shadcn-vue@latest add accordion` bila belum ada).
- Changelog/Updates → kartu per versi (`Card`) + `Badge` tipe (fitur/perbaikan) + `Separator`.
- Feedback form → form shadcn + `toast()`; jangan ubah endpoint/throttle.
- About → `PageHeader` + section `Card`; **aksen taktis** `TacticalFrame variant="panel"` maks. 1 (mis. kartu misi).
- Teks panjang: lebar baca `max-w-prose`.

## Selesai jika
- [ ] File lolos `lint:ui`; build & test hijau.
