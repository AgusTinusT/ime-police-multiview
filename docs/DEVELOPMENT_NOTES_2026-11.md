# Catatan Pengembangan — November 2026 (v2.7.0)

Ringkasan pekerjaan 20–22 November 2026, panduan operasional fitur baru, dan daftar hal yang masih perlu dirapikan. Semua perubahan sudah dirilis ke production lewat alur `branch → PR ke staging → PR rilis staging → main`.

## Yang dibangun

| Area | Ringkasan | PR |
|---|---|---|
| Dashboard | Highlight "Officer baru bergabung" 14 hari (`config/police.php` → `new_officer_days`), kartu ringkas untuk 1–3 officer | #9 |
| Dashboard | Panah geser hanya saat bisa digeser, satu baris Squad tanpa duplikat, fallback avatar Top 10, urutan baru (Live → Officer baru → Top 10 → Website rekomendasi → …), filter departemen dihapus | #10–#14 |
| Halaman tonton | `/watch/{videoId}` ala YouTube: player, live chat, live lainnya, rekaman; dipakai kartu dashboard, modal detail, tile multiview | #9 |
| Momen Aksi | TacClips → **Momen Aksi** (`/momen`), tombol **Tandai Momen** di `/watch`, daftar momen + lompat ke detik, `?t=` & `?clip=` | #8, #26 |
| Clip Studio | Satu nama **Clip Studio** (`/clip-studio`), redirect 301 dari `/clipper*`, `/action-clips`, `/clips` (query string ikut) | #26 |
| Perwira | Tombol Buka Channel; akun **non-personel** (`officers.is_listed`, scope `Officer::roster()`) | #15, #17 |
| Admin | Menu Rekomendasi Website bisa dibuka lagi (`VALID_TABS`) | #16 |
| Tampilan | Header kapsul melayang (polos di atas, kapsul saat scroll), menu Lainnya, menu akun baru, tombol Dukung | #19–#22 |
| Dukungan | Halaman `/dukung`, data dari `config/support.php` | #23, #24 |
| Storage | Disk S3 IDCloudHost + `php artisan storage:check-s3` | #27 |
| Arsip | Arsip otomatis Momen Aksi 720p ke S3, hapus file > 30 hari (data momen tetap) | #29, #33 |
| Sinkronisasi | `#imepolice` di judul menang atas kata badside; live tidak lagi masuk "Rekaman terbaru" | #30 |
| Halaman tonton | Deskripsi tampil penuh + link bisa diklik | #31 |
| Clip Studio | Potong live: tidak pernah merekam dari ujung live; deteksi live diperkuat di VPS | #33, #35 |
| Analytics | GA4 `select_stream` + `stream_heartbeat` dengan metadata di `/watch` | #37 |
| Keamanan | 11 advisory Composer ditutup (Laravel 13.43, Symfony, commonmark) | #28 |

## Operasional (server)

### Environment baru
Lihat `.env.production.example`. Yang ditambahkan bulan ini:

- `POLICE_NEW_OFFICER_DAYS` (default 14)
- `SUPPORT_DONATE_URL`
- `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION`, `AWS_BUCKET=ime-police-duty`, `AWS_ENDPOINT=https://is3.cloudhost.id`, `AWS_USE_PATH_STYLE_ENDPOINT=true`, `AWS_URL` (kosong, bucket privat)
- `MOMEN_ARCHIVE_ENABLED`, `MOMEN_ARCHIVE_DISK=s3`, `MOMEN_ARCHIVE_DAYS=30`

Setelah mengubah `.env`: `php artisan config:clear`.

### Perintah
| Perintah | Kegunaan |
|---|---|
| `php artisan storage:check-s3` | Uji tulis/baca/signed URL/hapus ke bucket |
| `php artisan momen:archive` | (Terjadwal tiap 5 menit) antrekan momen pending + coba ulang yang gagal (maks 3×) |
| `php artisan momen:archive --backfill` | Arsipkan momen 30 hari terakhir yang belum pernah diarsipkan |
| `php artisan momen:archive --redo=ID` | Hapus file arsip yang salah lalu potong ulang |
| `php artisan momen:prune-archives` | (Terjadwal harian 03:30) hapus file arsip > 30 hari, data momen tetap |

