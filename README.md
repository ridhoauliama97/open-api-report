# Open API Report

## Ringkasan
Project ini adalah aplikasi Laravel untuk:
- Login user (web session + API JWT)
- Preview laporan mutasi barang jadi via API
- Preview laporan mutasi finger joint via API
- Preview laporan mutasi moulding via API
- Preview laporan mutasi laminating via API
- Preview laporan mutasi sanding via API
- Preview laporan mutasi s4s via API
- Preview laporan mutasi st via API
- Preview laporan mutasi cca akhir via API
- Preview laporan mutasi reproses via API
- Preview laporan mutasi kayu bulat via API
- Preview laporan mutasi kayu bulat v2 via API
- Preview laporan mutasi kayu bulat kgv2 via API
- Preview laporan rangkuman jumlah label input via API
- Preview laporan mutasi hasil racip via API
- Preview laporan label nyangkut via API
- Preview laporan saldo kayu bulat via API
- Generate PDF laporan mutasi barang jadi
- Generate PDF laporan mutasi finger joint
- Generate PDF laporan mutasi moulding
- Generate PDF laporan mutasi laminating
- Generate PDF laporan mutasi sanding
- Generate PDF laporan mutasi s4s
- Generate PDF laporan mutasi st
- Generate PDF laporan mutasi cca akhir
- Generate PDF laporan mutasi reproses
- Generate PDF laporan mutasi kayu bulat
- Generate PDF laporan mutasi kayu bulat v2
- Generate PDF laporan mutasi kayu bulat kgv2
- Generate PDF laporan rangkuman jumlah label input
- Generate PDF laporan mutasi hasil racip
- Generate PDF laporan label nyangkut
- Generate PDF laporan saldo kayu bulat

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

MUTASI_FINGER_JOINT_REPORT_DB_CONNECTION=${DB_CONNECTION}
MUTASI_FINGER_JOINT_REPORT_PROCEDURE=SP_Mutasi_FingerJoint
MUTASI_FINGER_JOINT_SUB_REPORT_PROCEDURE=SP_SubMutasi_FingerJoint
MUTASI_FINGER_JOINT_REPORT_CALL_SYNTAX=exec
# MUTASI_FINGER_JOINT_REPORT_QUERY=
# MUTASI_FINGER_JOINT_SUB_REPORT_QUERY=
# MUTASI_FINGER_JOINT_REPORT_EXPECTED_COLUMNS=Jenis,Awal,Masuk,Keluar,Akhir
# MUTASI_FINGER_JOINT_SUB_REPORT_EXPECTED_COLUMNS=Jenis,CCAkhir,S4S

MUTASI_MOULDING_REPORT_DB_CONNECTION=${DB_CONNECTION}
MUTASI_MOULDING_REPORT_PROCEDURE=SP_Mutasi_Moulding
MUTASI_MOULDING_SUB_REPORT_PROCEDURE=SP_SubMutasi_Moulding
MUTASI_MOULDING_REPORT_CALL_SYNTAX=exec
# MUTASI_MOULDING_REPORT_QUERY=
# MUTASI_MOULDING_SUB_REPORT_QUERY=
# MUTASI_MOULDING_REPORT_EXPECTED_COLUMNS=Jenis,Awal,Masuk,Keluar,Akhir
# MUTASI_MOULDING_SUB_REPORT_EXPECTED_COLUMNS=Jenis,BJ,CCAkhir,FJ,Laminating,Moulding,Reproses,S4S,Sanding,WIP

MUTASI_LAMINATING_REPORT_DB_CONNECTION=${DB_CONNECTION}
MUTASI_LAMINATING_REPORT_PROCEDURE=SP_Mutasi_Laminating
MUTASI_LAMINATING_SUB_REPORT_PROCEDURE=SP_SubMutasi_Laminating
MUTASI_LAMINATING_REPORT_CALL_SYNTAX=exec
# MUTASI_LAMINATING_REPORT_QUERY=
# MUTASI_LAMINATING_SUB_REPORT_QUERY=
# MUTASI_LAMINATING_REPORT_EXPECTED_COLUMNS=Jenis,Awal,Masuk,Keluar,Akhir
# MUTASI_LAMINATING_SUB_REPORT_EXPECTED_COLUMNS=Jenis,BJ,CCAkhir,FJ,Laminating,Moulding,Reproses,S4S,Sanding,WIP

