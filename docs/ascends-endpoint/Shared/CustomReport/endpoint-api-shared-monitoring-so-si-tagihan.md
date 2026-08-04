# Dokumentasi Endpoint API Ascend Shared Laporan Monitoring SO - SI - Tagihan

Dokumen ini berisi endpoint internal untuk test/render laporan **Laporan Monitoring SO - SI - Tagihan** Ascend yang memakai Blade shared.

- Base host: `http://192.168.10.100:5006`
- Prefix API: `/api`

## Konsep Shared

Template shared ini dipakai supaya struktur Blade laporan bisa digunakan lintas perusahaan:

- `RU`
- `GSU`
- `UC`

Nama perusahaan pada title dan filename dibaca dari parameter field `DB_CompanyName`.
Nama user print pada footer dibaca dari parameter field `Sys_Username`.

## Endpoint

### Laporan Monitoring SO - SI - Tagihan

`POST /api/internal/ascends/shared/custom-report/monitoring-so-si-tagihan/pdf`

**Route name:** `api.internal.ascends.shared.custom-report.monitoring-so-si-tagihan.pdf`

**Controller:** `AscendXmlTestController::apiSharedCustomReportMonitoringSoSiTagihanPdf`

**Deskripsi:** Laporan monitoring Sales Order (SO), Sales Invoice (SI), dan Tagihan per baris SO/SI, menampilkan tanggal SO/Inv serta lama hari menuju SI dan menuju tagihan, plus status pelunasan.

## Input

Parameter field utama:

- `xml_file`: file XML dari Ascend (contoh `Monitoring SO SI dan Tagihan.xml`).
- `DB_CompanyName`: nama/kode perusahaan, contoh `RU`, `GSU`, atau `UC`.
- `Sys_Username`: nama user print, contoh `Ridho`.
- `StartDate`: tanggal awal periode (contoh `2026-07-01`).
- `EndDate`: tanggal akhir periode (contoh `2026-07-31`).

Input XML alternatif yang tetap diterima:

- field `xml` berisi string XML
- raw XML body dengan `Content-Type: application/xml`

Fallback kompatibilitas:

- `company`: fallback internal/test jika `DB_CompanyName` belum dikirim.
- `start_date` + `end_date`: alias untuk `StartDate` + `EndDate`.

Contoh `multipart/form-data`:

```text
DB_CompanyName=GSU
Sys_Username=Ridho
StartDate=2026-07-01
EndDate=2026-07-31
xml_file=Monitoring SO SI dan Tagihan.xml
```

## Response Sukses

- `200 application/pdf`
- `Content-Disposition: attachment`

Title yang tampil di halaman PDF:

```text
Laporan Monitoring SO - SI - Tagihan
```

Subtitle (periode):

```text
Dari 01-Jul-26 s/d 31-Jul-26
```

Filename PDF:

```text
Laporan Monitoring SO - SI - Tagihan {company}.pdf
```

## Response Gagal

- `422 application/json` jika XML kosong, tidak valid, atau tidak bisa diproses.
- `422 application/json` jika `DB_CompanyName` tidak dikirim.
- `422 application/json` jika tidak ada data dalam rentang tanggal yang dipilih.

## Struktur Laporan

Tabel flat tanpa pengelompokan, diurutkan sesuai urutan baris pada XML (descending sesuai source). Kolom pada tabel:

| # | Kolom | Sumber / Formula |
|---|-------|------------------|
| 1 | Nama Customer | `{Table.CustomerName}` |
| 2 | No. SO | `{Table.SONumber}` |
| 3 | SO Date (Hr) | `{Table.SODate}` |
| 4 | No. Invoice | `{Table.InvoiceNumber}` |
| 5 | Inv Date | `{Table.InvoiceDate}` |
| 6 | SO Ke SI (Hr) | `sosi = {Table.SO-SI}` |
| 7 | Date Pelunas | `{Table.DateV}` |
| 8 | SI Ke Tgh (Hr) | `sosi 2` → jika `SI-TGH` null maka `printdate - InvoiceDate`, selain itu `SI-TGH` |
| 9 | Lunas | `Ket` → jika `Table.Ket` null atau mengandung `Belum` maka `No`, selain itu `Yes` |

- Kolom 3 (`{Table.SODate}` dan kolom 7 (`Date Pelunas`) ditampilkan sebagai tanggal `dd-MMM-yy` menggunakan locale Indonesia.
- Kolom 9 (`Lunas`) dihitung dari formula `Ket`: `if isnull({Table.Ket}) or 'Belum' in {Table.Ket} then 'No' else 'Yes'`.
- Kolom 8 (`SI Ke Tgh`) dihitung dari formula `sosi 2`.

Filter baris berdasarkan `{Table.SODate}` antara `StartDate` dan `EndDate` (inklusif).

Format A4 landscape dengan font Noto Serif.

## Template Blade

Template Blade berada di:

```
resources/views/ascends/shared/custom_report/monitoring_so_si_tagihan/pdf.blade.php
```