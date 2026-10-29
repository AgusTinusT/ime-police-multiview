# MIG-06 — Halaman Bio-Link & Profil Officer

**Prioritas:** 🟡 P2 · **Bergantung:** MIG-01, P-01 · **Resep:** `GEMINI-UI-PLAYBOOK.md`
**Commit dipecah:** `refactor(ui): MIG-06a public bio page` → `refactor(ui): MIG-06b officer dashboards and claim modal`

## File
- 06a: `Pages/Public/BioLink.vue`
- 06b: `Pages/Officer/BioLinksDashboard.vue`, `Pages/Officer/OfficerProfile.vue`, `Components/OfficerClaimModal.vue`

## Catatan khusus
- **Halaman publik `/@slug` punya tema per officer** (`theme_preset`, `accent_color`, `button_style`). Pertahankan: aksen per profil dipasang sebagai CSS variable lokal di wrapper (`:style="{ '--primary': profile.accent_color }"`) sehingga `Button`/`Badge` otomatis mengikuti. Preset tema di `config/biolinks.php` tidak diubah.
- Perbaiki temuan aksesibilitas Review #5: tidak ada teks < 12px; tombol "Bagikan", "← SASP Command", link "Bio" minimal 40px tinggi.
- Kartu link → `Button variant="outline"` lebar penuh (`as-child` + `<a>` ke `/go/{id}`, `rel` & `target` dipertahankan); link donasi unggulan → `TacticalFrame variant="panel"` (**maks. 1**).
- Rekan patroli → `Card` + `OfficerAvatar` + `LiveBadge`.
- Dashboard officer: form → komponen form shadcn; reorder drag-drop tetap; modal password → `Dialog`; konfirmasi hapus → `AlertDialog`.
- Profil Dinas: 4 kelompok → 4 `Card`; field terkunci → `Input disabled` + ikon `Lock`.
- Modal klaim → `Dialog` + `Tabs` (YouTube / Manual).

## Selesai jika
- [ ] File lolos `lint:ui`.
- [ ] Ukur ulang `/@slug` di 360px: LCP & CLS tidak memburuk dibanding Review #5 (2,05 s / 0,011).
- [ ] Test `--filter=BioLink` hijau; build sukses.
