# Dokumentasi Endpoint API Ascend Shared Laporan Sales Return Per Customer Per Period

Dokumen ini berisi endpoint internal untuk test/render laporan **Laporan Sales Return Per Customer Per Period** Ascend yang memakai Blade shared.

- Base host: `http://192.168.10.100:5006`
- Prefix API: `/api`

## Konsep Shared

Template shared ini dipakai supaya struktur laporan bisa digunakan lintas perusahaan:

- `RU`
- `GSU`
- `UC`

Nama perusahaan pada title dan filename dibaca dari parameter field `DB_CompanyName`.
Nama user print pada footer dibaca dari parameter field `Sys_Username`.

## Endpoint

### Laporan Sales Return Per Customer Per Period

`POST /api/internal/ascends/shared/custom-report/sales-return-per-customer-per-period/pdf`

**Route name:** `api.internal.ascends.shared.custom-report.sales-return-per-customer-per-period.pdf`

**Controller:** `AscendXmlTestController::apiSharedCustomReportSalesReturnPerCustomerPerPeriodPdf`

**Deskripsi:** Laporan rekapitulasi retur penjualan per customer per periode bulan dengan kolom nilai per periode, Min, Max, dan Rata-Rata.

## Input

Parameter field utama:

- `xml_file`: file XML dari Ascend (contoh `Custom2048.xml`).
- `DB_CompanyName`: nama/kode perusahaan, contoh `RU`, `GSU`, atau `UC`.
- `Sys_Username`: nama user print, contoh `Ridho`.
- `StartDate`: tanggal awal periode (contoh `2026-01-01`).
- `EndDate`: tanggal akhir periode (contoh `2026-06-30`).

Contoh `multipart/form-data`:

```text
DB_CompanyName=GSU
Sys_Username=Ridho
StartDate=2026-01-01
EndDate=2026-06-30
xml_file=Custom2048.xml
```

## Response Sukses

- `200 application/pdf`
- `Content-Disposition: attachment`

Title yang tampil di halaman PDF:

```text
Laporan Sales Return Per Customer Per Period
```

Filename PDF:

```text
Laporan Sales Return Per Customer Per Period {company}.pdf
```

## Response Gagal

- `422 application/json` jika XML kosong, tidak valid, atau tidak bisa diproses.
- `422 application/json` jika `DB_CompanyName` tidak dikirim.

## Struktur Laporan

Laporan menampilkan tabel dengan orientasi **landscape** (A4 landscape):

1. **Customer Name** (diurutkan berdasarkan total NetTotal descending)
2. **Periode Bulan** (`01-2026` s/d `06-2026` dsb.)
3. **Min** (nilai minimum penjualan retur dalam periode)
4. **Max** (nilai maksimum penjualan retur dalam periode)
5. **Rata - Rata** (rata-rata nilai penjualan retur per periode)

## Template Blade

- Path view: `resources/views/ascends/shared/custom_report/sales_return_per_customer_per_period/pdf.blade.php`
- Footer: `@include('ascends.shared.partials.report-footer')`
