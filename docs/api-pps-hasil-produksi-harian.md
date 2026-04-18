# Dokumentasi API Laporan Harian Produksi PPS

Dokumen ini menjelaskan akses endpoint API untuk seluruh laporan **harian produksi PPS** yang saat ini terdaftar di `routes/api.php`.

Tanggal acuan dokumen ini: **17 April 2026**.

## Base URL
- Lokal: `http://localhost:8000`
- Prefix API: `/api`

## Autentikasi
Semua endpoint di dokumen ini berada di group middleware:

```php
Route::middleware('report.jwt.claims')
```

Header minimal yang dikirim:

```http
Authorization: Bearer <jwt_token>
Accept: application/json
```

Catatan:
- Middleware menerima **JWT external** yang valid.
- Middleware juga masih mendukung **Sanctum personal access token** untuk first-party flow.
- Claim user yang dipakai controller footer PDF diambil dari:
  - `name`
  - `username`
  - `email`
  - `sub`

## Pola Endpoint Umum
Untuk setiap report harian produksi PPS yang sudah tersedia di API, pola endpoint-nya sama:

1. Preview JSON

```http
POST /api/reports/pps/{slug}
```

2. Download PDF

```http
GET|POST /api/reports/pps/{slug}/pdf
```

3. Health Check

```http
POST /api/reports/pps/{slug}/health
```

## Parameter Request
Secara umum, laporan harian produksi PPS memakai nomor dokumen produksi sebagai input tunggal.

Parameter yang dipakai:
- `no_produksi`
- `no_packing`
- `preview_pdf` opsional, hanya untuk endpoint PDF

## Daftar Endpoint API Yang Aktif

### 1. Laporan Harian Hasil Washing Produksi
- Path dasar: `/api/reports/pps/washing/washing-produksi`
- SP: `SP_LapHasilProduksiHarianWashing`
- Parameter utama: `no_produksi`
- Alias request yang diterima:
  - `no_produksi`
  - `NoProduksi`
- Endpoint:
  - `POST /api/reports/pps/washing/washing-produksi`
  - `GET|POST /api/reports/pps/washing/washing-produksi/pdf`
  - `POST /api/reports/pps/washing/washing-produksi/health`

Contoh body:

```json
{
  "no_produksi": "C.0000002831"
}
```

### 2. Laporan Harian Hasil Broker Produksi
- Path dasar: `/api/reports/pps/broker/broker-produksi`
- SP: `SP_LapHasilProduksiHarianBroker`
- Parameter utama: `no_produksi`
- Endpoint:
  - `POST /api/reports/pps/broker/broker-produksi`
  - `GET|POST /api/reports/pps/broker/broker-produksi/pdf`
  - `POST /api/reports/pps/broker/broker-produksi/health`

Contoh body:

```json
{
  "no_produksi": "B.0000001234"
}
```

### 3. Laporan Harian Hasil Crusher Produksi
- Path dasar: `/api/reports/pps/crusher/crusher-produksi`
- SP: `SP_LapHasilProduksiHarianCrusher`
- Parameter utama: `no_produksi`
- Alias request yang diterima:
  - `no_produksi`
  - `NoCrusherProduksi`
- Endpoint:
  - `POST /api/reports/pps/crusher/crusher-produksi`
  - `GET|POST /api/reports/pps/crusher/crusher-produksi/pdf`
  - `POST /api/reports/pps/crusher/crusher-produksi/health`

Contoh body:

```json
{
  "NoCrusherProduksi": "CR.0000000001"
}
```

### 4. Laporan Harian Hasil Gilingan Produksi
- Path dasar: `/api/reports/pps/gilingan/gilingan-produksi`
- SP: `SP_LapHasilProduksiHarianGilingan`
- Parameter utama: `no_produksi`
- Alias request yang diterima:
  - `no_produksi`
  - `NoProduksi`
- Endpoint:
  - `POST /api/reports/pps/gilingan/gilingan-produksi`
  - `GET|POST /api/reports/pps/gilingan/gilingan-produksi/pdf`
  - `POST /api/reports/pps/gilingan/gilingan-produksi/health`

Contoh body:

```json
{
  "NoProduksi": "G.0000000001"
}
```

### 5. Laporan Harian Hasil Hot Stamping Produksi
- Path dasar: `/api/reports/pps/inject/hot-stamping/hot-stamping-produksi`
- SP: `SP_LapHasilProduksiHarianHotStamping`
- Parameter utama: `no_produksi`
- Endpoint:
  - `POST /api/reports/pps/inject/hot-stamping/hot-stamping-produksi`
  - `GET|POST /api/reports/pps/inject/hot-stamping/hot-stamping-produksi/pdf`
  - `POST /api/reports/pps/inject/hot-stamping/hot-stamping-produksi/health`

Contoh body:

```json
{
  "no_produksi": "BH.0000000350"
}
```

