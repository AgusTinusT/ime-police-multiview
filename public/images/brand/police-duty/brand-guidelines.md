# Panduan Penggunaan Logo — Police Duty

## 1. Identitas Inti — PD Metro Badge
Logo **Police Duty** menggabungkan siluet lencana kepolisian klasik bermahkota lengkung (*Metro Badge*) dengan inisial **PD** bersudut *chamfer* 45° yang tegas, satu bintang lencana di mahkota, dan *V-chevron* taktis di dasar perisai. Wordmark `POLICE DUTY` dibangun dari path geometris khusus (tanpa ketergantungan font) dengan titik aksen **Live Beacon Red** (`#EF4444`).

---

## 2. Varian & Kapan Memakainya

| Varian | File | Penggunaan |
| :--- | :--- | :--- |
| **Simbol Warna** | `masters/police-duty-symbol-color.svg` | Avatar Discord/YouTube, ikon aplikasi di atas dark mode `#020617` maupun latar terang |
| **Horizontal (Dark Mode)** | `masters/police-duty-horizontal-dark.svg` | Header web utama (`TacticalDashboardHeader.vue`), banner dark mode, overlay stream |
| **Horizontal (Light Mode)** | `masters/police-duty-horizontal-color.svg` | Dokumen, presentasi, media cetak latar terang |
| **Stacked (Dark / Light)** | `masters/police-duty-stacked-dark.svg` / `-color.svg` | Poster, thumbnail, layar pembuka, merchandise |
| **Small-Size Cut (Favicon)** | `masters/police-duty-symbol-small.svg` | Tab browser (`16–24 px`) — bintang & chevron dihilangkan, huruf `PD` diperbesar +22% |
| **Monokrom / Reversed** | `exports/symbol/police-duty-symbol-{black,white,mono-2563eb}.svg` | Stempel satu warna, bordir, watermark transparan |

---

## 3. Palet Warna Resmi

| Nama Token | HEX | RGB | Peran |
| :--- | :--- | :--- | :--- |
| **Tactical Blue** (`--primary`) | `#2563EB` | `37, 99, 235` | Warna utama perisai & teks `DUTY` (mode terang) |
| **Live Beacon Red** (`--live`) | `#EF4444` | `239, 68, 68` | Titik sinyal *live* pada wordmark |
| **Command Navy** (`--background`) | `#020617` | `2, 6, 23` | Latar dark mode utama & ubin app-icon |
| **Deep Slate** (`--card`) | `#0F172A` | `15, 23, 42` | Teks `POLICE` pada latar terang |
| **Signal White** (`--foreground`) | `#F1F5F9` / `#FFFFFF` | `241, 245, 249` / `255, 255, 255` | Teks & elemen dalam lencana di atas dark mode |

---

## 4. Ruang Kosong & Ukuran Minimum
- **Ruang kosong (*clear space*):** Sisakan jarak bebas minimal **1× lebar batang huruf `P`** (≈ 15% tinggi simbol) di keempat sisi logo — jangan menempelkan teks, tombol, atau tepi kartu terlalu rapat.
- **Ukuran minimum:**
  - **Simbol standar:** `24 px` (digital) / `10 mm` (cetak).
  - **Di bawah 24 px (`16–20 px`):** wajib gunakan `police-duty-symbol-small.svg` / `favicon.ico`.
  - **Lockup horizontal:** lebar minimum `120 px` agar wordmark `POLICE DUTY` tetap tajam.

---

## 5. Yang Harus Dihindari
- Jangan meregangkan atau mengubah rasio aspek logo.
- Jangan menambahkan *drop shadow*, *outer glow*, atau gradien pelangi di atas master vektor.
- Jangan menaruh warna merah `#EF4444` langsung menempel di atas biru `#2563EB` tanpa jarak/pemisah.
- Jangan mengganti wordmark dengan font sistem acak; selalu gunakan file SVG master yang sudah di-outline.
