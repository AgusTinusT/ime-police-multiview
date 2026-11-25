# Catatan Implementasi: Live Notifications, Settings & Active Bio-Links

Tanggal: **25 November 2026**  
Status: **Selesai & Live di Production (`main`)**

---

## 1. Fitur yang Diimplementasikan

### A. Notifikasi Perwira Live (LN-01 s/d LN-06)
- **Database & Model** (`LN-01`):
  - Tabel `officer_subscriptions` (`user_id`, `officer_id`, `notify_in_app`, `notify_web_push`)
  - Tabel `push_subscriptions` (`user_id`, `endpoint`, `endpoint_hash`, `public_key`, `auth_token`, `content_encoding`, `user_agent`, `last_used_at`)
  - Tabel `youtube_websub_subscriptions` (`officer_id`, `channel_id`, `callback_token`, `status`, `lease_seconds`, `expires_at`, `last_verified_at`, `last_notification_at`, `last_error`)
  - Tabel `notifications` (Laravel `DatabaseNotification`)
  - Kolom deduplikasi `active_streams.live_notified_at`
- **API Follow Perwira** (`LN-02`):
  - `GET /api/v1/subscriptions`
  - `POST /api/v1/officers/{officer}/subscribe` (idempotent, mendukung preferensi `notify_in_app` & `notify_web_push`)
  - `DELETE /api/v1/officers/{officer}/subscribe`
- **YouTube WebSub / PubSubHubbub** (`LN-03`):
  - `YouTubeWebSubService` & `YouTubeWebSubController` (`GET`/`POST /webhooks/youtube/websub/{officer}`, bebas CSRF di `bootstrap/app.php`)
  - Command `php artisan websub:sync` dijadwalkan setiap 6 jam di `routes/console.php`
  - Status terverifikasi di production: **47 channel `active`**
- **Verifikasi, Deduplikasi & In-App Notification** (`LN-04`):
  - `VerifyAndProcessLiveWebhookJob` memverifikasi status `LIVE` + `isPolicePatrolStream`
  - `LiveNotificationTriggerService` memakai `DB::transaction` + `lockForUpdate()` pada `active_streams.live_notified_at` agar webhook real-time & fallback `SyncOfficerStreamsJob` (5 menit) tidak pernah mengirim notifikasi ganda
  - `NotifyOfficerLiveJob` + `OfficerLiveNotification`
  - API: `GET /api/v1/notifications`, `POST /api/v1/notifications/{id}/read`, `POST /api/v1/notifications/read-all`
- **Web Push Backend (VAPID, Gratis)** (`LN-05`):
  - Menggunakan `minishlink/web-push` (`WebPushService`) dengan `TTL: 3600`, `urgency: high`, dan auto-cleanup otomatis untuk subscription status `404`/`410`
  - API: `GET /api/v1/push-subscriptions`, `GET /api/v1/push-subscriptions/config`, `POST /api/v1/push-subscriptions`, `DELETE /api/v1/push-subscriptions`, `DELETE /api/v1/push-subscriptions/{id}`, `POST /api/v1/push-subscriptions/test`
- **Frontend & Service Worker** (`LN-06` + perbaikan delivery):
  - `public/sw.js` (`push` + `notificationclick` + `vibrate` + ikon `/images/icons/icon-192.png`)
  - Disajikan lewat rute Laravel **`/service-worker`** (`Content-Type: application/javascript`, `Cache-Control: no-store`, `Service-Worker-Allowed: /`) agar tidak pernah di-cache 1 tahun oleh Nginx/Cloudflare
  - `usePushNotifications.js` otomatis menyinkronkan subscription browser aktif ke server saat load
  - `NotificationBell.vue` di `TacticalDashboardHeader.vue` + `OfficerSubscribeButton.vue` di `/officers`, `/watch/{videoId}`, `/@{slug}`, dan `/bio-link`

### B. Peningkatan Halaman Settings (`/settings`)
- **Tab Akun (`AccountSettings.vue` & `UpdateProfileInformationForm.vue`)**:
  - Badge status **Email Terverifikasi** / **Belum Verifikasi** di kartu identitas
  - Banner notifikasi status verifikasi email + tombol **Verifikasi Email** (`verification.send`)
- **Tab Notifikasi (`/settings?tab=notifications`, `NotificationSettings.vue`)**:
  - Toggle Web Push perangkat ini, badge status izin browser, tombol **Tes Notifikasi**, dan daftar **Perangkat Terdaftar** (dengan tombol hapus perangkat)
  - Daftar **Perwira yang Diikuti** dengan toggle kanal per-perwira (**Lonceng** & **Push**)
  - Pencarian & filter departemen (`LSPD`, `BCSO`, `SASP`, `SAPR`) untuk langsung mengikuti/berhenti mengikuti perwira

### C. Direktori Bio-Link Perwira Aktif (`/bio-link` & `/officers`)
- `BioLinkPromoController` & `BioLinkPromo.vue` menampilkan daftar seluruh bio-link perwira yang **aktif, sudah diklaim (`user_id` terisi), dan tidak di-suspend**, dilengkapi pencarian, filter departemen, status LIVE, jumlah tautan, tombol buka `/@{slug}`, dan tombol **Ikuti**.
- Panel detail di `/officers` menampilkan tombol shortcut `/@{slug}` jika perwira memiliki bio-link aktif.
- **Bug Fix**: Escape semua karakter `@` literal menjadi `{'@'}` pada `resources/js/locales/id.json` & `en.json` agar `vue-i18n` tidak crash (`Invalid linked format`) yang sebelumnya membuat `/bio-link` tampil kosong.

---

## 2. Konfigurasi Production (VPS)

### Variabel `.env` yang Sudah Dipasang
```env
YOUTUBE_WEBSUB_SECRET=<random-hex-64>
YOUTUBE_WEBSUB_LEASE_SECONDS=432000
VAPID_PUBLIC_KEY=<generated-public-key>
VAPID_PRIVATE_KEY=<generated-private-key>
VAPID_SUBJECT="mailto:admin@domain-anda.com"
```

### Supervisor & Verifikasi
- `police-sync-worker.conf` sudah menjalankan `--queue=sync,default`.
- Cek status WebSub di VPS:
  ```bash
  php artisan tinker --execute="dump(\App\Models\YouTubeWebSubSubscription::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'));"
  ```
- Cek header `/service-worker` (wajib `cf-cache-status: DYNAMIC` dan `no-store`):
  ```bash
  curl -sI https://policeduty.sasp-motionliferp.com/service-worker | grep -iE "http/|content-type|cache-control|service-worker-allowed|cf-cache-status"
  ```

---

## 3. Ide Pengembangan Berikutnya (Opsional)
- **Bot Personal (Telegram Bot / Discord DM)** memanfaatkan tabel `officer_subscriptions` yang sudah ada agar user menerima DM personal khusus perwira yang diikuti tanpa spam streamer lain.
- **Tab Preferensi di `/settings`** untuk mengelola daftar perwira disembunyikan (`user_blocked_officers`) dan bahasa antarmuka.
