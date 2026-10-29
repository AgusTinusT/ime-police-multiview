# MIG-03 — Multiview (kontrol, player, drawer)

**Prioritas:** 🟠 P1 · **Bergantung:** MIG-01 · **Resep:** `GEMINI-UI-PLAYBOOK.md`
**Commit dipecah:** `refactor(ui): MIG-03a multiview header, toolbar, picker` → `refactor(ui): MIG-03b players and drawers`

## File
- 03a: `Pages/Multiview.vue`, `Components/MultiviewHeader.vue`, `Components/MultiviewStreamPicker.vue`, `Components/MultiviewLandscapeOverlay.vue`, `Components/TacticalDockControlBar.vue`, `Components/TacChannelToolbar.vue`, `Components/TacChannelNotificationToast.vue`
- 03b: `Components/FocusModePlayer.vue` (1.258 baris — kerjakan per bagian), `Components/CustomVideoPlayer.vue`, `Components/TacticalChatDrawer.vue`, `Components/Drawers/QuickAddDrawer.vue`, `Components/QuickFeedDrawer.vue`, `Components/OfficerVisibilityBottomSheet.vue`

## Catatan khusus (risiko tinggi — fungsi player tidak boleh berubah)
- **Jangan** mengubah logika YouTube iframe/player, event, timer, layout grid slot, atau keyboard shortcut.
- Overlay di atas video boleh tetap gelap semi-transparan: pakai `bg-background/70`, `text-foreground`.
- Drawer/bottom-sheet → `Sheet` (`side="right"` desktop, `side="bottom"` HP) **hanya bila** perilaku buka/tutup bisa dipertahankan; jika `Sheet` mengganggu (mis. memaksa fokus dari player), pertahankan elemen lama dan cukup ganti kelas ke token — catat di Dev Notes.
- `TacChannelNotificationToast` → `toast()` Sonner bila perilakunya setara (durasi, aksi), jika tidak ganti kelas saja.
- Tombol ikon di dock bar wajib `aria-label` + `Tooltip`.
- Aksen taktis: **tidak ada** (area video harus bersih).

## Selesai jika
- [ ] Semua file lolos `lint:ui`.
- [ ] Uji manual: tambah/hapus stream ke slot, mode fokus, chat drawer, TAC channel, layar landscape HP — semua berfungsi seperti sebelumnya (catat di Dev Notes).
- [ ] Build & test hijau.
