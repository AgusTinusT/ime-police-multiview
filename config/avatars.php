<?php

/*
|--------------------------------------------------------------------------
| Foto Profil Perwira (tersimpan di object storage)
|--------------------------------------------------------------------------
|
| Foto profil pengganti (custom) yang diisi lewat tautan tidak lagi dipakai
| langsung sebagai hotlink: gambarnya diunduh sekali, divalidasi, lalu
| disimpan di disk di bawah (S3). Halaman memakai URL aplikasi
| /media/avatars/{file} yang mengalirkan file dari disk tersebut, sehingga
| foto tetap tampil walau tautan asalnya kedaluwarsa atau diblokir.
|
| Foto YouTube otomatis (bukan custom) tetap tautan dan disinkronkan oleh
| officer:sync-subscribers. Foto custom lama: php artisan officer:store-avatars
|
*/

return [
    'enabled' => (bool) env('AVATAR_STORE_ENABLED', true),
    'disk' => env('AVATAR_STORE_DISK', 's3'),
    'directory' => 'officer-avatars',

    // Batas unduhan gambar dari tautan.
    'max_kb' => (int) env('AVATAR_STORE_MAX_KB', 5120),
    'timeout' => 15,
];
