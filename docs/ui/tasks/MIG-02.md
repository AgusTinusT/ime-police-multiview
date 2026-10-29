# MIG-02 — Dashboard & kartu stream

**Prioritas:** 🟠 P1 · **Bergantung:** MIG-01, HS-03 · **Resep:** `GEMINI-UI-PLAYBOOK.md`
**Commit dipecah:** `refactor(ui): MIG-02a dashboard page` → `refactor(ui): MIG-02b stream cards and leaderboard`

## File
- 02a: `Pages/Dashboard.vue`
- 02b: `Components/TacticalStreamGrid.vue`, `Components/TacticalStreamCard.vue`, `Components/TacticalStreamSlimles.vue`, `Components/TacticalSubSupportGrid.vue`, `Components/TacticalMonthlyLeaderboard.vue`

## Catatan khusus
- Hero sudah memakai `HeroCarousel` (HS-03); fallback hero statis ikut dimigrasi ke token.
- Kartu stream → `Card` + `AspectRatio` (16/9) untuk thumbnail + `LiveBadge` + `OfficerAvatar` + `DeptBadge`. Hover: `hover:border-input` saja, tanpa scale besar.
- Leaderboard → `Table` (desktop) / list kartu (HP); peringkat 1–3 boleh aksen warna `text-warning`.
- **Aksen taktis:** `TacticalFrame variant="panel"` untuk 1 baris `StatTile` ringkasan (jumlah live, total penonton) — maksimal 2 frame di layar.
- Section header katalog per departemen → `SectionHeader` + `DeptBadge`.

## Selesai jika
- [ ] File lolos `lint:ui`; tidak ada overflow di 390px.
- [ ] Lighthouse mobile dashboard dicatat (Performance, LCP, CLS, Accessibility).
- [ ] Build & test hijau.
