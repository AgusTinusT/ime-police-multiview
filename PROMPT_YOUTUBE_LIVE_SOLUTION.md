# Prompt & Panduan Teknis Implementation: YouTube Live Clipper untuk Production (Ubuntu VPS)

Dokumen ini berisi **prompt instruksi dan panduan arsitektur teknis lengkap** yang dapat Anda berikan kepada Agent AI atau Developer untuk menerapkan perbaikan fitur **Pemotongan YouTube Live (DVR Stream)** pada server Production (Ubuntu VPS).

---

## 🤖 PROMPT UNTUK AGENT AI (Salin Teks di Bawah Ini)

```markdown
# TASK INSTRUCTION: Implementasi Fitur Cut YouTube Live & Optimization untuk Production (Ubuntu VPS)

## 📌 TUJUAN UTAMA
Terapkan arsitektur pemotongan YouTube Live berbasis **HLS DVR Sequence Manifest Extraction** di backend Laravel dan pastikan antarmuka UI Vue 3 (Inertia.js) beserta fitur **Timeline Trimmer & Touch Cut Slider** tetap dipertahankan dan berjalan sempurna pada lingkungan Production (Ubuntu VPS).

---

## 🔍 AKAR MASALAH (Mengapa Cut YouTube Live Sebelumnya Gagal di Production)

1. **Kelambatan / Error FFmpeg Seek pada Manifest Default (`index.m3u8`)**:
   - Manifest `.m3u8` bawaan yang dihasilkan `yt-dlp -g` pada YouTube Live aktif hanya memuat *sliding window* 900 detik (15 menit terakhir).
   - Menjalankan `ffmpeg -ss 00:00:10 -i index.m3u8` membuat FFmpeg mencari 10 detik dari **awal sliding window aktif** (misal jam ke-2 siaran / live head masa depan), BUKAN 10 detik dari awal siaran (*broadcast start*).

2. **Error `yt-dlp --download-sections` pada Live Stream Aktif**:
   - `yt-dlp --download-sections` gagal pada siaran berlangsung dengan format DASH (`dashG`) dan mengeluarkan error: `ERROR: This format cannot be partially downloaded`.

3. **Error Temp Directory / PyInstaller (`[PYI-29568:ERROR] Could not create temporary directory!`)**:
   - Pada web server (Nginx/FPM/Apache), variabel lingkungan `%TEMP%` atau `%TMP%` mengarah ke folder sistem yang tidak memiliki hak akses (*write permission*) untuk proses web worker PHP.

---

## 🛠️ SOLUSI TEKNIS BACKEND: HLS DVR Sequence Manifest Extraction

YouTube CDN menyimpan semua segmen chunk siaran live (`.ts`) berbasis nomor urut sequence (misal `/sq/5/` untuk detik ke-10, `/sq/300/` untuk detik ke-300).

### Langkah Algoritma Pemotongan di `YouTubeClipperService.php`:

1. **Ekstrak Stream URL via `yt-dlp`**:
   Execute `yt-dlp -g --no-playlist <URL>` untuk mendapatkan URL manifest `.m3u8` video dan audio.

2. **Deteksi Durasi Chunk secara Dinamis**:
   Baca isi teks `.m3u8`. Ambil durasi chunk dari tag `#EXTINF:([\d\.]+)` atau `#EXT-X-TARGETDURATION:(\d+)`.
   *(Catatan: YouTube Live dapat menggunakan chunk 1.0s, 2.0s, atau 5.0s per segmen)*.

3. **Kalkulasi Range Sequence**:
   - `startSq = floor(startTime / chunkDuration)`
   - `endSq = ceil(endTime / chunkDuration)`

4. **Konstruksi Manifest `.m3u8` Kustom**:
   Buat file `.m3u8` temporary di `storage/app/scratch_playlists` dengan format:
   ```m3u8
   #EXTM3U
   #EXT-X-VERSION:3
   #EXT-X-TARGETDURATION:<ceil(chunkDuration)>
   #EXT-X-MEDIA-SEQUENCE:<startSq>
   #EXTINF:<chunkDuration>,
   https://.../sq/<startSq>/...
   ...
   #EXTINF:<chunkDuration>,
   https://.../sq/<endSq>/...
   #EXT-X-ENDLIST
   ```

5. **Eksekusi FFmpeg dengan Whitelist Protocol**:
   Hitung sisa offset: `seekOffset = startTime - (startSq * chunkDuration)`.
   Jalankan perintah FFmpeg:
   ```bash
   ffmpeg -y \
     -protocol_whitelist "file,http,https,tcp,tls,crypto,data" \
     -ss <seekOffset> -i video_custom.m3u8 \
     -protocol_whitelist "file,http,https,tcp,tls,crypto,data" \
     -ss <seekOffset> -i audio_custom.m3u8 \
     -t <clipDuration> \
     -c:v copy -c:a copy \
     storage/app/public/clips/output.mp4
   ```

