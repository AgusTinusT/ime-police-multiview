# SQD-01 — Edit squad dari kartu di Dashboard

**Prioritas:** 🟠 P1 · **Estimasi:** kecil · **Commit:** `feat(squad): SQD-01 edit from dashboard card`
Baca dulu: `GEMINI.md`.

## Masalah
Officer sudah membuat squad "SWAT77" berisi 1 anggota. Besoknya ingin menambah anggota. Tidak ada opsi di dashboard — satu-satunya jalan adalah membuka Multiview dengan `?squad=slug` dulu, baru `SquadMenu` menampilkan tombol "Ubah Squad". User mencari dari dashboard dan merasa fiturnya tidak ada.

## Yang dibuat

### 1. Tombol Edit di kartu squad milik user
Di `resources/js/Components/Squad/SquadCatalogCard.vue`:
- Setelah tombol Fork di pojok kanan atas, tambahkan tombol **Edit** (ikon `Pencil` dari `@lucide/vue`) yang **hanya tampil bila `squad.is_owner === true`**. Letaknya di sebelah kiri tombol Fork dengan jarak 4px, gaya tombol sama (`size="icon-sm"`, `variant="secondary"`, lingkaran, latar `bg-background/70`).
- `aria-label` dan `title`: `$t('squad.edit_button')` (tambahkan key: id = "Ubah squad", en = "Edit squad").
- Pada klik: `@click.prevent.stop="emit('edit', squad)"`. Tambahkan `edit` ke `defineEmits`.

### 2. Dashboard menangani edit
Di `resources/js/Pages/Dashboard.vue` (temukan `<SquadCatalogCard`):
- Pastikan API `/api/v1/squads/public` juga mengirim squad milik user, atau tambahkan API `/api/v1/squads/mine` yang sudah ada untuk daftar milik user. **Cek dulu** apa yang sudah ditampilkan di dashboard: kalau baris "Squad komunitas" sekarang hanya menampilkan squad orang lain, tambahkan baris baru **"Squad saya"** di atas yang memakai `/api/v1/squads/mine`.
- Pasang handler `@edit="openEditSquad"`:
  ```js
  const editingSquad = ref(null);
  const showSquadDialog = ref(false);
  const openEditSquad = (squad) => {
      editingSquad.value = squad;
      showSquadDialog.value = true;
  };
  const onSquadSaved = (updated) => {
      // Perbarui kartu yang bersangkutan di state lokal
      const list = mySquads.value;
      const idx = list.findIndex((s) => s.id === updated.id);
      if (idx !== -1) list[idx] = updated;
      showSquadDialog.value = false;
      editingSquad.value = null;
  };
  ```
- Pasang `<SquadSaveDialog v-model:open="showSquadDialog" :squad="editingSquad" @saved="onSquadSaved" />` di bawah template.
- Jangan menambah polling baru. Setelah save, perbarui state lokal dari respons `@saved`.

### 3. Teks
`locales/id.json` + `en.json`: grup `squad`, key `edit_button` ("Ubah squad" / "Edit squad").

## Jangan
- Jangan mengubah `SquadSaveDialog.vue` — fitur edit sudah berjalan di sana (lihat `isEditing` dan `handleSave` yang memanggil `axios.put`).
- Jangan mengubah skema database/API squads.
- Commit hanya file yang terkait task.

## Cek
- `npm run lint:ui -- <file .vue yang diubah>` → 0 error; `npm run build` lulus.
- Manual: buat squad baru dengan 1 officer dari Multiview, buka Dashboard, klik tombol Edit di kartu squad, tambah 1 officer, Simpan — kartu menampilkan hitungan baru tanpa reload.

## Selesai jika
- [ ] Tombol Edit tampil hanya di kartu squad milik user.
- [ ] Dialog edit terbuka dengan data squad terisi.
- [ ] Setelah simpan, kartu di dashboard langsung ikut berubah.
- [ ] Baris SQD-01 di `docs/ui/TASKS.md` diisi 🟣 + Dev Notes.
