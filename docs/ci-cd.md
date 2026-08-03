# CI Setup

Project ini pakai GitHub Actions untuk CI (belum ada CD/auto-deploy).

## `.github/workflows/ci.yml`

Jalan otomatis di setiap **push** dan **pull request** ke branch `main`:

1. Checkout kode
2. Setup PHP 8.2 + Node 24
3. `composer install`
4. `php artisan key:generate` (pakai `.env.example` yang di-copy jadi `.env`)
5. `npm ci && npm run build`
6. Lint: `vendor/bin/pint --test`
7. Test: `php artisan test` (DB sqlite in-memory, sesuai `phpunit.xml`)

Kalau salah satu step gagal, workflow ditandai merah di tab **Actions** dan (kalau branch protection diaktifkan) PR tidak bisa di-merge.

### Opsional: Branch protection

Di **Settings → Branches → Add rule** untuk `main`, centang "Require status checks to pass before merging" lalu pilih job `Lint & Test`. Ini memastikan tidak ada kode yang gagal test/lint yang bisa masuk ke `main`.

## Deploy (belum di-setup)

Deploy otomatis ke VPS sengaja belum dibuat karena beberapa hal masih perlu disiapkan dulu:

- Akses SSH ke server (bukan port `5006` — itu port `php artisan serve`, bukan SSH)
- Cara proses app di-manage di server (idealnya systemd service atau Supervisor, bukan `php artisan serve` manual di terminal — biar bisa di-restart otomatis pas deploy dan tetap hidup kalau server reboot)

Kalau nanti dua hal itu sudah siap, tinggal bilang aja — tinggal tambah workflow `deploy.yml` yang SSH ke server, `git pull`, install dependency, build asset, migrate, lalu restart service app-nya.