6. **Pengaturan Environment Variables pada Symfony `Process`**:
   Buat helper `createProcess()` untuk memastikan `TEMP` dan `TMP` diarahkan ke folder `storage/app/tmp` yang dijamin 100% memiliki izin tulis (*write permission*):
   ```php
   protected function createProcess(array $command, ?int $timeout = 60): Process
   {
       $tmpDir = storage_path('app/tmp');
       if (!file_exists($tmpDir)) {
           @mkdir($tmpDir, 0755, true);
       }

       $process = new Process($command);
       if ($timeout !== null) {
           $process->setTimeout($timeout);
       }

       $process->setEnv([
           'TEMP' => $tmpDir,
           'TMP' => $tmpDir,
           'PATH' => getenv('PATH') ?: '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin',
       ]);

       return $process;
   }
   ```

---

## 🐧 PENYESUAIAN PADA LINGKUNGAN PRODUCTION (UBUNTU VPS)

Karena lingkungan lokal menggunakan Windows dan Production menggunakan **Ubuntu VPS**, perhatikan poin-poin penting berikut:

1. **Dependensi Binary Sistem**:
   - Pastikan `ffmpeg` dan `yt-dlp` terinstall dan dapat diakses secara global di server Ubuntu:
     ```bash
     sudo apt update && sudo apt install -y ffmpeg python3
     sudo curl -L https://github.com/yt-dlp/yt-dlp/releases/latest/download/yt-dlp -o /usr/local/bin/yt-dlp
     sudo chmod a+rx /usr/local/bin/yt-dlp
     ```
   - Lakukan update berkala pada `yt-dlp` agar terhindar dari pembatasan cipher YouTube: `yt-dlp -U`.

2. **Izin Akses Folder Storage (Permissions)**:
   - Pastikan direktori `storage` dan `bootstrap/cache` memiliki izin tulis untuk user web server (`www-data`):
     ```bash
     sudo chown -R www-data:www-data storage bootstrap/cache
     sudo chmod -R 775 storage bootstrap/cache
     ```
   - Jalankan `php artisan storage:link` untuk menghubungkan `storage/app/public` ke `public/storage`.

3. **Penggunaan File Path Lintas Platform**:
   - Selalu gunakan `DIRECTORY_SEPARATOR` atau tanda garis miring maju (`/`) untuk menangani path berkas agar kompatibel di Linux & Windows.

---

## 🎨 PERTAHANKAN DESAIN UI & FITUR INTERAKTIF FRONT-END

Agent AI **HARUS MEMPERTAHANKAN** seluruh estetika UI modern (Dark Slate Theme, Glassmorphism, Rose/Emerald/Amber Accents, Mobile-First Layout) dan fitur-fitur yang sudah ada:

1. **Preservasi Fitur `Timeline Trimmer & Touch Cut Slider` (`DualRangeTrimmer.vue`)**:
   - Pertahankan penanganan event `touchstart`, `touchmove`, dan `touchend` serta fungsi extractor koordinat `getTimeFromEvent(e)` untuk dukungan layar sentuh HP/tablet dan mouse desktop.
   - Pertahankan fitur **Real-Time Frame Preview** yang secara otomatis menggeser pemutar video saat handle `IN (Emerald)` atau `OUT (Rose)` digeser.
   - Gunakan `.stop.prevent` pada handle agar drag-and-drop tidak mengganggu event klik pada timeline track.

2. **Fitur Antarmuka Tambahan & Mobile-First**:
   - Pertahankan **Sticky Mobile Bottom Action Bar** di bagian bawah layar smartphone (`< 1024px`) dengan tombol cepat `⚡ Potong Klip Real`.
   - Pertahankan horizontal scrollable sample chips, preset durasi (+15s, +30s, +1m, +3m), dan rasio aspek switcher (16:9 Landscape vs 9:16 Shorts).
   - Tampilkan lencana strategi backend (`⚡ hls_dvr_sequence`) pada kartu riwayat klip yang berhasil dieksport.

---

## ✅ KRITERIA VERIFIKASI SELESAI

- [ ] `php artisan test` berjalan 100% lulus.
- [ ] Berhasil memotong video YouTube Live aktif (misal `00:00:10` s.d. `00:00:40` atau `05:00` s.d. `05:30`) secara tepat waktu dari awal siaran (*broadcast start*).
- [ ] Durasi klip hasil potong tepat sesuai rentang target (misal 30.0 detik).
- [ ] File MP4 dapat diputar & diunduh langsung melalui URL publik `storage/clips/...`.
- [ ] Tampilan UI pada smartphone dan desktop berjalan lancar tanpa error konsol.
```

---

## 📌 Ringkasan Komponen Kunci Terkait Dalam Repository Ini

1. **Backend Service**: [`app/Services/YouTubeClipperService.php`](file:///c:/Development/laragon/www/youtube-clipper-demo/app/Services/YouTubeClipperService.php)
2. **Controller Endpoint**: [`app/Http/Controllers/ClipController.php`](file:///c:/Development/laragon/www/youtube-clipper-demo/app/Http/Controllers/ClipController.php)
3. **UI SPA Main Page**: [`resources/js/Pages/Clipper.vue`](file:///c:/Development/laragon/www/youtube-clipper-demo/resources/js/Pages/Clipper.vue)
4. **Interactive Trimmer Component**: [`resources/js/Components/DualRangeTrimmer.vue`](file:///c:/Development/laragon/www/youtube-clipper-demo/resources/js/Components/DualRangeTrimmer.vue)
