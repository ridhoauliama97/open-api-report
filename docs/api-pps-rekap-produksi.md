# Dokumentasi API Laporan PPS (Rekap Produksi)

Dokumen ini menjelaskan endpoint API untuk seluruh laporan PPS pada grup `rekap-produksi`.

## Base URL
- Lokal: `http://localhost:8000`
- Prefix API: `/api`

## Autentikasi
Semua endpoint di bawah ini menggunakan middleware `AuthenticateReportJwtClaims`.

Kirim header:
```http
Authorization: Bearer <jwt_token>
Accept: application/json
```

## Parameter Request
Semua laporan menggunakan tanggal tunggal (1 parameter SP: `@PerTgl`):

- `TglAkhir` (date, format `YYYY-MM-DD`)

Alias yang diterima request object:
- `TglAkhir`
- `end_date`
- `report_date`

Jika tidak dikirim, aplikasi menggunakan default tanggal hari ini.

## Pola Endpoint Umum
Untuk setiap laporan tersedia 3 endpoint utama:

1. `POST /api/reports/pps/rekap-produksi/{slug}`
- Fungsi: preview data mentah (JSON)

2. `GET|POST /api/reports/pps/rekap-produksi/{slug}/pdf`
- Fungsi: generate PDF
- Response `Content-Type: application/pdf`

3. `POST /api/reports/pps/rekap-produksi/{slug}/health`
- Fungsi: cek kesehatan struktur output SP

## Daftar Laporan PPS

### 1) Inject FWIP
- Slug: `inject`
- SP: `SP_LapRekapProduksiInject_FWIP`
- Endpoints:
  - `POST /api/reports/pps/rekap-produksi/inject`
  - `GET|POST /api/reports/pps/rekap-produksi/inject/pdf`
  - `POST /api/reports/pps/rekap-produksi/inject/health`
- Alias kompatibilitas lama:
  - `POST /api/reports/pps/rekap-produksi/inject/preview`
  - `GET|POST /api/reports/pps/rekap-produksi/inject/download`

### 2) Inject BJ
- Slug: `inject-bj`
- SP: `SP_LapRekapProduksiInject_BJ`
- Endpoints:
  - `POST /api/reports/pps/rekap-produksi/inject-bj`
  - `GET|POST /api/reports/pps/rekap-produksi/inject-bj/pdf`
  - `POST /api/reports/pps/rekap-produksi/inject-bj/health`

### 3) Hot Stamping FWIP
- Slug: `hot-stamping-fwip`
- SP: `SP_LapRekapProduksiHotStamping_FWIP`
- Endpoints:
  - `POST /api/reports/pps/rekap-produksi/hot-stamping-fwip`
  - `GET|POST /api/reports/pps/rekap-produksi/hot-stamping-fwip/pdf`
  - `POST /api/reports/pps/rekap-produksi/hot-stamping-fwip/health`

### 4) Packing BJ
- Slug: `packing-bj`
- SP: `SP_LapRekapProduksiPacking_BJ`
- Endpoints:
  - `POST /api/reports/pps/rekap-produksi/packing-bj`
  - `GET|POST /api/reports/pps/rekap-produksi/packing-bj/pdf`
  - `POST /api/reports/pps/rekap-produksi/packing-bj/health`

### 5) Pasang Kunci FWIP
- Slug: `pasang-kunci-fwip`
- SP: `SP_LapRekapProduksiPKunci_FWIP`
- Endpoints:
  - `POST /api/reports/pps/rekap-produksi/pasang-kunci-fwip`
  - `GET|POST /api/reports/pps/rekap-produksi/pasang-kunci-fwip/pdf`
  - `POST /api/reports/pps/rekap-produksi/pasang-kunci-fwip/health`

### 6) Spanner FWIP
- Slug: `spanner-fwip`
- SP: `SP_LapRekapProduksiSpanner_FWIP`
- Endpoints:
  - `POST /api/reports/pps/rekap-produksi/spanner-fwip`
  - `GET|POST /api/reports/pps/rekap-produksi/spanner-fwip/pdf`
  - `POST /api/reports/pps/rekap-produksi/spanner-fwip/health`

## Contoh Request

### A. Preview JSON
```bash
curl -X POST "http://localhost:8000/api/reports/pps/rekap-produksi/packing-bj" \
  -H "Authorization: Bearer <jwt_token>" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"TglAkhir":"2026-03-03"}'
```

Contoh response (ringkas):
```json
{
  "message": "Preview laporan berhasil diambil.",
  "meta": {
    "start_date": "2026-03-03",
    "end_date": "2026-03-03",
    "TglAwal": "2026-03-03",
    "TglAkhir": "2026-03-03",
    "total_rows": 13,
    "column_order": ["DimType", "ItemCode", "Jenis", "Pcs", "Berat", "IdWarehouse"]
  },
  "data": []
}
```

### B. Download PDF
```bash
curl -X POST "http://localhost:8000/api/reports/pps/rekap-produksi/packing-bj/pdf" \
  -H "Authorization: Bearer <jwt_token>" \
  -H "Accept: application/pdf" \
  -H "Content-Type: application/json" \
  -d '{"TglAkhir":"2026-03-03"}' \
  --output packing-bj.pdf
```

### C. Health Check
```bash
curl -X POST "http://localhost:8000/api/reports/pps/rekap-produksi/packing-bj/health" \
  -H "Authorization: Bearer <jwt_token>" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"TglAkhir":"2026-03-03"}'
```

Contoh response (ringkas):
```json
{
  "message": "Struktur output SP_LapRekapProduksiPacking_BJ valid.",
  "health": {
    "is_healthy": true,
    "expected_columns": ["DimType", "ItemCode", "Jenis", "Pcs", "Berat", "IdWarehouse"],
    "detected_columns": ["DimType", "ItemCode", "Jenis", "Pcs", "Berat", "IdWarehouse"],
    "missing_columns": [],
    "extra_columns": [],
    "row_count": 13
  }
}
```

## Error Umum

### 401 Unauthorized
Contoh:
```json
{ "message": "Signature token tidak valid." }
```
Penyebab umum:
- Token tidak dikirim
- Signature JWT tidak cocok dengan secret verifier
- Token expired / belum aktif

### 422 Unprocessable Entity
Contoh:
```json
{ "message": "Stored procedure laporan belum dikonfigurasi." }
```
Penyebab umum:
- Konfigurasi env report belum lengkap
- Nama SP / koneksi DB tidak sesuai

---
Dokumen ini mengikuti route yang terdaftar saat ini pada `routes/api.php`.
