# Open API Report

## Ringkasan
Project ini adalah aplikasi Laravel untuk:
- Login user (web session + API JWT)
- Preview laporan mutasi barang jadi via API
- Generate PDF laporan mutasi barang jadi

## Requirement
- PHP `^8.2`
- Composer
- Node.js + npm
- Database (disarankan SQL Server untuk stored procedure)

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
5. Generate JWT secret:
```bash
php artisan jwt:secret
```
6. Jalankan migrasi:
```bash
php artisan migrate
```
7. Jalankan aplikasi:
```bash
php artisan serve
```

## Konfigurasi `.env`
```env
MUTASI_BARANG_JADI_REPORT_DB_CONNECTION=${DB_CONNECTION}
MUTASI_BARANG_JADI_REPORT_PROCEDURE=SP_Mutasi_BarangJadi
MUTASI_BARANG_JADI_SUB_REPORT_PROCEDURE=SP_SubMutasi_BarangJadi
MUTASI_BARANG_JADI_REPORT_CALL_SYNTAX=exec
# MUTASI_BARANG_JADI_REPORT_QUERY=
# MUTASI_BARANG_JADI_SUB_REPORT_QUERY=
```

### JWT
```env
JWT_SECRET=isi_dengan_hasil_jwt_secret
```

## Web Flow
- Halaman report: `GET /reports/mutasi/barang-jadi`
- Login web: `POST /login`
- Logout web: `POST /logout`
- Download PDF report (web): `POST /reports/mutasi/barang-jadi/download`
- Preview report (web, JSON): `POST /reports/mutasi/barang-jadi/preview`

## API Endpoint
OpenAPI schema:
- `GET /api/openapi.json`

Auth JWT:
- `POST /api/auth/register`
- `POST /api/auth/login`
- `POST /api/auth/logout`
- `POST /api/auth/refresh`
- `GET /api/auth/me`

Report API (perlu Bearer token):
- `POST /api/reports/mutasi-barang-jadi`
- `GET|POST /api/reports/mutasi-barang-jadi/pdf`
- `POST /api/reports/mutasi-barang-jadi/health`

Catatan autentikasi report API:
- Endpoint report API memvalidasi JWT berdasarkan signature + claim token.
- Service laporan tidak melakukan lookup user ke database untuk request report API.
- Bisa batasi issuer/audience/scope melalui:
  - `REPORT_JWT_TRUSTED_ISSUERS` (comma separated)
  - `REPORT_JWT_TRUSTED_AUDIENCES` (comma separated)
  - `REPORT_JWT_REQUIRED_SCOPE` (contoh: `report:generate`)
  - `REPORT_JWT_SCOPE_CLAIM` (default: `scope`)

### Integrasi Token dari Aplikasi Lain
1. Tentukan algoritma token yang dipakai issuer:
   - `HS256`: samakan `JWT_SECRET` antara issuer dan service laporan.
   - `RS256/ES256`: set `JWT_ALGO` + `JWT_PUBLIC_KEY` di service laporan.
2. Standarkan claim token minimal:
   - subject: `sub` (atau ubah via `REPORT_JWT_SUBJECT_CLAIM`)
   - nama user: `name`
   - email user: `email`
   - scope report: `report:generate` (jika enforce scope)
3. Set policy trust di service laporan:
   - `REPORT_JWT_TRUSTED_ISSUERS`
   - `REPORT_JWT_TRUSTED_AUDIENCES`
   - `REPORT_JWT_REQUIRED_SCOPE`
4. (Opsional) agar token dari endpoint auth project ini langsung kompatibel:
   - `REPORT_JWT_ISSUED_AUDIENCE`
   - `REPORT_JWT_ISSUED_SCOPE`

### Contoh Penerapan dari Aplikasi Lain
Contoh payload JWT dari aplikasi lain yang akan diterima service report:
```json
{
  "iss": "https://auth.company.local",
  "aud": ["open-api-report"],
  "sub": "user-uuid-123",
  "name": "Budi Santoso",
  "email": "budi@company.local",
  "scope": "report:generate profile:read",
  "iat": 1760000000,
  "nbf": 1760000000,
  "exp": 1760003600
}
```

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

Referensi detail integrasi ada di `docs/jwt-cross-app-integration.md`.

## Contoh Penggunaan API
### 1) Login
```bash
curl -X POST http://127.0.0.1:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d "{\"email\":\"user@example.com\",\"password\":\"secret123\"}"
```

### 2) Preview report
```bash
curl -X POST http://127.0.0.1:8000/api/reports/mutasi-barang-jadi \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <access_token>" \
  -d "{\"TglAwal\":\"2026-01-01\",\"TglAkhir\":\"2026-01-31\"}"
```

### 3) Generate PDF via API
```bash
curl -X POST http://127.0.0.1:8000/api/reports/mutasi-barang-jadi/pdf \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <access_token>" \
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