Status arsip (`tac_clips.archive_status`): `pending`, `processing`, `stored`, `failed` (dicoba ulang), `unavailable` (members-only / private / dihapus, tidak dicoba ulang), `expired`.

### Pemotong video (Clip Studio & arsip)
- `YouTubeClipperService::trimToFile()` adalah satu-satunya kebijakan: VOD → `yt-dlp --download-sections`; live → segmen DVR (`trimLiveStreamDvr`). Jalur VOD menolak siaran live (`--match-filter !is_live`).
- Segmen DVR live YouTube bernomor dari 0 = awal siaran (2 dtk per segmen), dan `getCurrentTime()` embed = detik sejak awal siaran; segmen lama tetap tersedia (diuji ±3,5 jam ke belakang).
- Butuh di VPS: `yt-dlp` (perbarui berkala: `sudo yt-dlp -U`), `ffmpeg`, `node` di `/usr/local/bin` atau `/usr/bin`, worker Supervisor `queue:listen --queue=clipper` (user `www-data`), cron `schedule:run`.
- Kalau log berisi `getVideoStatus yt-dlp failed`, YouTube kemungkinan meminta verifikasi bot: siapkan `storage/app/cookies.txt`.

### Uji cepat potong live di VPS
```bash
php artisan tinker --execute='$c = new App\Models\VideoClip(["youtube_url"=>"https://www.youtube.com/watch?v=<LIVE_ID>","start_time"=>60,"end_time"=>90,"format"=>"MP4 720p"]); $c->id="vpstest"; $out=storage_path("app/tmp/vpstest.mp4"); app(App\Services\YouTubeClipperService::class)->trimToFile($c,$out); dump(filesize($out));'
ffprobe -v error -show_entries format=duration -of csv=p=0 storage/app/tmp/vpstest.mp4   # harus ±30.0
rm -f storage/app/tmp/vpstest.mp4
```

## Perlu dirapikan / tindak lanjut

- [ ] **Hapus komponen yang tidak dipakai:** `FocusModePlayer.vue`, `TacticalStreamGrid.vue`, `CommunityClipsTab.vue`, `CreateActionClipModal.vue`, `VideoClipperModal.vue`, `TacticalClipperBetaBanner.vue`, `MultiviewHeader.vue`.
- [ ] **Putar dari arsip saat video YouTube hilang:** API sudah mengirim `archive_url` (signed URL 2 jam) untuk Momen Aksi, tapi UI `/watch` dan `/momen` belum memakainya.
- [ ] **"Rekaman terbaru" kosong di production:** cek cache `cinema_hub_replays_cache` setelah sinkronisasi; baris itu tersembunyi saat datanya kosong.
- [ ] **Halaman `/dukung`:** isi nominal target hadiah event; benefit "Badge Supporter" dijanjikan tapi belum ada fiturnya.
- [ ] **GA4:** heartbeat dengan metadata juga untuk Multiview (masih heartbeat generik); daftarkan `focused_officer`, `focused_stream_id`, `selected_department` sebagai custom dimension.
- [ ] **`php artisan clipper:doctor`:** satu perintah untuk cek yt-dlp/ffmpeg/node/ekstensi PHP + uji potong 30 detik lewat antrean `clipper`.
- [ ] **Backup database ke S3** (`db:backup` masih menyimpan di VPS yang sama).
- [ ] **Branch protection:** semua merge memakai `--admin` (melewati review). Pertimbangkan approval dari akun kedua atau melonggarkan aturan untuk staging.
- [ ] **Teks hard-coded:** beberapa label baru (Momen Aksi, Clip Studio, Dukung) masih campuran teks langsung dan i18n.
