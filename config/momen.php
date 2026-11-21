<?php

/*
|--------------------------------------------------------------------------
| Arsip Momen Aksi
|--------------------------------------------------------------------------
|
| Setiap Momen Aksi dipotong dari VOD YouTube (720p) dan disimpan di object
| storage, lalu file arsipnya dihapus setelah `archive_days` hari. Data
| momen (judul, detik mulai-selesai, officer, suka) tetap disimpan.
|
| Matikan (MOMEN_ARCHIVE_ENABLED=false) sampai S3 di server terbukti
| tersambung: php artisan storage:check-s3
|
*/

return [
    'archive_enabled' => (bool) env('MOMEN_ARCHIVE_ENABLED', false),
    'archive_disk' => env('MOMEN_ARCHIVE_DISK', 's3'),
    'archive_days' => (int) env('MOMEN_ARCHIVE_DAYS', 30),
    'archive_format' => 'MP4 720p',

    // A moment is retried this many times before it stays "failed".
    'archive_max_attempts' => 3,

    // Signed archive links handed to the browser stay valid this long.
    'archive_url_minutes' => 120,
];