MUTASI_SANDING_REPORT_DB_CONNECTION=${DB_CONNECTION}
MUTASI_SANDING_REPORT_PROCEDURE=SP_Mutasi_Sanding
MUTASI_SANDING_SUB_REPORT_PROCEDURE=SP_SubMutasi_Sanding
MUTASI_SANDING_REPORT_CALL_SYNTAX=exec
# MUTASI_SANDING_REPORT_QUERY=
# MUTASI_SANDING_SUB_REPORT_QUERY=
# MUTASI_SANDING_REPORT_EXPECTED_COLUMNS=Jenis,Awal,Masuk,Keluar,Akhir
# MUTASI_SANDING_SUB_REPORT_EXPECTED_COLUMNS=Jenis,BJ,CCAkhir,FJ,Laminating,Moulding,Reproses,S4S,Sanding,WIP

MUTASI_S4S_REPORT_DB_CONNECTION=${DB_CONNECTION}
MUTASI_S4S_REPORT_PROCEDURE=SP_Mutasi_S4S
MUTASI_S4S_SUB_REPORT_PROCEDURE=SP_SubMutasi_S4S
MUTASI_S4S_REPORT_CALL_SYNTAX=exec
# MUTASI_S4S_REPORT_QUERY=
# MUTASI_S4S_SUB_REPORT_QUERY=
# MUTASI_S4S_REPORT_EXPECTED_COLUMNS=Jenis,Awal,Masuk,Keluar,Akhir
# MUTASI_S4S_SUB_REPORT_EXPECTED_COLUMNS=Jenis,BJ,CCAkhir,FJ,Laminating,Moulding,Reproses,S4S,Sanding,WIP

MUTASI_ST_REPORT_DB_CONNECTION=${DB_CONNECTION}
MUTASI_ST_REPORT_PROCEDURE=SP_Mutasi_ST
# Opsional, isi hanya jika ada sub report:
# MUTASI_ST_SUB_REPORT_PROCEDURE=
MUTASI_ST_REPORT_CALL_SYNTAX=exec
# MUTASI_ST_REPORT_QUERY=
# MUTASI_ST_SUB_REPORT_QUERY=
# MUTASI_ST_REPORT_EXPECTED_COLUMNS=Jenis,Awal,Masuk,Keluar,Akhir
# MUTASI_ST_SUB_REPORT_EXPECTED_COLUMNS=Jenis

MUTASI_CCA_AKHIR_REPORT_DB_CONNECTION=${DB_CONNECTION}
MUTASI_CCA_AKHIR_REPORT_PROCEDURE=SP_Mutasi_CCAkhir
MUTASI_CCA_AKHIR_SUB_REPORT_PROCEDURE=SP_SubMutasi_CCAkhir
MUTASI_CCA_AKHIR_REPORT_CALL_SYNTAX=exec
# MUTASI_CCA_AKHIR_REPORT_QUERY=
# MUTASI_CCA_AKHIR_SUB_REPORT_QUERY=
# MUTASI_CCA_AKHIR_REPORT_EXPECTED_COLUMNS=Jenis,CCAkhirAwal,AdjOutputCCA,BSOutputCCA,CCAProdOutput,CCAMasuk,AdjInptCCA,BSInputCCA,FJProdInpt,MldProdinpt,S4SProdInpt,SandProdInpt,LMTProdInpt,CCAJual,CCAAkhir,PACKProdInpt,CCAInputCCA
# MUTASI_CCA_AKHIR_SUB_REPORT_EXPECTED_COLUMNS=Jenis,FJ,Laminating,Reproses,WIP,BJ,Sanding,CCAkhir

