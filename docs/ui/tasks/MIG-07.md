# MIG-07 — Panel Admin

**Prioritas:** 🟡 P2 · **Bergantung:** MIG-01 · **Resep:** `GEMINI-UI-PLAYBOOK.md`
**Commit dipecah (satu commit per baris):**

| Sub | File | Isi |
| :--- | :--- | :--- |
| 07a | `Pages/Admin/OfficerManagement.vue` — kerangka | Layout admin: `PageHeader`, `StatTile` ringkasan (**aksen taktis** `TacticalFrame` maks. 2), navigasi tab → `Tabs` (atau `Sidebar` bila tab > 8, catat pilihan) |
| 07b | `OfficerManagement.vue` — tab Officers | Tabel officer → `Table`; filter → `Input`/`Select`; aksi baris → `DropdownMenu` (Edit, Bind, Unbind, Ubah Slug, Suspend, Ban); form tambah/ubah → `Dialog` + form shadcn |
| 07c | `OfficerManagement.vue` — tab Agencies, Certifications, Announcements, Users | Sama polanya; ganti semua `confirm()`/`prompt()` dengan `AlertDialog`/`Dialog` + alasan |
| 07d | `Components/Admin/OfficerClaimsTab.vue`, `Components/Admin/OfficerBindingModal.vue`, `Components/Admin/AdminChangeRequestsTab.vue` | `Table` + `Badge` status + `Dialog` alasan |
| 07e | `Components/Admin/BioModerationTab.vue` (+ `HeroSlidesTab`/`HeroSlideForm` bila belum memakai kit) | Moderasi link, ban domain, suspend, audit log → `Table`, `Pagination`, `Dialog` alasan |

## Catatan khusus
- File 3.742 baris: kerjakan **berurutan** 07a→07e, setiap commit harus tetap jalan.
- Toast admin (`showToast`) → `toast()` Sonner; hapus implementasi toast lama setelah tidak dipakai.
- Jangan ubah endpoint, payload, atau urutan kolom tabel.

## Selesai jika
- [ ] Semua file lolos `lint:ui`; tidak ada `confirm/prompt/alert` tersisa.
- [ ] Uji manual alur admin: CRUD officer, bind/unbind, klaim approve/reject, moderasi link, suspend, ban, pengajuan rank, hero slides.
- [ ] Test `--filter=BioLink` & `--filter=Hero` hijau; build sukses.
