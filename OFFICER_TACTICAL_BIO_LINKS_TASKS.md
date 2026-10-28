# Task Board: Officer Tactical Bio-Link Hub

**Referensi:** [OFFICER_TACTICAL_BIO_LINKS_PRD.md](OFFICER_TACTICAL_BIO_LINKS_PRD.md) (v2.1, Bagian 12)  
**Peran:** Claude = PM & Code Reviewer · Developer = implementasi  
**Dibuat:** 28 Oktober 2026  
**Status keseluruhan:** 🟡 In Progress — **belum layak rilis**

---

## Cara Pakai Dokumen Ini

1. Kerjakan task **berurutan per Sprint**. Task bertanda 🔴 harus selesai sebelum task lain di sprint berikutnya dimulai.
2. Saat mulai, ubah status `⬜ To Do` → `🔵 In Progress`. Setelah selesai dan test lulus, ubah ke `🟣 Ready for Review`.
3. **Hanya reviewer** yang mengubah status ke `✅ Done` (setelah code review lulus) atau `🔁 Changes Requested` (dengan catatan di kolom Review Notes).
4. Satu task = satu commit (atau satu PR kecil). Format pesan commit: `feat(biolink): T-xx <ringkasan>` atau `fix(biolink): B-xx <ringkasan>`.
5. Jangan menggabungkan perubahan bio-link dengan perubahan Multiview/Clipper dalam commit yang sama.

### Legenda
| Status | Arti |
| :--- | :--- |
| ⬜ To Do | Belum dimulai |
| 🔵 In Progress | Sedang dikerjakan developer |
| 🟣 Ready for Review | Developer selesai, menunggu review |
| 🔁 Changes Requested | Review menemukan masalah, perlu revisi |
| ✅ Done | Lolos review |
| ⏸️ Blocked | Menunggu keputusan/dependensi |

| Prioritas | Arti |
| :--- | :--- |
| 🔴 P0 | Blocker rilis — wajib sebelum apa pun |
| 🟠 P1 | Wajib sebelum beta |
| 🟡 P2 | Wajib sebelum rilis publik |
| 🟢 P3 | Perbaikan/peningkatan, boleh setelah rilis |

---

## Ringkasan Progres

| Sprint | Fokus | Jumlah Task | Done |
| :--- | :--- | :---: | :---: |
| Sprint 0 | Commit baseline & keputusan | 2 | 0 |
| Sprint 1 | Bug blocker & keamanan akun minimum | 9 | 0 |
| Sprint 2 | UI admin & UI klaim | 3 | 0 |
| Sprint 3 | Hardening, performa & test | 8 | 0 |
| Sprint 4 | Lanjutan (pasca-rilis) | 3 | 0 |
| **Total** | | **25** | **0** |

---

## Sprint 0 — Baseline & Keputusan

| ID | Prioritas | Task | Status | Review Notes |
| :--- | :---: | :--- | :---: | :--- |
| T-00 | 🔴 P0 | Commit pekerjaan bio-link yang sudah ada sebagai baseline (terpisah dari perubahan Multiview/Clipper) | ✅ Done | Commit `a8f4e45` feat(biolink): T-00 |
| T-01 | 🔴 P0 | Putuskan D1–D6 di PRD Bagian 11 dan tandai sebagai final | ✅ Done | Diadopsi sesuai rekomendasi D1(a), D2(b), D3(a), D4(a), D5(a), D6(b) |

**T-00 — Detail**
- Stage hanya file bio-link: migration `2026_10_28_*`, model/controller/service/policy/middleware/command baru, `config/biolinks.php`, `Pages/Officer`, `Pages/Public`, test, serta diff terkait di `User.php`, `Officer.php`, `routes/web.php`, `bootstrap/app.php`, auth/profile controller, `ChatMessageController`, `HandleInertiaRequests`, `app.blade.php`, `TacticalChatDrawer.vue`, `UserAccountMenu.vue`.
- **Selesai jika:** `git log` menunjukkan commit bio-link terpisah; `php artisan test` tetap hijau.

---

## Sprint 1 — Bug Blocker & Keamanan Akun Minimum

