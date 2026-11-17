# Deploy otomatis dengan GitHub Actions

Setiap push ke `main` men-deploy ke **production**, dan setiap push ke `staging` men-deploy ke **staging**. Deploy hanya jalan setelah CI lulus.

```
push ke main / staging
   │
   ▼
CI (.github/workflows/ci.yml)
   composer install, npm ci, npm run check (lint UI + build), php artisan test
   │  gagal → deploy tidak jalan
   ▼
Deploy (.github/workflows/deploy.yml)
   SSH ke VPS → cd <folder> → bash scripts/deploy.sh --branch <branch> [--maintenance]
```

`scripts/deploy.sh` di VPS menjalankan `git pull`, `composer install` dan `npm ci` (hanya bila lock file berubah), `npm run build`, `migrate`, cache, dan `queue:restart`, lalu reload PHP-FPM. Production memakai mode maintenance selama deploy. Pull request ke `main` atau `staging` hanya menjalankan CI.

## Secrets

Isi di **Settings → Secrets and variables → Actions → Secrets** pada repo ini:

| Name | Wajib | Isi |
|---|---|---|
| `VPS_HOST` | ya | IP atau domain VPS |
| `VPS_USER` | ya | user SSH yang menjalankan deploy |
| `VPS_SSH_KEY` | ya | private key khusus deploy (lihat di bawah) |
| `VPS_KNOWN_HOSTS` | ya | sidik jari server (lihat di bawah) |
| `VPS_PATH_PRODUCTION` | ya | folder aplikasi production di VPS, misalnya `/var/www/ime-police-multiview` |
| `VPS_PATH_STAGING` | tidak | folder staging. Kalau kosong, deploy staging dilewati dengan peringatan |
| `VPS_PORT` | tidak | port SSH bila bukan 22 |

Kalau Secret production belum lengkap, deploy **gagal (merah)** dan menyebut Secret mana yang belum diisi. Deploy tidak akan pernah terlihat sukses tanpa melakukan apa-apa.

### VPS_SSH_KEY

Jalankan di VPS sebagai `VPS_USER`. Buat satu key khusus untuk project ini, supaya bisa dicabut tanpa mengganggu project lain:

```bash
ssh-keygen -t ed25519 -N "" -C "deploy-ime-police" -f ~/.ssh/deploy_ime_police
cat ~/.ssh/deploy_ime_police.pub >> ~/.ssh/authorized_keys && chmod 600 ~/.ssh/authorized_keys
cat ~/.ssh/deploy_ime_police
```

Salin seluruh hasil `cat` terakhir, mulai dari `-----BEGIN OPENSSH PRIVATE KEY-----` sampai `-----END OPENSSH PRIVATE KEY-----`, ke `VPS_SSH_KEY`. Setelah tersimpan, hapus file private key dari VPS:

```bash
rm ~/.ssh/deploy_ime_police
```

Untuk mencabut akses project ini nanti:

```bash
sed -i '/deploy-ime-police$/d' ~/.ssh/authorized_keys
```

### VPS_KNOWN_HOSTS

Jalankan **di VPS**. `ssh-keyscan` bawaan Windows versi lama sering gagal dengan galat `unsupported KEX method`, jadi jangan jalankan dari Windows. Ganti `IP_VPS` dengan nilai yang persis sama dengan `VPS_HOST`:

```bash
for f in /etc/ssh/ssh_host_*_key.pub; do echo "IP_VPS $(cut -d' ' -f1,2 "$f")"; done
```

Salin semua baris hasilnya. Kalau SSH tidak di port 22, awal setiap barisnya ditulis `[IP_VPS]:PORT`. Bila project lain di VPS yang sama sudah punya Secret ini, isinya boleh disalin persis.

## Persiapan VPS (sekali saja)

- **Repo di VPS bisa `git fetch` tanpa password.** Cek dengan `git fetch origin main` di folder aplikasi.
- **`scripts/deploy.sh` di VPS sudah mengenal opsi `--branch`.** Cek dengan `bash scripts/deploy.sh --help`. Kalau keluar `Opsi tidak dikenal`, jalankan `git pull origin main` sekali secara manual.
- **Tidak ada file yang diedit langsung di VPS.** `git status` harus bersih. Skrip deploy otomatis mengembalikan `package-lock.json` dan `bootstrap/cache/.gitignore`, tapi menolak jalan kalau ada file lain yang diubah.
- **Opsional: reload PHP-FPM tanpa password**, supaya OPcache dikosongkan setelah deploy:

  ```bash
  echo "$USER ALL=(root) NOPASSWD: /usr/bin/systemctl reload php8.3-fpm" | sudo tee /etc/sudoers.d/ime-police-deploy
  sudo chmod 440 /etc/sudoers.d/ime-police-deploy && sudo visudo -c
  ```

## Menjalankan deploy

- Otomatis: push ke `main` atau `staging`.
- Manual: buka **Actions → Deploy → Run workflow**, lalu pilih environment.

Hasil dan log setiap deploy ada di tab **Actions**. Kalau CI gagal, deploy tidak jalan. Buka langkah yang merah untuk melihat test atau build mana yang gagal.