### 6. Laporan Harian Hasil Inject Produksi
- Path dasar: `/api/reports/pps/inject/inject-produksi`
- SP: `SP_LapHasilProduksiHarianInject`
- Parameter utama: `no_produksi`
- Endpoint:
  - `POST /api/reports/pps/inject/inject-produksi`
  - `GET|POST /api/reports/pps/inject/inject-produksi/pdf`
  - `POST /api/reports/pps/inject/inject-produksi/health`

Contoh body:

```json
{
  "no_produksi": "S.0000033806"
}
```

### 7. Laporan Harian Hasil Pasang Kunci Produksi
- Path dasar: `/api/reports/pps/inject/pasang-kunci/pasang-kunci-produksi`
- SP: `SP_LapHasilProduksiHarianPasangKunci`
- Parameter utama: `no_produksi`
- Endpoint:
  - `POST /api/reports/pps/inject/pasang-kunci/pasang-kunci-produksi`
  - `GET|POST /api/reports/pps/inject/pasang-kunci/pasang-kunci-produksi/pdf`
  - `POST /api/reports/pps/inject/pasang-kunci/pasang-kunci-produksi/health`

Contoh body:

```json
{
  "no_produksi": "PK.0000000001"
}
```

## Endpoint Web-Only Yang Belum Tersedia Di API
Laporan berikut **sudah ada route web-nya** di `routes/web.php`, tetapi **belum didaftarkan** di `routes/api.php`:

### 1. Laporan Harian Hasil Mixer Produksi
- Web path:
  - `/reports/pps/mixer/mixer-produksi`
- SP:
  - `SP_LapHasilProduksiHarianMixer`
- Parameter:
  - `no_produksi`

### 2. Laporan Harian Hasil Packing Produksi
- Web path:
  - `/reports/pps/inject/packing/packing-produksi`
- SP:
  - `SP_LapHasilProduksiHarianPacking`
- Parameter:
  - `no_packing`

### 3. Laporan Harian Hasil Spanner Produksi
- Web path:
  - `/reports/pps/inject/spanner/spanner-produksi`
- SP:
  - `SP_LapHasilProduksiHarianSpanner`
- Parameter:
  - `no_produksi`

Jika ingin laporan-laporan ini bisa diakses lewat API, route tambahan perlu didaftarkan di `routes/api.php`.

## Contoh Request

### A. Preview JSON

```bash
curl -X POST "http://localhost:8000/api/reports/pps/inject/inject-produksi" \
  -H "Authorization: Bearer <jwt_token>" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d "{\"no_produksi\":\"S.0000033806\"}"
```

Contoh response ringkas:

```json
{
  "message": "Preview laporan berhasil diambil.",
  "meta": {
    "no_produksi": "S.0000033806",
    "NoProduksi": "S.0000033806",
    "detail_row_count": 1,
    "source": "stored_procedure"
  },
  "data": {
    "header": {},
    "detail_rows": []
  }
}
```

### B. Download PDF

```bash
curl -X POST "http://localhost:8000/api/reports/pps/broker/broker-produksi/pdf" \
  -H "Authorization: Bearer <jwt_token>" \
  -H "Accept: application/pdf" \
  -H "Content-Type: application/json" \
  -d "{\"no_produksi\":\"E.0000005901\",\"preview_pdf\":true}" \
  --output broker-produksi.pdf
```

Catatan:
- `preview_pdf=true` akan mengubah `Content-Disposition` menjadi `inline`
- tanpa `preview_pdf`, respons PDF akan dikirim sebagai `attachment`

### C. Health Check

```bash
curl -X POST "http://localhost:8000/api/reports/pps/inject/inject-produksi/health" \
  -H "Authorization: Bearer <jwt_token>" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d "{\"no_produksi\":\"S.0000033806\"}"
```

Contoh response ringkas:

```json
{
  "message": "Struktur output SP_LapHasilProduksiHarianInject valid.",
  "meta": {
    "no_produksi": "S.0000033806",
    "NoProduksi": "S.0000033806"
  },
  "health": {
    "is_healthy": true
  }
}
```

## Format Error Umum

### 401 Unauthorized

Contoh:

```json
{
  "message": "Token tidak valid."
}
```

Kemungkinan penyebab:
- token tidak dikirim
- signature JWT salah
- token expired
- claim user tidak lengkap
- scope tidak memenuhi kebijakan report

### 422 Unprocessable Entity

Contoh validasi:

```json
{
  "message": "Validasi gagal.",
  "errors": {
    "no_produksi": [
      "The no produksi field is required."
    ]
  }
}
```

Contoh data report:

```json
{
  "message": "No produksi tidak ditemukan."
}
```

## Ringkasan Cepat

Endpoint API harian produksi PPS yang aktif saat ini:
- washing
- broker
- crusher
- gilingan
- hot stamping
- inject
- pasang kunci

Laporan yang masih web-only:
- mixer
- packing
- spanner

Dokumen ini mengikuti route dan request object yang aktif saat ini pada:
- [routes/api.php](/C:/xampp/htdocs/open-api-report/routes/api.php)
- [routes/web.php](/C:/xampp/htdocs/open-api-report/routes/web.php)
- [config/reports.php](/C:/xampp/htdocs/open-api-report/config/reports.php)