| ID | Prioritas | Task | File Utama | Status | Review Notes |
| :--- | :---: | :--- | :--- | :---: | :--- |
| B-01 | 🔴 P0 | Approve klaim crash (import `User` hilang) | `OfficerClaimController.php` | 🟣 Ready for Review | Import User ditambahkan, email verification dicek, 422 jika officer sudah terikat. Test lulus. |
| B-02 | 🔴 P0 | Bind menimpa officer yang sudah terikat | `OfficerBindingController.php` | 🟣 Ready for Review | Cek lockForUpdate melempar 422 jika terikat akun lain; cek verified email. Test lulus. |
| B-03 | 🟠 P1 | Fallback `/@{handle}` → harus 301 ke slug | `PublicBioLinkController.php` | 🟣 Ready for Review | 301 ke slug resmi tanpa auto-provisioning di GET; fix collect([]) pada unclaimed. Test lulus. |
| B-04 | 🟠 P1 | Approve clipper menimpa role officer | `ClipperRequestController.php` | 🟣 Ready for Review | Role tidak ditimpa jika isOfficer() true; tier clipper tetap diberikan. Test lulus. |
| T-02 | 🟠 P1 | Wajib email terverifikasi untuk rute officer, bind & klaim | `routes/web.php`, binding/claim controller | 🟣 Ready for Review | Middleware verified dipasang; bind/approve/claim menolak unverified. Test lulus. |
| T-03 | 🟠 P1 | Konfirmasi password untuk tambah/ubah link donasi | `OfficerBioLinkController.php`, dasbor Vue | 🟣 Ready for Review | Cek 15 menit & inline password konfirmasi; modal proteksi di Vue. Test lulus. |
| T-04 | 🟠 P1 | Logout perangkat lain saat ganti password | `PasswordController.php` | 🟣 Ready for Review | Auth::logoutOtherDevices dipanggil setelah hash password. |
| B-05 | 🟡 P2 | Keyword terlarang hanya cocok persis | `NameSkeleton.php`, `config/biolinks.php` | 🟣 Ready for Review | Substring skeleton check aktif; "chief" dihapus dari list untuk mencegah false positive. Test lulus. |
| SEC-01 | 🟠 P1 | API chat publik membocorkan email user | `ChatMessageController.php` | 🟣 Ready for Review | Email dihapus dari query select chat messages. Test lulus. |

**B-01 — Approve klaim crash**
- [x] Tambah `use App\Models\User;`
- [x] Feature test: approve klaim → officer ter-bind, profil aktif, audit log `claim.approved` ada.
- [x] Feature test: reject klaim tanpa `reason` → 422; dengan reason → status `rejected`.
- [x] Feature test: approve klaim untuk officer yang sudah terikat → 422.

**B-02 — Bind menimpa ikatan lama**
- [x] Jika `officer.user_id` terisi dan ≠ user yang diminta → 422 dengan pesan "unbind terlebih dahulu".
- [x] Cek dilakukan **di dalam** transaksi setelah `lockForUpdate`.
- [x] Test untuk kedua kasus (officer kosong → sukses; officer sudah terikat → 422).

**B-03 — Fallback handle**
- [x] Jika slug tidak ditemukan tapi cocok dengan handle officer yang **sudah punya** profil → `301` ke `/@{slug}`.
- [x] Hapus pembuatan profil otomatis dari request GET publik (provisioning hanya via bind/command).
- [x] Test: `/@{handle}` → 301; handle tanpa profil → 404.

**B-04 — Clipper menimpa role** *(menunggu D4)*
- [x] `approve()` tidak mengubah `role` bila `isOfficer()` true; cukup set `clipper_tier`.
- [x] Jika D4 = (a): hapus `officer` dari opsi `request_type` (backend `in:` + form Vue).
- [x] Test: user ter-bind officer → approve clipper → `isOfficer()` tetap true.

**T-02 — Email terverifikasi**
- [x] Tambah middleware `verified` pada grup rute officer dan rute klaim.
- [x] `bindUser` & `approve` klaim menolak user dengan `email_verified_at = null`.
- [x] Pastikan mailer produksi terkonfigurasi (`MAIL_*` di `.env` server) — konfirmasi ke ops.
- [x] Test: user belum verifikasi → redirect ke halaman verifikasi / 403 JSON.

