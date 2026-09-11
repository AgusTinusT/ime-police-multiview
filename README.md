# 🚔 IME ROLEPLAY - Police Division Tactical Multiview & Bodycam Command Center

A high-tech tactical CCTV and Axon-style Bodycam Multiview Command Center built specifically for the **GTA 5 Server IME ROLEPLAY Police Division** (LSPD, BCSO, SASP, SAPR, SWAT, AIR-1, Traffic & K9).

---

## 🌟 Key Features

1. **Law Enforcement Tactical Theme & Aesthetics**:
   - San Andreas Police Dispatch & Command Center dark interface.
   - Bodycam HUD overlay on stream feeds: Officer Name, Callsign (e.g. `1-ADAM-12`), Rank, Badge Number, Department Badge, `● REC` indicator, GPS Patrol Zone, and Real-Time Clock (WIB UTC+7).
2. **Comprehensive Department & Division Support**:
   - **LSPD** (Los Santos Police Department)
   - **BCSO** (Blaine County Sheriff's Office)
   - **SASP** (San Andreas State Police / State Troopers)
   - **SAPR** (San Andreas Park Rangers)
   - **SWAT** (Special Weapons & Tactics)
   - **AIR-1** (Air Support Division / Eagle)
   - **TRAFFIC** (Highway Patrol & Motor Unit)
   - **K9** (Canine Division)
3. **Admin Dispatcher Command Hub (`/admin/officers`)**:
   - Standalone, authenticated web portal for managing officer database.
   - Instant YouTube channel verification (fetches channel ID, avatar, and streamer name automatically).
   - Real-time stream synchronization & subscriber count refresh controls.
4. **Strict Single-Audio YouTube Policy**:
   - Enforces only **one unmuted stream at a time** via YouTube IFrame Player API.
   - Switching audio automatically mutes all other streams to prevent chaotic audio overlap.
   - Realistic Police Radio Roger Beep / Squelch sound effects on audio switch.
5. **Flexible Tactical Layouts**:
   - **Auto-Fit Grid**: Dynamically scales with stream count.
   - **2x2 Quad Patrol Grid**: 4 streams.
   - **3x3 Sector Command Grid**: 9 streams.
   - **4x4 Tactical Wall**: 16 streams.
   - **Focus Lead Mode (1+N)**: 1 primary large stream + patrol thumbnails.
6. **GTA V RP Tactical Toolkit**:
   - **10-Codes Quick Reference Drawer**: Interactive search & instant one-click copy for common GTA V RP radio codes (10-4, 10-8, 10-13, 10-80, 10-99, 11-99, Signal 100, Code 1/2/3).
   - **MDT Dispatch Incident Scratchpad**: Built-in notepad saved to local browser storage for active pursuit notes, suspect vehicle descriptions, plate numbers, and 10-20 coordinates.
   - **Ad-hoc Quick Feed Injector**: Instant addition of any YouTube live URL or video ID directly onto the grid.
   - **Emergency Signal 100 / Code 13 Priority Alert**: Pulsing top alert banner for server-wide emergency calls.
   - **10-7 Offline Officer Directory**: Full roster of registered officers with badges, ranks, and direct links to YouTube channels.

---

## 🛠️ Technology Stack

- **Backend**: Laravel 11.x (PHP 8.3 / 8.4)
- **Frontend**: Inertia.js + Vue 3 (Composition API) + Tailwind CSS + Vite
- **Database**: MySQL / SQLite compatible
- **Integrations**: YouTube IFrame API, Resilient Zero-Quota Concurrent Scraper, Google Analytics 4 (SPA)

---

## 💻 Essential Terminal Commands

### 1. Admin Management (Dispatcher Authentication)

Create or update an admin user to access the `/admin/officers` management portal:

```bash
# Interactive prompt:
php artisan make:admin

# Or with direct arguments:
php artisan make:admin --name="Chief Dispatcher" --email="admin@dispatch.com" --password="YourSecurePassword"
```

### 2. YouTube Synchronization & Scraping

- **Refresh All Officer Subscriber Counts (Road to 1K Subs)**:
  ```bash
  php artisan officer:sync-subscribers
  ```

- **Sync Active Live Streams (Crawls registered YouTube channels for active 10-8 patrol streams)**:
  ```bash
  php artisan officer:sync
  ```

- **Import Officers from JSON Seed / Roster**:
  ```bash
  php artisan officer:import
  ```

- **CLI Interactive Officer CRUD Manager**:
  ```bash
  # List all registered officers
  php artisan officer:manage list

  # Add a new officer via CLI
  php artisan officer:manage add

  # Delete an officer via CLI
  php artisan officer:manage delete
  ```

---

## 🚀 Installation & Local Development

### 1. Database Setup & Migration

```bash
composer install
php artisan migrate --seed
```

### 2. Build Frontend Assets

```bash
# Development hot-reload:
npm run dev

# Production build:
npm run build
```

### 3. Clear Caches & Optimization

```bash
php artisan optimize:clear
```

