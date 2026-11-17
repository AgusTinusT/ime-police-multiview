# Kebijakan Keamanan

## Melaporkan celah keamanan

**Jangan membuka issue publik untuk celah keamanan.** Laporkan secara privat lewat **GitHub → tab Security → Report a vulnerability** (Private vulnerability reporting) di repo ini.

Sertakan dalam laporan:

- jenis celah dan halaman, endpoint, atau file yang terdampak;
- langkah untuk mereproduksi, atau bukti konsep;
- dampak yang mungkin terjadi, misalnya akses data pengguna atau mengambil alih akun.

Kami akan membalas secepatnya, memberi tahu perkembangan perbaikan, dan mencantumkan nama Anda di catatan perbaikan bila Anda bersedia.

## Cakupan

Kode di repo ini: aplikasi Laravel/Inertia, skrip deploy, dan workflow GitHub Actions.

Di luar cakupan:

- serangan DoS, spam, atau rekayasa sosial terhadap pengguna maupun admin;
- celah di layanan pihak ketiga (YouTube, Discord, penyedia hosting). Laporkan ke penyedianya;
- pengujian terhadap server production tanpa izin. Gunakan lingkungan lokal Anda sendiri.

## Untuk kontributor

- Jangan pernah meng-commit `.env`, kredensial, token, atau backup database. Kalau tidak sengaja ter-commit, segera beri tahu maintainer: kredensial itu harus diganti, bukan cukup dihapus dari commit berikutnya.
- Akun demo dari seeder (`password123`) hanya untuk lokal, dan seeder menolak membuatnya di production atau staging.