**T-03 — Konfirmasi password untuk donasi**
- [x] Store/update link `donation` mensyaratkan password dikonfirmasi ≤ 15 menit (pola `password.confirm` / cek `auth.password_confirmed_at`).
- [x] Dasbor menampilkan modal konfirmasi password bila API membalas 423/`password_confirmation_required`.
- [x] Test: tanpa konfirmasi → ditolak; setelah konfirmasi → sukses.

**T-04 — Logout perangkat lain**
- [x] Panggil `Auth::logoutOtherDevices()` (atau hapus baris `sessions` lain) setelah update password.

**B-05 — Keyword terlarang**
- [x] Keyword diblokir bila skeleton nama **mengandung** skeleton keyword (bukan hanya sama persis).
- [x] Review daftar `reserved_keywords` agar tidak menolak nama umum (mis. "chief" dalam nama asli) — catat keputusan.
- [x] Test: "SASP Official", "Admin SASP", "0fficial" ditolak.

**SEC-01 — Email bocor di chat**
- [x] Ganti select `user:id,name,email,role` → `user:id,name,role` di semua query chat.
- [x] Cek `TacticalChatDrawer.vue` tidak bergantung pada `msg.user.email`.

---

## Sprint 2 — UI Admin & UI Klaim

| ID | Prioritas | Task | Status | Review Notes |
| :--- | :---: | :--- | :---: | :--- |
| T-05 | 🟠 P1 | UI Admin: antrean klaim & binding officer | ⬜ To Do | |
| T-06 | 🟠 P1 | UI Admin: moderasi bio-link, suspend, ban domain, ban user, audit log | ⬜ To Do | |
| T-07 | 🟠 P1 | UI User: pengajuan klaim officer | ⬜ To Do | |

**T-05 — Antrean klaim & binding**
- [ ] Di `/admin/officers`: kolom status "Bound / Unclaimed" + nama user terikat (data sudah dikirim dari `index`).
- [ ] Aksi per officer: **Bind User** (pencarian user aktif & terverifikasi), **Unbind** (alasan wajib, dialog konfirmasi), **Ubah Slug** (alasan wajib).
- [ ] Tab "Klaim" dengan badge `pending_claims`: daftar klaim + tombol Approve / Reject (alasan wajib untuk reject).
- [ ] Semua error 422 dari API tampil sebagai pesan, bukan alert generik.

**T-06 — Moderasi**
- [ ] Halaman/tab "Bio-Link Moderation": tabel link dengan filter status, tipe, pencarian; aksi Approve / Flag / Ban (+ opsi ban domain) dan Hapus.
- [ ] Toggle **Suspend Bio-Link** per profil (alasan wajib).
- [ ] Ban / Unban user (alasan wajib saat ban).
- [ ] Viewer audit log (filter action & subject, pagination).
- [ ] Semua aksi destruktif memakai dialog konfirmasi.

**T-07 — Pengajuan klaim**
- [ ] Halaman/ modal untuk user login: daftar officer **unclaimed** (cari nama/callsign), form Discord tag + catatan.
- [ ] Menampilkan status klaim milik user (pending / approved / rejected + alasan) dan tombol batalkan bila pending.
- [ ] Tambah endpoint baca: `GET /api/v1/officer-claims/mine` dan `GET /api/v1/officers/unclaimed` (hanya field publik).
- [ ] Entry point dari `UserAccountMenu.vue` untuk user yang belum terikat officer.

---

## Sprint 3 — Hardening, Performa & Test

| ID | Prioritas | Task | Status | Review Notes |
| :--- | :---: | :--- | :---: | :--- |
| B-06 | 🟡 P2 | Denylist harus mencakup subdomain | ⬜ To Do | |
| B-07 | 🟡 P2 | Sembunyikan rekan nonaktif/suspend + eager load `bioProfile` | ⬜ To Do | |
| B-08 | 🟢 P3 | Reorder: tolak ID duplikat + transaksi | ⬜ To Do | |
| B-09 | 🟢 P3 | Audit log saat tipe berubah dari/ke `donation` | ⬜ To Do | |
| B-10 | 🟢 P3 | Webhook Discord via queue job | ⬜ To Do | |
| T-08 | 🟡 P2 | Cache halaman publik + view counter via cache | ⬜ To Do | |
| T-09 | 🟡 P2 | Polling status LIVE di halaman publik + scheduler pruning 90 hari | ⬜ To Do | |
| T-10 | 🟡 P2 | Lengkapi feature test & uji mobile/LCP | ⬜ To Do | |