MUTASI_REPROSES_REPORT_DB_CONNECTION=${DB_CONNECTION}
MUTASI_REPROSES_REPORT_PROCEDURE=SP_Mutasi_Reproses
MUTASI_REPROSES_SUB_REPORT_PROCEDURE=SP_SubMutasi_Reproses
MUTASI_REPROSES_REPORT_CALL_SYNTAX=exec
# MUTASI_REPROSES_REPORT_QUERY=
# MUTASI_REPROSES_SUB_REPORT_QUERY=
# MUTASI_REPROSES_REPORT_EXPECTED_COLUMNS=Jenis,REPROAwal,AdjOutput,MLDOutput,PACKOutput,REPROKeluar,AdjInput,BSInput,CCAInput,LMTInput,MLDInput,S4SInput,SANDInput,REPROJual,ReprosesAkhir
# MUTASI_REPROSES_SUB_REPORT_EXPECTED_COLUMNS=

MUTASI_KAYU_BULAT_REPORT_DB_CONNECTION=${DB_CONNECTION}
MUTASI_KAYU_BULAT_REPORT_PROCEDURE=SP_Mutasi_KayuBulat
# Opsional, isi hanya jika ada sub report:
# MUTASI_KAYU_BULAT_SUB_REPORT_PROCEDURE=
MUTASI_KAYU_BULAT_REPORT_CALL_SYNTAX=exec
# MUTASI_KAYU_BULAT_REPORT_QUERY=
# MUTASI_KAYU_BULAT_SUB_REPORT_QUERY=
# MUTASI_KAYU_BULAT_REPORT_EXPECTED_COLUMNS=Jenis,Awal,Masuk,Keluar,Akhir
# MUTASI_KAYU_BULAT_SUB_REPORT_EXPECTED_COLUMNS=Jenis

MUTASI_KAYU_BULAT_V2_REPORT_DB_CONNECTION=${DB_CONNECTION}
MUTASI_KAYU_BULAT_V2_REPORT_PROCEDURE=SP_Mutasi_KayuBulatV2
# Opsional, isi hanya jika ada sub report:
# MUTASI_KAYU_BULAT_V2_SUB_REPORT_PROCEDURE=
MUTASI_KAYU_BULAT_V2_REPORT_CALL_SYNTAX=exec
# MUTASI_KAYU_BULAT_V2_REPORT_QUERY=
# MUTASI_KAYU_BULAT_V2_SUB_REPORT_QUERY=
# MUTASI_KAYU_BULAT_V2_REPORT_EXPECTED_COLUMNS=Jenis,Awal,Masuk,Keluar,Akhir
# MUTASI_KAYU_BULAT_V2_SUB_REPORT_EXPECTED_COLUMNS=Jenis

MUTASI_KAYU_BULAT_KGV2_REPORT_DB_CONNECTION=${DB_CONNECTION}
MUTASI_KAYU_BULAT_KGV2_REPORT_PROCEDURE=SP_Mutasi_KayuBulatKGV2
# Opsional, isi hanya jika ada sub report:
# MUTASI_KAYU_BULAT_KGV2_SUB_REPORT_PROCEDURE=
MUTASI_KAYU_BULAT_KGV2_REPORT_CALL_SYNTAX=exec
# MUTASI_KAYU_BULAT_KGV2_REPORT_QUERY=
# MUTASI_KAYU_BULAT_KGV2_SUB_REPORT_QUERY=
# MUTASI_KAYU_BULAT_KGV2_REPORT_EXPECTED_COLUMNS=Jenis,Awal,Masuk,Keluar,Akhir
# MUTASI_KAYU_BULAT_KGV2_SUB_REPORT_EXPECTED_COLUMNS=Jenis

RANGKUMAN_LABEL_INPUT_REPORT_DB_CONNECTION=${DB_CONNECTION}
RANGKUMAN_LABEL_INPUT_REPORT_PROCEDURE=SPWps_LapRangkumanJlhLabelInput
RANGKUMAN_LABEL_INPUT_REPORT_CALL_SYNTAX=exec
# RANGKUMAN_LABEL_INPUT_REPORT_QUERY=
# RANGKUMAN_LABEL_INPUT_REPORT_EXPECTED_COLUMNS=

