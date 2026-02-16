# Open API Report - Instruction

## Ringkasan
Project ini adalah aplikasi Laravel untuk:
- Login user (web session + API JWT)
- Preview laporan penjualan/mutasi via API
- Generate PDF laporan

## Requirement
- PHP `^8.2`
- Composer
- Node.js + npm
- Database (disarankan MySQL/SQL Server untuk stored procedure)

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

## Konfigurasi Penting `.env`
### Database laporan
```env
SALES_REPORT_DB_CONNECTION=${DB_CONNECTION}
SALES_REPORT_PROCEDURE=sp_sales_report
SALES_REPORT_CALL_SYNTAX=auto
# SALES_REPORT_QUERY=

MUTASI_CROSS_CUT_REPORT_DB_CONNECTION=${DB_CONNECTION}
MUTASI_CROSS_CUT_REPORT_PROCEDURE=sp_mutasi_cross_cut_report
MUTASI_CROSS_CUT_REPORT_CALL_SYNTAX=auto
# MUTASI_CROSS_CUT_REPORT_QUERY=

MUTASI_BARANG_JADI_REPORT_DB_CONNECTION=${DB_CONNECTION}
MUTASI_BARANG_JADI_REPORT_PROCEDURE=SP_Mutasi_BarangJadi
MUTASI_BARANG_JADI_SUB_REPORT_PROCEDURE=SP_SubMutasi_BarangJadi
MUTASI_BARANG_JADI_REPORT_CALL_SYNTAX=auto
# MUTASI_BARANG_JADI_REPORT_QUERY=
# MUTASI_BARANG_JADI_SUB_REPORT_QUERY=
```

Keterangan:
- `SALES_REPORT_CALL_SYNTAX=auto`:
  - SQL Server -> `EXEC`
  - selain SQL Server -> `CALL`
- Jika pakai SQLite/testing, gunakan query manual:
  - set `SALES_REPORT_CALL_SYNTAX=query`
  - isi `SALES_REPORT_QUERY`

### JWT
```env
JWT_SECRET=isi_dengan_hasil_jwt_secret
```

## Web Flow
- Halaman report: `GET /reports/sales`
- Halaman report: `GET /reports/mutasi/cross-cut`
- Halaman report: `GET /reports/mutasi/barang-jadi`
- Login web: `POST /login`
- Logout web: `POST /logout`
- Download PDF report (web): `POST /reports/sales/download`
- Download PDF report (web): `POST /reports/mutasi/cross-cut/download`
- Download PDF report (web): `POST /reports/mutasi/barang-jadi/download`

Catatan:
- Setelah login web berhasil, akan muncul notifikasi toast.
- PDF mencetak nama user yang sedang login.

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
- `POST /api/reports/sales`
- `POST /api/reports/sales/pdf`
- `POST /api/reports/mutasi-cross-cut`
- `POST /api/reports/mutasi-cross-cut/pdf`
- `POST /api/reports/mutasi-barang-jadi`
- `POST /api/reports/mutasi-barang-jadi/pdf`

## Contoh Penggunaan API
### 1) Login
```bash
curl -X POST http://127.0.0.1:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d "{\"email\":\"user@example.com\",\"password\":\"secret123\"}"
```

### 2) Preview report
```bash
curl -X POST http://127.0.0.1:8000/api/reports/sales \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <access_token>" \
  -d "{\"start_date\":\"2026-01-01\",\"end_date\":\"2026-01-31\"}"
```

### 3) Generate PDF via API
```bash
curl -X POST http://127.0.0.1:8000/api/reports/sales/pdf \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <access_token>" \
  -d "{\"start_date\":\"2026-01-01\",\"end_date\":\"2026-01-31\"}" \
  --output laporan.pdf
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

Output akan tersimpan di:
- `storage/app/private/db-structure/*_summary.json`
- `storage/app/private/db-structure/*_tables.json`
- `storage/app/private/db-structure/*_views.json`
- `storage/app/private/db-structure/*_functions.json`
- `storage/app/private/db-structure/*_procedures.json`

Jika ingin ikut mengekspor definisi SQL setiap stored procedure:

```bash
php artisan db:export-structure sqlsrv --with-definitions
```