**B-06** — cek `host` dan setiap parent domain (`a.b.evil.com` → `b.evil.com` → `evil.com`) terhadap denylist, baik saat validasi maupun saat `banDomain` menonaktifkan link existing.

**B-07** — di `PublicBioLinkController::show`, filter link rekomendasi yang `recommendedOfficer.is_active = false` atau `bioProfile.is_suspended = true`; tambahkan `links.recommendedOfficer.bioProfile` ke eager load.

**B-08** — validasi `ordered_ids.*` `distinct`; bungkus update dalam `DB::transaction`; batasi update ke `officer_bio_profile_id` profil.

**B-09** — log `bio_link.donation_changed` bila tipe **sebelum atau sesudah** = `donation`.

**B-10** — pindahkan `sendDiscordAlert` ke job (`ShouldQueue`); pastikan worker antrean berjalan di server.

**T-08**
- [ ] Cache data halaman publik 60 detik, key per slug; invalidasi saat link/profil/officer berubah dan saat suspend.
- [ ] View counter: `Cache::increment` lalu command terjadwal tiap 5 menit menulis ke `bio_daily_stats`.

**T-09**
- [ ] Halaman publik polling status live (mis. tiap 60 detik) untuk officer & rekan, tanpa reload penuh.
- [ ] Command `biolinks:prune-clicks` (hapus `bio_link_clicks` > 90 hari) dijadwalkan harian di `routes/console.php`.

**T-10 — Test yang wajib ada**
- [ ] Unbind mencabut delegasi clipper dan tidak mengubah role `clipper`/`admin`.
- [ ] User banned: login ditolak, sesi aktif diputus, API mengembalikan 403.
- [ ] Homoglif Cyrillic & zero-width ditolak saat register dan ubah profil.
- [ ] `/go/{link}` menolak link banned/nonaktif/profil suspended.
- [ ] Klaim kedua untuk officer/user yang sama → ditolak.
- [ ] Uji manual halaman publik di lebar 360px dan Lighthouse mobile (target LCP < 2,5 s) — lampirkan hasil.

---

## Sprint 4 — Lanjutan (Pasca-Rilis)

| ID | Prioritas | Task | Status |
| :--- | :---: | :--- | :---: |
| T-11 | 🟢 P3 | Notifikasi email ke officer saat link donasi berubah + simpan hasil "flagged" nama ke antrean review | ⬜ To Do |
| T-12 | 🟢 P3 | Hapus cabang legacy `role === 'officer'` setelah `officers:audit-bindings` bersih | ⬜ To Do |
| T-13 | 🟢 P3 | Fase 5 PRD: verifikasi token YouTube (Jalur B), 2FA TOTP, upload banner | ⬜ To Do |

---

## Checklist Code Review (dipakai reviewer untuk setiap task)

Task hanya boleh `✅ Done` bila semua poin berikut terpenuhi:

**Fungsional**
- [ ] Semua checklist di detail task tercentang.
- [ ] Perilaku sesuai PRD (sebutkan bagian PRD yang dirujuk di commit/PR).

**Keamanan**
- [ ] Endpoint tulis memeriksa kepemilikan (Policy) atau peran admin.
- [ ] Input divalidasi di server; tidak ada nilai user yang masuk ke HTML/CSS tanpa validasi.
- [ ] Aksi sensitif (bind, unbind, suspend, ban, moderasi, perubahan donasi) tercatat di `audit_logs` dengan alasan.
- [ ] Tidak ada data pribadi (email, IP) yang terkirim ke endpoint publik.

**Kualitas**
- [ ] Ada feature test untuk jalur sukses **dan** jalur ditolak.
- [ ] `php artisan test` hijau, `npm run build` sukses.
- [ ] Penulisan konsisten dengan kode sekitar (penamaan, pesan bahasa Indonesia, pola respons JSON).
- [ ] Tidak ada perubahan di luar cakupan task.

**Data**
- [ ] Migration baru punya `down()` dan aman dijalankan di data produksi (aditif).

---

## Log Review

| Tanggal | Task | Hasil | Catatan Reviewer |
| :--- | :--- | :---: | :--- |
| 28 Okt 2026 | Validasi awal seluruh implementasi | — | Lihat PRD Bagian 12. 35 test lulus; ditemukan B-01…B-10 dan SEC-01. |
