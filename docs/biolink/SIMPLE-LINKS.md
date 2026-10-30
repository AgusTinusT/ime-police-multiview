# Tautan Sederhana — rancangan Sprint 6

Status: **rancangan, menunggu persetujuan** · 30 Okt 2026

## Masalah yang dilaporkan

1. **Urutan tidak berlaku.** Dashboard bisa memindahkan Instagram ke paling atas, tetapi
   `Pages/Public/BioLink.vue` mengelompokkan tautan ke section tetap
   (Donasi → Rekan → Sosial → Lainnya). `sort_order` hanya berlaku *di dalam* satu section,
   jadi hasil di halaman publik tidak sesuai harapan.
2. **Form terlalu banyak isian.** Judul, subjudul, tipe, ikon, label badge, sorot — officer
   bingung harus mengisi apa. Yang sebenarnya dibagikan hampir selalu: Saweria / Bagibagi /
   Trakteer / Tako, dan Instagram / TikTok / YouTube / Discord.
3. **Pratinjau bukan tampilan asli.** Pratinjau di dashboard adalah tiruan sederhana
   (`--preview-accent`), tanpa latar tema, gaya tombol, maupun ikon yang sebenarnya.
4. **Ikon verified** diganti SVG yang disediakan (svgrepo "verified-check").

## Keputusan (usulan)

| # | Keputusan | Alasan |
| :--- | :--- | :--- |
| K1 | **Satu daftar, urutan bebas.** Halaman publik menampilkan semua tautan URL dalam satu daftar persis menurut `sort_order`. Tidak ada lagi section per kategori. | Yang officer lihat di dashboard = yang tampil. Paling mudah dipahami. |
| K2 | **Donasi tetap terlihat menonjol** (warna `accent_2` tema), di posisi mana pun officer menaruhnya. Tidak ada lagi toggle "Sorot" dan label badge. | Warna membedakan, bukan posisi. |
| K3 | **Input = pilih platform + tempel link.** Nama dan ikon otomatis dari platform. Hanya platform "Lainnya" yang meminta nama. | Tiga hal saja: ikon, link, nama platform. |
| K4 | **Platform ditentukan dari daftar tetap** (`config/biolinks.php` → `platforms`). Domain link harus cocok dengan platform yang dipilih. Link yang ditempel juga dikenali otomatis (tempel `instagram.com/...` → Instagram terpilih). | Validasi lebih ketat & tetap sederhana. |
| K5 | **Rekan patroli** (rekomendasi officer) bukan tautan URL: dipindah ke kartu sendiri di bawah daftar tautan, tidak ikut diurutkan. | Menghindari campuran dua jenis data dalam satu daftar. |
| K6 | **Pratinjau memakai komponen publik yang sama** (`BioLinkView.vue`), dengan data form yang belum disimpan. | Pratinjau = hasil asli. |
| K7 | Kolom lama (`subtitle`, `badge_label`, `is_highlighted`, `icon`) **tidak dihapus** dulu; hanya tidak lagi diisi/ditampilkan. Hapus di sprint berikutnya bila sudah aman. | Migrasi kecil, mudah dibatalkan. |
| K8 | Konfirmasi password untuk menambah/mengubah/menghapus **donasi tetap ada**. | Keamanan finansial (T-03) tidak ikut disederhanakan. |

## Daftar platform awal

| key | Nama | Kategori | Domain |
| :--- | :--- | :--- | :--- |
| `saweria` | Saweria | donation | saweria.co |
| `bagibagi` | Bagibagi | donation | bagibagi.co |
| `trakteer` | Trakteer | donation | trakteer.id |
| `tako` | Tako | donation | tako.id *(cek domain asli sebelum rilis)* |
| `sociabuzz` | Sociabuzz | donation | sociabuzz.com |
| `instagram` | Instagram | social | instagram.com |
| `tiktok` | TikTok | social | tiktok.com |
| `youtube` | YouTube | social | youtube.com, youtu.be |
| `discord` | Discord | discord | discord.gg, discord.com |
| `twitch` | Twitch | social | twitch.tv |
| `x` | X | social | x.com, twitter.com |
| `facebook` | Facebook | social | facebook.com |
| `custom` | Lainnya | custom | bebas (tetap HTTPS + blocklist) |

Urutan tampilan di pemilih: donasi dulu, lalu 4 sosmed utama, lalu sisanya di "Lainnya…".

## Model data

- Kolom baru `officer_bio_links.platform` (string 32, nullable, index).
- `link_type` tetap disimpan, **diturunkan** dari kategori platform (`donation` / `social` / `custom`),
  sehingga audit, konfirmasi password, dan statistik lama tetap jalan. Discord tetap memakai link_type `discord`.
- `title` diisi otomatis = nama platform (kecuali `custom`).
- Backfill: tebak `platform` dari `url_host` untuk tautan lama; yang tidak cocok → `custom`; rekan patroli → `null`. Dilakukan di migration `2026_10_30_000001` lewat `AppSupportBioLinkPlatforms::detect()`.

## Task

| ID | Pelaksana | Task | Bergantung |
| :--- | :--- | :--- | :--- |
| S-01 | Claude | Registry platform + kolom `platform` + backfill + validasi controller | — |
| S-02 | Claude | `BioLinkView.vue` diekstrak dari halaman publik; satu daftar sesuai `sort_order`; rekan patroli di kartu sendiri | S-01 |
| S-03 | Claude | Form dashboard "pilih platform + link" dan pratinjau memakai `BioLinkView` | S-02, S-04 |
| S-04 | **Gemini** | `PlatformIcon.vue` — ikon merek per platform | — ([brief](tasks/S-04.md)) |
| S-05 | **Gemini** | `VerifiedBadge.vue` dari SVG yang disediakan | — ([brief](tasks/S-05.md)) |
| S-06 | **Gemini** | Test: urutan publik, validasi platform, backfill | S-01, S-02 ([brief](tasks/S-06.md)) |

S-04 dan S-05 bisa dikerjakan Gemini sekarang, paralel dengan S-01.
