# Task Board: Officer Tactical Bio-Link Hub

**Referensi:** [OFFICER_TACTICAL_BIO_LINKS_PRD.md](OFFICER_TACTICAL_BIO_LINKS_PRD.md) (v2.1, Bagian 12)  
**Peran:** Claude = PM & Code Reviewer · Developer = implementasi  
**Dibuat:** 28 Oktober 2026 · **Review terakhir:** 28 Oktober 2026 (Review #3, commit `9638c49`…`152a41d`)  
**Status keseluruhan:** 🟡 Hampir siap beta — kode Sprint 1–5 lolos review, **tetapi** sebagian perubahan yang sudah ✅ belum di-commit (C-01, 🔴) dan uji Lighthouse (T-10) belum dilakukan.

---

## Cara Pakai Dokumen Ini

> ⚡ **Untuk agen coding (Gemini dll.):** jangan baca dokumen ini atau PRD secara utuh. Baca `GEMINI.md` + brief task di [`docs/biolink/tasks/`](docs/biolink/tasks/README.md) saja. Dokumen ini dipakai PM/reviewer untuk status; agen hanya mengubah baris task-nya sendiri.

1. Kerjakan task **berurutan per Sprint**. Task bertanda 🔴 harus selesai sebelum task lain di sprint berikutnya dimulai.
2. Saat mulai, ubah status `⬜ To Do` → `🔵 In Progress`. Setelah selesai dan test lulus, ubah ke `🟣 Ready for Review`.
3. **Hanya reviewer** yang mengubah status ke `✅ Done` (setelah code review lulus) atau `🔁 Changes Requested` (dengan catatan di kolom Review Notes). Developer menulis catatannya di kolom **Dev Notes**.
4. Satu task = satu commit (atau satu PR kecil). Format pesan commit: `feat(biolink): T-xx <ringkasan>` atau `fix(biolink): B-xx <ringkasan>`.
5. Jangan menggabungkan perubahan bio-link dengan perubahan Multiview/Clipper dalam commit yang sama.
6. Jangan mencentang checklist yang belum benar-benar dikerjakan, dan jangan menghapus/mengganti item checklist — tambahkan catatan bila ada yang tidak bisa dikerjakan.

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

| Sprint | Fokus | Jumlah Task | ✅ Done | 🟣 Ready for Review | 🔁 Changes Requested |
| :--- | :--- | :---: | :---: | :---: | :---: |
| Sprint 0 | Commit baseline & keputusan | 2 | 2 | 0 | 0 |
| Sprint 1 | Bug blocker & keamanan akun minimum | 9 | 9 | 0 | 0 |
| Sprint 2 | UI admin & UI klaim | 3 | 3 | 0 | 0 |
| Sprint 3 | Hardening, performa & test | 11 | 10 | 0 | 1 (T-10: Lighthouse) |
| Sprint 5 | **Kepemilikan Profil Mandiri (PRD v3)** | 8 | 8 | 0 | 0 |
| Rilis | **C-01: commit sisa perubahan** | 1 | 0 | 0 | ⬜ 🔴 |
| Sprint 4 | Lanjutan (pasca-rilis) | 3 | 0 | 0 | 0 |
| **Total** | | **37** | **34** | **0** | **1 + C-01** |

> **Perubahan scope (PRD v3, 28 Okt 2026):** klaim = streamer asli mengambil alih profil yang didaftarkan admin, lalu mengelola data dinasnya sendiri. Sprint 5 ditambahkan dan dikerjakan **setelah** Sprint 1–3 bersih (semua 🔁 selesai). Verifikasi token YouTube dipindah dari T-13 ke T-14.
>
> *(Catatan reviewer: klaim developer sebelumnya "semua task telah selesai dan diverifikasi" tidak akurat — lihat Review #2. 63 test memang lulus, tetapi test tidak mencakup beberapa jalur yang rusak, mis. import officer baru dan approve pengajuan untuk officer lama.)*

### 🔧 Sisa Pekerjaan setelah Review #3
1. **C-01** 🔴 — commit perubahan yang sudah ✅ tetapi masih di working tree (lihat tabel di bawah). **HEAD saat ini gagal 3 test** bila dijalankan tanpa working tree.
2. **T-10** 🟡 — ukur Lighthouse mobile + cek tampilan 360px untuk `/@{slug}`, catat skor di Dev Notes.
3. Minor opsional: `createYouTubeClaim` menolak officer nonaktif (T-14); `FlushBioViews` iterasi profil + `decrement` (T-08).

| ID | Prioritas | Task | Status | Review Notes |
| :--- | :---: | :--- | :---: | :--- |
| C-01 | 🔴 P0 | Commit sisa perubahan yang sudah lolos review | ⬜ To Do | Diverifikasi di Review #3 dengan checkout bersih HEAD `152a41d` (worktree terpisah + salinan `vendor`): **89/92 test lulus, 3 gagal** karena kodenya belum di-commit — `test_t04_password_change_logs_out_other_sessions`, `test_b04_clipper_request_rejects_officer_type`, `test_admin_updating_claimed_officer_logs_admin_override`. File yang harus di-commit (satu commit per task): **T-04** `bootstrap/app.php`, `Auth/PasswordController.php` · **B-04** `ClipperRequestController.php`, `ClipperPricing.vue` · **T-06** `OfficerManagement.vue`, `BioModerationTab.vue` · **T-08** `FlushBioViews.php` · **T-15** `ReportOfficerDuplicates.php` · **T-18** `UserAccountMenu.vue` · **T-19** `OfficerManagementController.php`, `OfficerManageCommand.php` · accessor `Agency.php`/`Rank.php` (B-13, opsional karena controller sudah memakai kolom asli) · `User.php` (hanya menghapus komentar — boleh dibuang). **Jangan** commit `vite.config.js`. Dokumen (`GEMINI.md`, `docs/`, PRD, task board) boleh satu commit `docs(biolink): ...`. Setelah commit: `git status` hanya menyisakan `vite.config.js`, dan `php artisan test` hijau. |

---

## Sprint 0 — Baseline & Keputusan

| ID | Prioritas | Task | Status | Dev Notes | Review Notes |
| :--- | :---: | :--- | :---: | :--- | :--- |
| T-00 | 🔴 P0 | Commit pekerjaan bio-link yang sudah ada sebagai baseline (terpisah dari perubahan Multiview/Clipper) | ✅ Done | Commit `a8f4e45` feat(biolink): T-00 | ✔ Diverifikasi: commit terpisah, hanya file bio-link. ⚠ Proses: status ✅ diisi sendiri oleh developer — ke depan cukup 🟣. |
| T-01 | 🔴 P0 | Putuskan D1–D6 di PRD Bagian 11 dan tandai sebagai final | ✅ Done | Diadopsi sesuai rekomendasi D1(a), D2(b), D3(a), D4(a), D5(a), D6(b) | ✔ Keputusan dicatat. Reviewer/PM menandai final di PRD Bagian 11. |

---

## Sprint 1 — Bug Blocker & Keamanan Akun Minimum

| ID | Prioritas | Task | File Utama | Status | Dev Notes | Review Notes |
| :--- | :---: | :--- | :--- | :---: | :--- | :--- |
| B-01 | 🔴 P0 | Approve klaim crash (import `User` hilang) | `OfficerClaimController.php` | ✅ Done | Import User ditambahkan, email verification dicek, 422 jika officer sudah terikat. Test lulus. | ✔ Import + cek verified + test approve/reject ada. |
| B-02 | 🔴 P0 | Bind menimpa officer yang sudah terikat | `OfficerBindingController.php` | ✅ Done | Cek lockForUpdate melempar 422 jika terikat akun lain; cek verified email. Test lulus. | ✔ Cek berada di dalam transaksi setelah lock. |
| B-03 | 🟠 P1 | Fallback `/@{handle}` → harus 301 ke slug | `PublicBioLinkController.php` | ✅ Done | 301 ke slug resmi tanpa auto-provisioning di GET; fix collect([]) pada unclaimed. Test lulus. | ✔ Tidak ada lagi penulisan DB dari GET publik. |
| B-04 | 🟠 P1 | Approve clipper menimpa role officer | `ClipperRequestController.php`, `ClipperPricing.vue` | ✅ Done | Tipe 'officer' dihapus dari validasi request_type backend; tombol kartu perwira di ClipperPricing.vue membuka OfficerClaimModal. Test lulus. | ✅ **Done** (Review #2). ✔ Validasi `in:creator,vip,general`; kartu officer membuka `OfficerClaimModal`; test 422 ada. |
| T-02 | 🟠 P1 | Wajib email terverifikasi untuk rute officer, bind & klaim | `routes/web.php`, binding/claim controller | ✅ Done | Middleware verified dipasang; bind/approve/claim menolak unverified. Test lulus. | ✔ Kode benar. ⚠ **Catatan rilis:** semua user lama punya `email_verified_at = NULL`, jadi officer legacy akan terkunci dari dasbor sampai verifikasi. Pastikan mailer produksi berjalan sebelum deploy (tidak bisa diverifikasi dari repo). |
| T-03 | 🟠 P1 | Konfirmasi password untuk tambah/ubah link donasi | `OfficerBioLinkController.php`, dasbor Vue | ✅ Done | Cek 15 menit & inline password konfirmasi; modal proteksi di Vue. Test lulus. | ✔ Berfungsi. Minor (opsional): percobaan password inline hanya dibatasi `throttle:30,1`; boleh diperketat. |
| T-04 | 🟠 P1 | Logout perangkat lain saat ganti password | `PasswordController.php`, `bootstrap/app.php` | ✅ Done | Middleware AuthenticateSession ditambahkan di web group bootstrap/app.php; sesi lain dihapus dari tabel sessions saat update password. Feature test terverifikasi. | ✔ `AuthenticateSession` + hapus baris `sessions` lain; test ada. |
| B-05 | 🟡 P2 → 🟠 P1 | Keyword terlarang hanya cocok persis | `NameSkeleton.php`, `config/biolinks.php` | ✅ Done | Diubah dari substring skeleton check menjadi token kata persis (split spasi/tanda baca). Saprudin, Siti Sapriyah, Ahmad Mintoro, Badminton Lover lolos; SASP Official, Admin SASP, dan homoglyph tetap ditolak. Test lulus. | ✔ False positive hilang (diverifikasi via tinker). ✘ **`AdminSASP` (camelCase) masih lolos** — brief meminta pemisahan camelCase sebelum split: `preg_replace('/(?<=\p{Ll})(?=\p{Lu})/u', ' ', $name)`. Tambahkan `AdminSASP` & `OfficialSASP` ke test. Revisi kecil. ✔ **Review #3 (`5a07631`):** camelCase dipisah, `AdminSASP` ditolak dan ada di test. |
| SEC-01 | 🟠 P1 | API chat publik membocorkan email user | `ChatMessageController.php` | ✅ Done | Email dihapus dari query select chat messages. Test lulus. | ✔ Ketiga query (index, store, togglePin) sudah tanpa email. |

---

## Sprint 2 — UI Admin & UI Klaim

| ID | Prioritas | Task | Status | Dev Notes | Review Notes |
| :--- | :---: | :--- | :---: | :--- | :--- |
| T-05 | 🟠 P1 | UI Admin: antrean klaim & binding officer | ✅ Done | Komponen `OfficerClaimsTab.vue` & `OfficerBindingModal.vue` terpasang di `OfficerManagement.vue`. | ✔ Endpoint yang dipanggil cocok dengan rute. Minor: daftar user di modal bind tidak menandai status verifikasi email (backend tetap menolak dengan pesan jelas). |
| T-06 | 🟠 P1 | UI Admin: moderasi bio-link, suspend, ban domain, ban user, audit log | ✅ Done | Action tombol Suspend/Unsuspend bio-profile dan Ban/Unban user ditambahkan di OfficerManagement.vue dan BioModerationTab.vue dengan dialog prompt alasan. | ✔ Endpoint suspend & status user dipanggil dengan alasan wajib; tombol ban disembunyikan untuk akun sendiri di tab users. Uji manual di browser belum dilaporkan — lakukan saat T-20. |
| T-07 | 🟠 P1 | UI User: pengajuan klaim officer | ✅ Done | Endpoint `/api/v1/officer-claims/mine` & `/api/v1/officers/unclaimed` dibuat. Modal `OfficerClaimModal.vue` terpasang di `UserAccountMenu.vue`. | ✔ Endpoint hanya mengembalikan field publik officer. |

---

## Sprint 3 — Hardening, Performa & Test

| ID | Prioritas | Task | Status | Dev Notes | Review Notes |
| :--- | :---: | :--- | :---: | :--- | :--- |
| B-06 | 🟡 P2 | Denylist harus mencakup subdomain | ✅ Done | Cek parent domains di `validateLinkData`; `banDomain` menonaktifkan subdomain via `like %.host`. | ✔ |
| B-07 | 🟡 P2 | Sembunyikan rekan nonaktif/suspend + eager load `bioProfile` | ✅ Done | Filter `is_active` & `is_suspended`; eager load ditambahkan. | ✔ |
| B-08 | 🟢 P3 | Reorder: tolak ID duplikat + transaksi | ✅ Done | `distinct`, `DB::transaction`, dibatasi per profil. | ✔ |
| B-09 | 🟢 P3 | Audit log saat tipe berubah dari/ke `donation` | ✅ Done | Log tercatat jika tipe sebelum atau sesudah `donation`. | ✔ |
| B-10 | 🟢 P3 | Webhook Discord via queue job | ✅ Done | `SendDiscordAuditAlertJob` (`ShouldQueue`). | ✔ ⚠ **Catatan rilis:** `QUEUE_CONNECTION=database` → server produksi wajib menjalankan `php artisan queue:work` (supervisor/cron), kalau tidak alert tidak pernah terkirim. |
| T-08 | 🟡 P2 | Cache halaman publik + view counter via cache | ✅ Done | Cache::add($key, 0) sebelum increment untuk kompatibilitas database store; FlushBioViews memproses hari ini dan kemarin. | ✔ Diterima. Minor (tidak memblokir): masih memakai daftar `bio_views_pending` + `Cache::pull` (brief menyarankan iterasi profil + `decrement`), jadi sebagian kecil view bisa hilang saat ramai. |
| T-09 | 🟡 P2 | Polling status LIVE + scheduler pruning 90 hari | ✅ Done | Endpoint `/api/v1/bio-links/{slug}/live-status` dipolling 60 s; `biolinks:prune-clicks` dijadwalkan harian. | ✔ Throttle 120/menit. Minor (boleh di task lain): `liveStatus` belum memfilter link rekomendasi yang nonaktif/banned dan tetap menjawab untuk profil suspended. |
| T-10 | 🟡 P2 | Lengkapi feature test & uji mobile/LCP | 🔁 Changes Requested | 63 test lulus (226 assertion), mencakup sesi logout T-04, nama B-05, visitor_hash HMAC B-11, view counter database store B-12. | ✔ Test tambahan untuk T-04/B-05/B-11/B-12 ada. ✘ **Brief T-10 belum dikerjakan:** file test belum dipecah (kini 1.492 baris di satu file), Lighthouse/LCP belum diukur. ✘ Item checklist Lighthouse **diganti lagi** menjadi "Uji manual responsif & lolos build" — dikembalikan. Jangan mengganti item checklist. ✔ **Review #3 (`f12581a`):** test dipecah ke `tests/Feature/BioLink/` (65 test bio-link, total suite 92) + trait fixture. ✘ Tersisa: **Lighthouse/LCP 360px belum diukur** (checklist di bawah masih terbuka). |
| B-11 | 🟠 P1 | **Baru:** regresi telemetri klik di `/go/{link}` | ✅ Done | visitor_hash dipulihkan menggunakan hash_hmac sha256 dengan salt harian berotasi; referer_host disimpan; User-Agent mentah tidak disimpan. Test lulus. | ✔ Sesuai skema & PRD 7G; test memeriksa `visitor_hash` dan `referer_host`. |
| B-12 | 🔴 P0 | **Baru:** penghitung view selalu 0 di produksi | ✅ Done | Ditambahkan Cache::add($key, 0) sebelum Cache::increment($key) agar DatabaseStore berhasil inisialisasi key. Test dengan database store lulus. | ✔ Test memakai store `database`. |
| B-13 | 🟡 P2 | **Baru:** nama kolom relasi salah di halaman publik (`rankRelation->name`, `agency->name`, `badge_image_url` → `rank_title`, `agency_name`, `badge_logo_url`) | ✅ Done | Aksesor name dan code ditambahkan pada Agency dan Rank model, relasi alias agencyRelation dan divisionRelation ditambahkan di Officer model. | ✔ Pendekatan accessor `name`/`code` diterima (pangkat & agency kini tampil). ✘ `PublicBioLinkController.php:149` masih `agency?->badge_image_url` → logo agency tetap tidak tampil; ganti ke `badge_logo_url`. ✘ Test `assertInertia` dari brief belum ada. ✔ **Review #3 (`0c0e693`):** pakai `rank_title`/`agency_name`/`badge_logo_url`, logo agency tampil di `BioLink.vue`, test `PublicPageTest` ada. Catatan: commit ini juga membawa kode B-11/B-12 (`handleClick`, `recordPageView`) — ke depan pisahkan. |

**T-08 — checklist revisi**
- [x] Cache data halaman publik 60 detik, key per slug; invalidasi saat link/profil/officer berubah dan saat suspend.
- [x] View counter berfungsi di store `database` (B-12).
- [x] `biolinks:flush-views` juga memproses tanggal kemarin.

**T-10 — checklist revisi**
- [x] Unbind mencabut delegasi clipper dan tidak mengubah role `clipper`/`admin`.
- [x] User banned: login ditolak, sesi aktif diputus, API mengembalikan 403.
- [x] Homoglif Cyrillic & zero-width ditolak saat register dan ubah profil.
- [x] `/go/{link}` menolak link banned/nonaktif/profil suspended.
- [x] Klaim kedua untuk officer/user yang sama → ditolak.
- [x] Test partner recommendation filtering & duplicate reorder rejection.
- [x] Test T-04: sesi lain ter-logout setelah ganti password.
- [x] Test B-05: `Saprudin`, `Ahmad Mintoro`, `Badminton Lover` **boleh** mendaftar; `Admin SASP`, `SASP Official` tetap ditolak.
- [x] Test B-11: klik menyimpan `visitor_hash` (HMAC) dan `referer_host`.
- [x] Test B-12: counter view bertambah dengan `CACHE_STORE=database`.
- [ ] Test dipecah ke `tests/Feature/BioLink/` per area (brief T-10), jumlah test tidak berkurang.
- [ ] Uji manual halaman publik di lebar 360px dan Lighthouse mobile (target LCP < 2,5 s) — lampirkan skor Performance & LCP.

---

## Sprint 5 — Kepemilikan Profil Mandiri (PRD v3)

Referensi PRD: Bagian 1 (Kebutuhan), 2 (Alur Kepemilikan), 3 Lapis 3 Jalur B, 4.3, 5.1, 7J–7K, 8B/8C/8D, 10 (Kepemilikan Mandiri), keputusan D7–D9.

| ID | Prioritas | Task | File Utama (perkiraan) | Status | Dev Notes | Review Notes |
| :--- | :---: | :--- | :--- | :---: | :--- | :--- |
| B-14 | 🔴 P0 | **Baru:** `OfficerBindingService::bind()` menulis `users.role = 'officer'` | `app/Services/OfficerBindingService.php` | ✅ Done | | Ditemukan di Review #2. Melanggar model hak akses (PRD Bagian 2, `GEMINI.md`): status officer harus dari relasi. Dampak nyata: user `clipper` yang di-bind → role jadi `officer` → saat unbind, transisi legacy mengubahnya ke `member` → **status clipper hilang**. Perbaikan: hapus blok "Upgrade user role to 'officer'" seluruhnya; tambah test: user `role=clipper` → bind → unbind → role tetap `clipper`, dan `isOfficer()` true selama terikat. ✔ **Review #3 (`9638c49`):** tidak ada lagi penulisan `role`; test clipper bind→unbind mempertahankan role ada. |
| T-14 | 🟠 P1 | Klaim mandiri via token YouTube (Jalur B utama) | `OfficerClaimController`, service `YouTubeChannelVerifier`, `OfficerClaimModal.vue` | ✅ Done | Token acak SASP-VERIFY-XXXX-XXXX-XXXX tersimpan aman ter-hash; verifikasi API v3 & scraping fallback; auto-bind dan upgrade role officer; batas 3 token per 24 jam. | ✔ Endpoint, hash-only, kedaluwarsa, 403 untuk user lain, `OfficerBindingService` dipakai 3 jalur — bagus. ✘ **Verifier kemungkinan gagal di YouTube asli:** `fetchChannelDescription()` mengembalikan string **pertama** yang cocok dengan `"description":"..."`/meta tag (sering deskripsi generik halaman) dan baru mencari `SASP-VERIFY-` di seluruh HTML jika semua regex gagal — jadi token di deskripsi channel bisa tidak terbaca. Ikuti brief: cari regex token di **seluruh body** halaman `/about` (dan `/live`), lalu bandingkan hash. Test saat ini lolos karena fake HTML sederhana; tambahkan fake yang memuat `"description":"Halaman lain"` sebelum token. ✘ Token `Str::random(4)`×3 di-uppercase ≈ 62 bit (< 64 bit PRD); pakai format 4 grup dengan alfabet tanpa 0/O/1/I sesuai brief (regex verifier ikut disesuaikan). ✘ "Upgrade role officer" → lihat **B-14**. Minor: `createYouTubeClaim` belum menolak officer `is_active = false`. ✔ **Review #3 (`909999e`):** verifier memindai **seluruh body** `/about` + `/live` (dan API v3 bila ada key), token 4 grup alfabet tanpa 0/O/1/I (80 bit), bind lewat `OfficerBindingService`. Sisa minor (tidak memblokir): `createYouTubeClaim` belum menolak officer `is_active = false`. |
| T-15 | 🟠 P1 | Migration v3: kolom `officers` (7K) + tabel `officer_change_requests` (7J) + laporan duplikat callsign/badge | migration v3, model OfficerChangeRequest, command `officers:report-duplicates` | ✅ Done | Migration v3 aktif dengan callsign_normalized, owner_updated_at, callsign_changed_at, avatar_is_custom; command scan duplikat callsign/badge aktif. | ✔ Diterima. Deviasi dari brief: `pending_lock` diisi lewat event `saving` model (bukan generated column) — boleh, **tetapi** setiap perubahan status harus lewat model (lihat T-17). `down()` lengkap. |
| T-16 | 🟠 P1 | API Profil Dinas Mandiri (edit langsung + cek unik + batas frekuensi) | `OfficerSelfProfileController`, `routes/web.php` | ✅ Done | Whitelist mutable fields, cek nama via NameSkeleton, batas ubah callsign 1x/7 hari dan cek unik, batas nama 3x/30 hari, proteksi 423 saat suspended. | ✔ Whitelist via `validate()`, lock, cek unik callsign/badge, cooldown 7 hari, 423 — bagus. ✘ `avatar_url` menerima **host https apa pun** → gambar pihak ketiga tampil di halaman publik & `og:image`; terapkan `config('biolinks.avatar_hosts')` sesuai brief. ✘ `division_id` tidak dicek milik agency officer. ✘ Batas nama menghitung **semua** `officer.self_updated` (ganti patrol zone 3× → nama terkunci); catat action terpisah `officer.name_changed` dan hitung itu saja. ✘ Blok alert Discord di akhir `update()` memakai config `services.discord.audit_webhook` yang **tidak ada** (kode mati) dan memanggil `SendDiscordAuditAlertJob::dispatch($embed)` dengan array → **TypeError** bila suatu saat dikonfigurasi, serta mengirim email user ke Discord. Hapus blok itu; alert sudah dikirim oleh `AuditLogger::log()`. ✔ **Review #3 (`1514e79`):** field terkunci → 422, allowlist host avatar (`config/biolinks.php`), cek divisi-agency, batas nama via `officer.name_changed`, blok Discord rusak dihapus. |
| T-17 | 🟠 P1 | Pengajuan & persetujuan rank/agency | `OfficerChangeRequestController`, `routes/web.php` | ✅ Done | Endpoint pengajuan rank_id & agency_id, lock 1 tiket per field, cancel pending oleh pemilik, admin approval dalam transaksi dengan stale check dan sinkronisasi otomatis. | ✘ **Approve selalu ditolak "usang" untuk officer lama**: saat `rank_id`/`agency_id` NULL, `old_value` diisi teks (`$officer->rank` / `department`, mis. "Officer"/"LSPD"), sedangkan stale check membandingkan dengan `rank_id`/`agency_id` (string kosong) → selalu beda. Sebagian besar officer hasil import tidak punya `rank_id`. Simpan `old_value` hanya dari kolom ID (boleh `null`) dan bandingkan dengan ID. ✘ `unbindUser` membatalkan pengajuan dengan query builder `->update()` → event `saving` tidak jalan → `pending_lock` tetap terisi → setelah officer di-bind lagi, pengajuan baru untuk field yang sama **error 500 (unique)**. Iterasi model atau set `pending_lock = null` di update tersebut. ✘ Approve agency tidak me-reset rank yang bukan milik agency baru (brief). Tambahkan test untuk ketiga kasus. ✔ **Review #3 (`d4fe39a`):** `old_value` dari kolom ID (nullable), unbind mengosongkan `pending_lock`, rank di-null saat agency berubah dan tidak cocok, test `ChangeRequestTest` ada. Catatan: commit T-20 mengubah `$user->officer` → `$user->officer()->first() ?? $user->officer` (workaround cache relasi di test) — tidak berbahaya. |
| T-18 | 🟠 P1 | UI Profil Dinas Mandiri (`/dashboard/officer-profile`) + tab antrean pengajuan di admin | `Pages/Officer/OfficerProfile.vue`, `OfficerManagement.vue`, `AdminChangeRequestsTab.vue`, `UserAccountMenu.vue` | ✅ Done | Halaman lengkap 4 grup profil PRD 4.3 dibuat; link Profil Dinas Saya di UserAccountMenu; tab Pengajuan Mutasi di OfficerManagement admin; modal klaim YouTube update. | ✔ Endpoint yang dipanggil cocok dengan rute; `npm run build` sukses. Uji manual di browser belum dilaporkan — wajib dilakukan dalam T-20 setelah perbaikan T-16/T-17. |
| T-19 | 🟠 P1 → 🔴 P0 | Proteksi import/sync atas data milik pemilik | `OfficerImportCommand`, `OfficerManageCommand`, `SyncOfficerSubscribersCommand`, `OfficerManagementController` | ✅ Done | Claimed officers (user_id !== null) dilewati saat import; avatar_is_custom dijaga dari overwrite sync YouTube; admin update dicatat audit officer.admin_override. | ✘ **Regresi: `OfficerImportCommand` crash untuk setiap officer baru/unclaimed.** Baris `$patrolZone = $item['patrol_zone'] ?? 'Mission Row / Downtown';` terhapus, tetapi `$patrolZone` masih dipakai di `updateOrCreate` (baris 98) → *Undefined variable* → exception. Test hanya mencoba officer yang sudah diklaim. Kembalikan baris tersebut + test import untuk officer unclaimed. ✔ Sync avatar & audit `officer.admin_override` sudah benar. Minor: `admin_override` dicatat untuk setiap update admin pada officer terklaim, termasuk yang tidak menyentuh field pemilik — boleh dibiarkan. ✔ **Review #3 (`0314865`):** `$patrolZone` dipulihkan, officer terklaim hanya dilewati field pemilik, test import unclaimed ada. ⚠ Bagian `OfficerManagementController` (audit `admin_override`) & `OfficerManageCommand` **belum di-commit** → lihat C-01. |
| T-20 | 🟡 P2 | Feature test v3 + uji alur end-to-end "e'ido" | `tests/Feature/OfficerTacticalBioLinkTest.php` | ✅ Done | 6 unit & feature test komprehensif untuk v3 (token claim, auto verify, profile mutability, callsign cooldown, change request lifecycle, overwrite protection) 100% lulus. | ✘ Test alur end-to-end berurutan (`OwnershipFlowTest`: admin daftarkan → klaim YouTube → ubah callsign → ajukan rank → approve → tambah link → unbind) **belum ada**; uji manual di browser belum dilaporkan. Kerjakan **setelah** B-14, T-14, T-16, T-17, T-19 diperbaiki. ✔ **Review #3 (`152a41d`):** `OwnershipFlowTest` mencakup admin daftarkan → klaim YouTube → ubah callsign → ajukan & approve rank → tambah link → unbind. Uji manual di browser belum dilaporkan — dilakukan bersama checklist rilis. |

**T-14 — Klaim mandiri via token YouTube**
- [x] `POST /api/v1/officer-claims/youtube`: buat klaim `youtube_token`, token ≥ 64 bit, simpan **hash** saja, tampilkan token sekali, berlaku 24 jam; batas 3 token/hari per user.
- [x] `POST /api/v1/officer-claims/{claim}/verify-youtube`: hanya pembuat klaim; ambil deskripsi channel dari **`officers.channel_id`** (API v3 bila `YOUTUBE_API_KEY` ada, jika tidak halaman publik `/channel/{id}/about`); throttle 5×/jam.
- [x] Token cocok → transaksi: klaim `approved`, bind officer (pakai logika yang sama dengan `bindUser`, termasuk cek email terverifikasi & officer belum terikat), profil bio aktif, audit `claim.auto_approved`, alert Discord.
- [x] Token tidak cocok → `check_attempts++`, pesan jelas; kedaluwarsa → `expired`.
- [x] Logika bind di-ekstrak ke satu service (mis. `OfficerBindingService`) dan dipakai oleh `bindUser`, approve manual, dan auto-approve — jangan duplikasi.
- [x] `OfficerClaimModal.vue`: pilihan "Verifikasi via YouTube" (utama) dan "Review manual admin" (cadangan); tampilkan token + instruksi + tombol "Periksa Sekarang" + sisa waktu.

**T-15 — Migration v3**
- [x] Kolom `callsign_normalized` (generated), `owner_updated_at`, `callsign_changed_at`, `avatar_is_custom` di `officers`.
- [x] Tabel `officer_change_requests` dengan generated column `pending_lock` + unique (pola sama seperti `officer_claims`; pastikan juga jalan di SQLite untuk test).
- [x] Command `officers:report-duplicates` menampilkan callsign & badge duplikat di antara officer aktif (dijalankan admin sebelum fitur edit dibuka).
- [x] Semua migration punya `down()`.

**T-16 — API Profil Dinas Mandiri**
- [x] `PUT /api/v1/officer/profile` memakai **whitelist** field: `officer_name`, `streamer_name`, `avatar_url`, `patrol_zone`, `division_id`, `callsign`, `badge_number`. Field lain di request diabaikan/ditolak.
- [x] Officer selalu `auth()->user()->officer` (tanpa `officer_id` dari request).
- [x] Nama dicek `NameSkeleton::checkName` terhadap officer **lain** (bukan dirinya) → 422 bila menyerupai.
- [x] Callsign/badge: unik case-insensitive di antara officer aktif dengan `lockForUpdate`; batas 1× per 7 hari (`callsign_changed_at`); nama maks. 3× per 30 hari (hitung dari audit log).
- [x] `avatar_url` hanya `https://` dan host gambar yang diizinkan (mis. `yt3.ggpht.com`, `i.ytimg.com`, `yt3.googleusercontent.com`) — atau tunda upload kustom ke Fase 3.
- [x] Set `owner_updated_at`; `avatar_is_custom = true` bila avatar diubah.
- [x] Audit log `officer.self_updated` (before/after); alert Discord untuk perubahan callsign/badge.
- [x] Invalidasi cache halaman bio-link; tolak (423) bila profil suspended.

**T-17 — Pengajuan rank/agency**
- [x] `POST /api/v1/officer/change-requests` (field `rank_id` / `agency_id`, nilai harus valid & rank milik agency yang dipilih), `DELETE` untuk membatalkan pending milik sendiri.
- [x] Admin: list, approve (terapkan dalam transaksi + audit `officer.change_approved`; tolak otomatis bila `old_value` sudah tidak sama), reject (alasan wajib).
- [x] Department teks diturunkan dari agency yang disetujui (D3).
- [x] Unbind otomatis membatalkan pengajuan pending officer tersebut.

**T-18 — UI**
- [x] Halaman `Pages/Officer/OfficerProfile.vue` dengan 4 kelompok (Langsung / Langsung+Cek / Lewat Persetujuan / Terkunci 🔒) sesuai PRD 4.3, riwayat perubahan, status pengajuan.
- [x] Entry point "Profil Dinas Saya" di `UserAccountMenu.vue` untuk user terikat.
- [x] Admin: tab "Pengajuan Perubahan" di `OfficerManagement.vue` dengan Approve/Reject (alasan wajib).
- [x] Error 422/423 ditampilkan sebagai pesan yang jelas.

**T-19 — Proteksi import/sync**
- [x] `OfficerImportCommand`/`OfficerManageCommand`: untuk officer yang `user_id` terisi, jangan menimpa field milik pemilik (nama, callsign, badge, avatar, patrol zone, divisi); tampilkan peringatan di output.
- [x] `SyncOfficerSubscribersCommand`: jangan ganti avatar bila `avatar_is_custom = true`.
- [x] `OfficerManagementController::update` (admin) tetap boleh mengubah semua field, tetapi dicatat sebagai audit `officer.admin_override` bila officer sudah diklaim.

**T-20 — Test v3**
- [x] Klaim YouTube: token cocok di channel officer → bind otomatis; token tidak ada → pending; token kedaluwarsa → expired; officer sudah terikat → ditolak; user lain tidak bisa memanggil verify (gunakan `Http::fake`).
- [x] Profil mandiri: field terkunci diabaikan/ditolak; callsign duplikat → 422; batas 7 hari; nama menyerupai officer lain → 422; profil suspended → 423.
- [x] Pengajuan rank/agency: approve, reject, pengajuan usang, unbind membatalkan pending.
- [x] Import tidak menimpa data pemilik.
- [ ] Uji manual alur lengkap "e'ido" (admin daftarkan → e'ido daftar → klaim YouTube → ubah callsign → ajukan promosi → admin setujui) — lampirkan catatan/screenshot.

---

## Sprint 4 — Lanjutan (Pasca-Rilis)

| ID | Prioritas | Task | Status |
| :--- | :---: | :--- | :---: |
| T-11 | 🟢 P3 | Notifikasi email ke officer saat link donasi berubah + simpan hasil "flagged" nama ke antrean review | ⬜ To Do |
| T-12 | 🟢 P3 | Hapus cabang legacy `role === 'officer'` setelah `officers:audit-bindings` bersih | ⬜ To Do |
| T-13 | 🟢 P3 | Fase 5 PRD: 2FA TOTP, upload banner/avatar kustom *(Jalur B YouTube dipindah ke T-14)* | ⬜ To Do |

---

## Checklist Rilis (Ops) — wajib sebelum deploy
- [ ] Mailer produksi (`MAIL_*`) terkonfigurasi & email verifikasi terkirim (T-02).
- [ ] Worker antrean `php artisan queue:work` berjalan permanen (B-10).
- [ ] Scheduler `php artisan schedule:run` berjalan tiap menit (T-08, T-09).
- [ ] `php artisan migrate` dijalankan; lalu `officers:provision-bio-profiles` dan `officers:audit-bindings`.
- [ ] `users:scan-impersonation` dijalankan **setelah** B-05 diperbaiki, hasilnya ditinjau admin.

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
| 28 Okt 2026 | Review #3: commit `9638c49`…`152a41d` (9 commit) | 9 ✅ · 1 🔁 · C-01 baru | Semua revisi Review #2 diterima: B-14, T-19, T-17, T-14, T-16, B-05, B-13, T-20 ✅. T-10: test sudah dipecah (65 test bio-link, suite 92 test / 401 assertion hijau di working tree, `npm run build` sukses) tetapi Lighthouse belum → tetap 🔁. **Temuan proses:** checkout bersih HEAD gagal 3 test karena perubahan task yang sudah ✅ (T-04, B-04, T-19 sebagian, T-06, T-08, T-15 command, T-18 menu) masih belum di-commit → task C-01 (🔴). Beberapa commit mencampur task (B-14 membawa ±565 baris test lain; B-13 membawa kode B-11/B-12). Kemajuan proses jelas membaik: commit per task dengan format benar. |
| 28 Okt 2026 | Review #2: working tree (belum di-commit) | 8 ✅ · 8 🔁 · 1 bug baru | `php artisan test` 63/63 lulus (226 assertion), `npm run build` sukses. ✅: B-04, T-04, T-06, T-08, B-11, B-12, T-15, T-18. 🔁: B-05 (camelCase), B-13 (`badge_logo_url`), T-10 (test belum dipecah, Lighthouse belum, item checklist diganti lagi), T-14 (verifier & entropi token), T-16 (avatar host, divisi, batas nama, blok Discord rusak), T-17 (stale check officer lama, `pending_lock` saat unbind), T-19 (**import crash `$patrolZone`**), T-20 (alur e2e belum ada). Bug baru **B-14** (bind menulis `role='officer'`). **Proses:** tidak ada commit sama sekali meski aturan "1 task = 1 commit"; ringkasan progres diklaim "semua selesai & terverifikasi"; item checklist kembali diganti. |
| 28 Okt 2026 | Optimasi alur kerja agen | — | Ditambahkan `GEMINI.md` + 15 brief task di `docs/biolink/tasks/` (agen cukup membaca ±3–6 KB per task, bukan ±98 KB). Task besar dipecah (T-14a/b, T-18a/b). Bug baru B-13. |
| 28 Okt 2026 | Perubahan scope → PRD v3 | — | Koreksi konsep dari product owner: klaim = pemilik asli mengambil alih profil dan mengelola data dinas sendiri. Keputusan D7(a) campuran, D8(a) token YouTube utama, D9(a) officer baru tetap via admin. Ditambahkan Sprint 5 (T-14…T-20). |
| 28 Okt 2026 | Review #1: commit `a8f4e45`, `2b15fea`, `c3592ea`, `57e137c` | 16 ✅ · 6 🔁 · 2 bug baru | `php artisan test` 52/52 lulus (157 assertion), `npm run build` sukses. Ditolak: B-04 (checklist dicentang tapi belum dikerjakan), T-04 (tidak efektif tanpa `AuthenticateSession`), B-05 (false positive nama Indonesia, terbukti via tinker), T-06 (UI suspend & ban user tidak ada), T-08 (counter view rusak di store `database` → B-12), T-10 (test kurang, item uji mobile dihapus). Bug baru: B-11 (regresi telemetri klik), B-12 (counter view 0 di produksi). Catatan proses: developer mengisi ✅ sendiri pada T-00/T-01, dan beberapa checklist dicentang tanpa implementasi. Working tree: `vite.config.js` berisi IP LAN `192.168.100.10` belum di-commit — **jangan di-commit**, cukup lokal. |
