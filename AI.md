# AI Context & Architectural Guidelines: Automated YouTube Multiview Platform

Dokumen ini berisi konteks arsitektur, skema data, aturan bisnis, dan konvensi kode untuk pengembangan platform **Automated YouTube Multiview**. Gunakan aturan dan standar di bawah ini sebagai pedoman implementasi kode.

---

## 1. Ringkasan Proyek & Tujuan

Platform web untuk menampilkan agregasi siaran langsung (_live streams_) YouTube secara simultan dalam tampilan multi-grid.
Sistem mengkurasi siaran secara otomatis berdasarkan daftar channel atau kata kunci yang telah tersimpan di dalam basis data (bukan diinput manual oleh pengunjung).

---

## 2. Tech Stack & Dependensi Utama

- **Backend**: Laravel (PHP 8.2+) / Node.js (REST API, Schedulers/Cron, Queues)
- **Database & Cache**: PostgreSQL / MySQL + Redis (Caching stream aktif)
- **Frontend**: Vue.js / React / Next.js + Tailwind CSS
- **Integrasi Eksternal**: YouTube Data API v3, WebSub (PubSubHubbub), YouTube IFrame Player API

---

## 3. Aturan Arsitektur & Performa (Prinsip Wajib)

1. **Efisiensi Kuota YouTube API (Zero-Exhaustion Policy)**:
    - **DILARANG KERAS** melakukan pemanggilan YouTube Data API secara langsung dari frontend/client.
    - Frontend **HANYA** boleh mengonsumsi endpoint backend lokal (misal: `GET /api/v1/streams/active`).
    - Gunakan Background Worker / Scheduler dengan interval berkala (3–5 menit) untuk memeriksa status channel.
    - Gunakan caching layer (Redis) dengan TTL 60–180 detik untuk endpoint public active streams.
    - Prioritaskan validasi HTTP `GET / @handle/live` redirect scraper atau WebSub webhook sebelum menggunakan kuota `search.list` YouTube API v3.

2. **Performa Rendering & Audio Policy**:
    - Iframe YouTube wajib dimuat dengan parameter `autoplay=1&mute=1&enablejsapi=1`.
    - **Single Audio Policy**: Hanya izinkan 1 stream yang berstatus _unmuted_ pada satu waktu. Mengaktifkan suara pada satu player harus otomatis me-_mute_ player lainnya.
    - Gunakan CSS Grid responsif dengan opsi layout preset (`auto-fit`, `grid-2x2`, `grid-3x3`, dan `focus-mode` di mana 1 video dominan dan sisanya berukuran _thumbnail/mini_).

---

## 4. Skema Basis Data

### A. Tabel `channels` (Master Channel & Tag)

| Kolom        | Tipe               | Keterangan                                    |
| :----------- | :----------------- | :-------------------------------------------- |
| `id`         | UUID / BIGINT (PK) | Identifier unik                               |
| `channel_id` | VARCHAR(64)        | ID resmi YouTube (misal: `UC...`) / UNIQUE    |
| `handle`     | VARCHAR(100)       | Handle YouTube (misal: `@ChannelName`)        |
| `name`       | VARCHAR(255)       | Nama channel                                  |
| `category`   | VARCHAR(100)       | Kategori/Tag (e.g., `Gaming`, `News`, `Tech`) |
| `is_active`  | BOOLEAN            | Flag status monitoring (default: `true`)      |
| `created_at` | TIMESTAMP          | Waktu dibuat                                  |
| `updated_at` | TIMESTAMP          | Waktu pembaruan                               |

### B. Tabel `active_streams` (Data Siaran Aktif)

| Kolom            | Tipe               | Keterangan                      |
| :--------------- | :----------------- | :------------------------------ |
| `id`             | UUID / BIGINT (PK) | Identifier unik                 |
| `channel_id`     | VARCHAR(64) (FK)   | Relasi ke `channels.channel_id` |
| `video_id`       | VARCHAR(20)        | 11 karakter ID video YouTube    |
| `title`          | VARCHAR(255)       | Judul live stream               |
| `thumbnail_url`  | TEXT               | URL gambar thumbnail            |
| `viewer_count`   | INTEGER            | Jumlah penonton (opsional)      |
| `status`         | ENUM               | `['LIVE', 'UPCOMING', 'ENDED']` |
| `last_synced_at` | TIMESTAMP          | Waktu sinkronisasi terakhir     |

---

## 5. Standar Kontrak API Backend

### `GET /api/v1/streams/active`

- **Query Params**: `category` (opsional), `limit` (default: 12)
- **Response Format**:

```json
{
    "status": "success",
    "data": [
        {
            "video_id": "dQw4w9WgXcQ",
            "channel_name": "Channel Example",
            "title": "24/7 Coding & Chill Stream",
            "category": "Tech",
            "thumbnail": "[https://i.ytimg.com/vi/dQw4w9WgXcQ/hqdefault.jpg](https://i.ytimg.com/vi/dQw4w9WgXcQ/hqdefault.jpg)",
            "status": "LIVE",
            "live_chat_url": "[https://www.youtube.com/live_chat?v=dQw4w9WgXcQ&embed_domain=localhost](https://www.youtube.com/live_chat?v=dQw4w9WgXcQ&embed_domain=localhost)"
        }
    ],
    "meta": {
        "total_active": 1,
        "cached_until": "2026-08-14T12:20:00Z"
    }
}
```