MUTASI_HASIL_RACIP_REPORT_DB_CONNECTION=${DB_CONNECTION}
MUTASI_HASIL_RACIP_REPORT_PROCEDURE=SPWps_LapMutasiHasilRacip
MUTASI_HASIL_RACIP_REPORT_CALL_SYNTAX=exec
# MUTASI_HASIL_RACIP_REPORT_QUERY=
# MUTASI_HASIL_RACIP_REPORT_EXPECTED_COLUMNS=

SALDO_KAYU_BULAT_REPORT_DB_CONNECTION=${DB_CONNECTION}
SALDO_KAYU_BULAT_REPORT_PROCEDURE=SPWps_LapSaldoKayuBulat
SALDO_KAYU_BULAT_REPORT_CALL_SYNTAX=exec
# SALDO_KAYU_BULAT_REPORT_QUERY=
# SALDO_KAYU_BULAT_REPORT_EXPECTED_COLUMNS=NokayuBulat,DateCreate,DateUsage,Jenis,NmSupplier,Ton

LABEL_NYANGKUT_REPORT_DB_CONNECTION=${DB_CONNECTION}
LABEL_NYANGKUT_REPORT_PROCEDURE=SPWps_LapLabelNyangkut
LABEL_NYANGKUT_REPORT_CALL_SYNTAX=exec
# LABEL_NYANGKUT_REPORT_QUERY=
# LABEL_NYANGKUT_REPORT_EXPECTED_COLUMNS=
```

### JWT
```env
JWT_SECRET=isi_dengan_hasil_jwt_secret
```

## Web Flow
- Halaman report: `GET /reports/mutasi/barang-jadi`
- Halaman report: `GET /reports/mutasi/finger-joint`
- Halaman report: `GET /reports/mutasi/moulding`
- Halaman report: `GET /reports/mutasi/laminating`
- Halaman report: `GET /reports/mutasi/sanding`
- Halaman report: `GET /reports/mutasi/s4s`
- Halaman report: `GET /reports/mutasi/st`
- Halaman report: `GET /reports/mutasi/cca-akhir`
- Halaman report: `GET /reports/mutasi/reproses`
- Halaman report: `GET /reports/mutasi/kayu-bulat`
- Halaman report: `GET /reports/mutasi/kayu-bulat-v2`
- Halaman report: `GET /reports/mutasi/kayu-bulat-kgv2`
- Halaman report: `GET /reports/mutasi-hasil-racip`
- Halaman report: `GET /reports/rangkuman-label-input`
- Halaman report: `GET /reports/label-nyangkut`
- Halaman report: `GET /reports/kayu-bulat/saldo`
- Login web: `POST /login`
- Logout web: `POST /logout`
- Download PDF report (web): `POST /reports/mutasi/barang-jadi/download`
- Download PDF report (web): `POST /reports/mutasi/finger-joint/download`
- Download PDF report (web): `POST /reports/mutasi/moulding/download`
- Download PDF report (web): `POST /reports/mutasi/laminating/download`
- Download PDF report (web): `POST /reports/mutasi/sanding/download`
- Download PDF report (web): `POST /reports/mutasi/s4s/download`
- Download PDF report (web): `POST /reports/mutasi/st/download`
- Download PDF report (web): `POST /reports/mutasi/cca-akhir/download`
- Download PDF report (web): `POST /reports/mutasi/reproses/download`
- Download PDF report (web): `POST /reports/mutasi/kayu-bulat/download`
- Download PDF report (web): `POST /reports/mutasi/kayu-bulat-v2/download`
- Download PDF report (web): `POST /reports/mutasi/kayu-bulat-kgv2/download`
- Download PDF report (web): `POST /reports/mutasi-hasil-racip/download`
- Download PDF report (web): `POST /reports/rangkuman-label-input/download`
- Download PDF report (web): `POST /reports/label-nyangkut/download`
- Download PDF report (web): `POST /reports/kayu-bulat/saldo/download`
- Preview report (web, JSON): `POST /reports/mutasi/barang-jadi/preview`
- Preview report (web, JSON): `POST /reports/mutasi/finger-joint/preview`
- Preview report (web, JSON): `POST /reports/mutasi/moulding/preview`
- Preview report (web, JSON): `POST /reports/mutasi/laminating/preview`
- Preview report (web, JSON): `POST /reports/mutasi/sanding/preview`
- Preview report (web, JSON): `POST /reports/mutasi/s4s/preview`
- Preview report (web, JSON): `POST /reports/mutasi/st/preview`
- Preview report (web, JSON): `POST /reports/mutasi/cca-akhir/preview`
- Preview report (web, JSON): `POST /reports/mutasi/reproses/preview`
- Preview report (web, JSON): `POST /reports/mutasi/kayu-bulat/preview`
- Preview report (web, JSON): `POST /reports/mutasi/kayu-bulat-v2/preview`
- Preview report (web, JSON): `POST /reports/mutasi/kayu-bulat-kgv2/preview`
- Preview report (web, JSON): `POST /reports/mutasi-hasil-racip/preview`
- Preview report (web, JSON): `POST /reports/rangkuman-label-input/preview`
- Preview report (web, JSON): `POST /reports/label-nyangkut/preview`
- Preview report (web, JSON): `POST /reports/kayu-bulat/saldo/preview`

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
- `POST /api/reports/mutasi-finger-joint`
- `GET|POST /api/reports/mutasi-finger-joint/pdf`
- `POST /api/reports/mutasi-finger-joint/health`
- `POST /api/reports/mutasi-moulding`
- `GET|POST /api/reports/mutasi-moulding/pdf`
- `POST /api/reports/mutasi-moulding/health`
- `POST /api/reports/mutasi-laminating`
- `GET|POST /api/reports/mutasi-laminating/pdf`
- `POST /api/reports/mutasi-laminating/health`
- `POST /api/reports/mutasi-sanding`
- `GET|POST /api/reports/mutasi-sanding/pdf`
- `POST /api/reports/mutasi-sanding/health`
- `POST /api/reports/mutasi-s4s`
- `GET|POST /api/reports/mutasi-s4s/pdf`
- `POST /api/reports/mutasi-s4s/health`
- `POST /api/reports/mutasi-st`
- `GET|POST /api/reports/mutasi-st/pdf`
- `POST /api/reports/mutasi-st/health`
- `POST /api/reports/mutasi-cca-akhir`
- `GET|POST /api/reports/mutasi-cca-akhir/pdf`
- `POST /api/reports/mutasi-cca-akhir/health`
- `POST /api/reports/mutasi-reproses`
- `GET|POST /api/reports/mutasi-reproses/pdf`
- `POST /api/reports/mutasi-reproses/health`
- `POST /api/reports/mutasi-kayu-bulat`
- `GET|POST /api/reports/mutasi-kayu-bulat/pdf`
- `POST /api/reports/mutasi-kayu-bulat/health`
- `POST /api/reports/mutasi-kayu-bulat-v2`
- `GET|POST /api/reports/mutasi-kayu-bulat-v2/pdf`
- `POST /api/reports/mutasi-kayu-bulat-v2/health`
- `POST /api/reports/mutasi-hasil-racip`
- `GET|POST /api/reports/mutasi-hasil-racip/pdf`
- `POST /api/reports/mutasi-hasil-racip/health`
- `POST /api/reports/mutasi-kayu-bulat-kgv2`
- `GET|POST /api/reports/mutasi-kayu-bulat-kgv2/pdf`
- `POST /api/reports/mutasi-kayu-bulat-kgv2/health`
- `POST /api/reports/rangkuman-label-input`
- `GET|POST /api/reports/rangkuman-label-input/pdf`
- `POST /api/reports/rangkuman-label-input/health`
- `POST /api/reports/label-nyangkut`
- `GET|POST /api/reports/label-nyangkut/pdf`
- `POST /api/reports/label-nyangkut/health`
- `POST /api/reports/kayu-bulat/saldo`
- `GET|POST /api/reports/kayu-bulat/saldo/pdf`
- `POST /api/reports/kayu-bulat/saldo/health`

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
