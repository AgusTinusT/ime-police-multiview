# Brief Task Bio-Link (untuk agen coding)

Setiap file di folder ini adalah **brief mandiri** untuk satu task: cukup baca `GEMINI.md` + satu brief. Status resmi dan review tetap di `OFFICER_TACTICAL_BIO_LINKS_TASKS.md`.

## ⚠️ Perbaikan Review #2 (kerjakan dulu, urut)

| # | Task | Inti revisi (detail di kolom Review Notes task board) |
| :---: | :--- | :--- |
| 1 | [B-14](B-14.md) 🔴 | Hapus penulisan `role='officer'` di `OfficerBindingService` |
| 2 | T-19 🔴 | Kembalikan `$patrolZone` di `OfficerImportCommand` + test import officer unclaimed |
| 3 | T-17 🟠 | `old_value` hanya dari kolom ID; unbind harus mengosongkan `pending_lock`; reset rank saat agency berubah |
| 4 | T-14 🟠 | Verifier cari token di seluruh body; token 4 grup ≥ 64 bit; tolak officer nonaktif |
| 5 | T-16 🟠 | Allowlist host avatar; cek divisi-agency; batas nama pakai action `officer.name_changed`; hapus blok Discord rusak |
| 6 | B-05, B-13 🟡 | camelCase `AdminSASP`; `badge_logo_url` + test `assertInertia` |
| 7 | T-10, T-20 🟡 | Pecah test; Lighthouse; test alur e2e + uji manual |

Setelah tiap perbaikan: **commit per task** (format di `GEMINI.md`) lalu ubah status ke 🟣.

## Urutan Pengerjaan (awal)

| # | Brief | Prioritas | Ringkasan |
| :---: | :--- | :---: | :--- |
| 1 | [B-12](B-12.md) | 🔴 P0 | View counter selalu 0 di cache `database` (+ sisa T-08) |
| 2 | [B-11](B-11.md) | 🟠 P1 | Telemetri klik `/go` regresi |
| 3 | [B-05](B-05.md) | 🟠 P1 | Keyword terlarang memblokir nama umum |
| 4 | [T-04](T-04.md) | 🟠 P1 | Logout perangkat lain belum efektif |
| 5 | [T-06](T-06.md) | 🟠 P1 | UI suspend bio & ban user |
| 6 | [B-04](B-04.md) | 🟠 P1 | Hentikan tipe `officer` di clipper request |
| 7 | [B-13](B-13.md) | 🟡 P2 | Nama kolom relasi salah di halaman publik |
| 8 | [T-10](T-10.md) | 🟡 P2 | Pecah file test + uji Lighthouse |
| 9 | [T-15](T-15.md) | 🟠 P1 | Migration v3 + laporan duplikat |
| 10 | [T-14](T-14.md) | 🟠 P1 | Klaim via token YouTube (14a backend, 14b UI) |
| 11 | [T-16](T-16.md) | 🟠 P1 | API Profil Dinas Mandiri |
| 12 | [T-17](T-17.md) | 🟠 P1 | Pengajuan rank/agency |
| 13 | [T-18](T-18.md) | 🟠 P1 | UI Profil Dinas + tab admin (18a, 18b) |
| 14 | [T-19](T-19.md) | 🟠 P1 | Proteksi import/sync |
| 15 | [T-20](T-20.md) | 🟡 P2 | Uji end-to-end alur e'ido |

## Prompt yang Disarankan untuk Gemini

```
Baca GEMINI.md dan docs/biolink/tasks/<ID>.md saja.
Kerjakan task <ID> sesuai brief, jalankan test yang relevan dengan --filter,
lalu commit sesuai format di brief dan ubah baris <ID> di
OFFICER_TACTICAL_BIO_LINKS_TASKS.md menjadi 🟣 Ready for Review + Dev Notes.
Jangan baca PRD kecuali brief menyebut bagian tertentu.
```

## Catatan WIP
Per 28 Okt 2026 working tree berisi pekerjaan Sprint 5 yang **belum di-commit** dan dikerjakan sekaligus (migration v3, `OfficerSelfProfileController`, `OfficerChangeRequestController`, `OfficerBindingService`, `YouTubeChannelVerifier`, dll.). Sebelum melanjutkan, rapikan menjadi commit per task sesuai urutan di atas; brief terkait sudah menandai file WIP yang perlu dilanjutkan.
