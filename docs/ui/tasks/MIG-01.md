# MIG-01 — Shell aplikasi: layout, header, navigasi, footer

**Prioritas:** 🟠 P1 · **Bergantung:** DS-03 · **Resep:** `docs/design-system/GEMINI-UI-PLAYBOOK.md` (wajib dibaca)
**Commit:** `refactor(ui): MIG-01 app shell and navigation`

## File
`Layouts/TacticalLayout.vue`, `Layouts/AuthenticatedLayout.vue`, `Layouts/GuestLayout.vue`, `Components/TacticalDashboardHeader.vue`, `Components/TacticalMobileBottomNav.vue`, `Components/TacticalFooter.vue`, `Components/UserAccountMenu.vue`, `Components/AnnouncementBanner.vue`, `Components/NavLink.vue`, `Components/ResponsiveNavLink.vue`, `Components/Dropdown.vue`, `Components/DropdownLink.vue`

## Catatan khusus
- `UserAccountMenu` → `DropdownMenu` (trigger `OfficerAvatar`/`Avatar`), item memakai ikon lucide; badge tier/role → `Badge`.
- Bottom nav HP: tinggi item ≥ 48px, ikon lucide + label `text-xs`, item aktif `text-primary`.
- `AnnouncementBanner` → `Alert` (variant sesuai tipe: info/promo/poll → `info`/`default`/`warning` via kelas token).
- `Dropdown.vue`/`DropdownLink.vue` (bawaan Breeze): ganti pemakaiannya dengan `DropdownMenu`; bila tidak ada pemakai lagi, hapus filenya.
- Header: logo + nav + aksi; jangan ubah item menu/route.
- Aksen taktis: **tidak ada** di shell.

## Selesai jika
- [ ] Semua file di atas lolos `npm run lint:ui -- <file>`.
- [ ] Navigasi desktop & HP (390px) berfungsi; menu akun bisa dibuka dengan keyboard.
- [ ] Build & test hijau; Dev Notes berisi lint sebelum→sesudah.
