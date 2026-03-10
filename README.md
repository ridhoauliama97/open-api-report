# Open API Report

## Ringkasan
Project ini adalah aplikasi Laravel untuk:
- Login user (web session)
- Validasi JWT dari backend existing untuk akses report API (mode microservice)
- Preview dan Generate Semua Laporan 

## Requirement
- PHP `^8.2`
- Composer
- Node.js + npm
<!-- - Database (disarankan SQL Server untuk stored procedure) -->

## Setup Project
1. Install dependency backend:
```bash
composer install
```
2. Install dependency frontend:
```bash
npm install
```
3. Buat file environment:
```bash
cp .env.example .env
```
4. Generate key aplikasi:
```bash
php artisan key:generate
```
5. Jalankan migrasi:
```bash
php artisan migrate
```
6. Jalankan aplikasi:
```bash
php artisan serve
```

## Konfigurasi `.env`
```env
Contoh :
MUTASI_BARANG_JADI_REPORT_DB_CONNECTION=${DB_CONNECTION}
MUTASI_BARANG_JADI_REPORT_PROCEDURE=SP_Mutasi_BarangJadi
MUTASI_BARANG_JADI_SUB_REPORT_PROCEDURE=SP_SubMutasi_BarangJadi
MUTASI_BARANG_JADI_REPORT_CALL_SYNTAX=exec
# MUTASI_BARANG_JADI_REPORT_QUERY=
# MUTASI_BARANG_JADI_SUB_REPORT_QUERY=
```

### JWT Token Policy (Microservice)
```env
REPORT_API_JWT_SECRET=${SECRET_KEY}
REPORT_API_JWT_CLOCK_SKEW_SECONDS=30
REPORT_API_JWT_SUBJECT_CLAIM=sub
REPORT_API_JWT_USERNAME_CLAIM=username
REPORT_API_JWT_NAME_CLAIM=name
REPORT_API_JWT_EMAIL_CLAIM=email
REPORT_API_ENFORCE_SCOPE=false
REPORT_API_REQUIRED_SCOPE=report:generate
```

## Web Flow
- Halaman report: `GET /reports/nama-laporan`
- Login web: `POST /login`
- Logout web: `POST /logout`
- Download PDF report (web): `POST /reports/nama-laporan/download`
- Preview report (web, JSON): `POST /reports/nama-laporan/preview`

## API Endpoint
OpenAPI schema:
- `GET /api/openapi.json`

Auth API (opsional untuk login lokal service ini):
- `POST /api/auth/register`
- `POST /api/auth/login`
- `POST /api/auth/logout`
- `POST /api/auth/refresh`
- `GET /api/auth/me`

Catatan payload auth lokal:
- `register`: `username`, `password`, `password_confirmation`
- `login`: `username`, `password`

Report API (perlu Bearer token):
- `POST /api/reports/nama-laporan`
- `GET|POST /api/reports/nama-laporan/pdf`
- `POST /api/reports/nama-laporan/health`

Catatan autentikasi report API:
- Endpoint report API menggunakan Bearer token JWT dari backend existing.
- JWT diverifikasi dengan HS256 + secret (`REPORT_API_JWT_SECRET` atau fallback `SECRET_KEY`).
- Claim minimal: `username` dan `exp`.
- Scope opsional dapat dipaksa via `REPORT_API_REQUIRED_SCOPE` + `REPORT_API_ENFORCE_SCOPE=true`.

### Integrasi Token dari Aplikasi Lain
Langsung kirim JWT dari backend existing sebagai Bearer token ke endpoint report. Service ini tidak perlu login ulang ke tabel user.

Contoh request dari aplikasi lain (Node.js):
```js
const token = "<jwt_dari_aplikasi_auth>";

const response = await fetch("http://report-service.local/api/reports/mutasi-barang-jadi", {
  method: "POST",
  headers: {
    "Content-Type": "application/json",
    Authorization: `Bearer ${token}`,
  },
  body: JSON.stringify({
    TglAwal: "2026-01-01",
    TglAkhir: "2026-01-31",
  }),
});

const data = await response.json();
```

Contoh request dari aplikasi lain (PHP/Laravel HTTP client):
```php
$response = Http::withToken($jwtToken)
    ->post('http://report-service.local/api/reports/mutasi-barang-jadi', [
        'TglAwal' => '2026-01-01',
        'TglAkhir' => '2026-01-31',
    ]);
```

Referensi detail integrasi JWT ada di `docs/jwt-cross-app-integration.md`.

## Contoh Penggunaan API
### 1) Preview report
```bash
curl -X POST http://127.0.0.1:8000/api/reports/mutasi-barang-jadi \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <jwt_dari_backend_existing>" \
  -d "{\"TglAwal\":\"2026-01-01\",\"TglAkhir\":\"2026-01-31\"}"
```

### 2) Generate PDF via API
```bash
curl -X POST http://127.0.0.1:8000/api/reports/mutasi-barang-jadi/pdf \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <jwt_dari_backend_existing>" \
  -d "{\"TglAwal\":\"2026-01-01\",\"TglAkhir\":\"2026-01-31\"}" \
  --output laporan-mutasi-barang-jadi.pdf
```

## Testing
Jalankan test:
```bash
php artisan test
```

## Export Struktur Database
Untuk memahami struktur database yang dipakai laporan stored procedure (tables, kolom, PK/FK, views, functions, procedures, parameter, dan dependency), jalankan:

```bash
php artisan db:export-structure sqlsrv
```

Jika ingin ikut mengekspor definisi SQL setiap stored procedure:

```bash
php artisan db:export-structure sqlsrv --with-definitions
```
