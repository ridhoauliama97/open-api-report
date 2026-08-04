# Dokumentasi Endpoint API Ascend Shared Laporan Customer Over Limit

Dokumen ini berisi endpoint internal untuk test/render laporan **Laporan Customer Over Limit** Ascend yang memakai Blade shared.

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

### Laporan Customer Over Limit

`POST /api/internal/ascends/shared/custom-report/customer-over-limit/pdf`

**Route name:** `api.internal.ascends.shared.custom-report.customer-over-limit.pdf`

**Controller:** `AscendXmlTestController::apiSharedCustomReportCustomerOverLimitPdf`

**Deskripsi:** Laporan rekap piutang per customer terhadap batas kredit (credit limit), dikelompokkan berdasarkan umur hari (aging) dari invoice yang belum lunas.

## Input

Parameter field utama:

- `xml_file`: file XML dari Ascend (contoh `Customer Over Limit.xml`).
- `DB_CompanyName`: nama/kode perusahaan, contoh `RU`, `GSU`, atau `UC`.
- `Sys_Username`: nama user print, contoh `Ridho`.

Input XML alternatif yang tetap diterima:

- field `xml` berisi string XML
- raw XML body dengan `Content-Type: application/xml`

Fallback kompatibilitas:

- `company`: fallback internal/test jika `DB_CompanyName` belum dikirim.

> Catatan: laporan ini tidak menggunakan filter tanggal `StartDate`/`EndDate`. Laporan disajikan sebagai snapshot per tanggal invoice terakhir pada data (as-of date).

Contoh `multipart/form-data`:

```text
DB_CompanyName=GSU
Sys_Username=Ridho
xml_file=Customer Over Limit.xml
```

## Response Sukses

- `200 application/pdf`
- `Content-Disposition: attachment`

Title yang tampil di halaman PDF:

```text
Laporan Customer Over Limit
```

Subtitle (as-of date diambil dari `InvoiceDate` terbesar pada XML):

```text
Per Tanggal : 04-Agt-26
```

Filename PDF:

```text
Laporan Customer Over Limit {company}.pdf
```

## Response Gagal

- `422 application/json` jika XML kosong, tidak valid, atau tidak bisa diproses.
- `422 application/json` jika `DB_CompanyName` tidak dikirim.

## Struktur Laporan

Baris XML dikelompokkan berdasarkan `CustomerName` (urut alfabetis) menjadi satu baris ringkasan per customer, ditutup dengan baris `Total`.

| # | Kolom | Sumber / Formula |
|---|-------|------------------|
| 1 | Nama Customer | `{Table.CustomerName}` |
| 2 | Credit Limit | `{Table.CreditLimit}` |
| 3 | 1 - 30 Hari | sum `Hasil` jika `{Table.LamaHari}` 1 sampai 30, selain itu 0 |
| 4 | 31 - 60 Hari | sum `Hasil` jika `{Table.LamaHari}` 31 sampai 60, selain itu 0 |
| 5 | 61 - 90 Hari | sum `Hasil` jika `{Table.LamaHari}` 61 sampai 90, selain itu 0 |
| 6 | Over 90 | sum `Hasil` jika `{Table.LamaHari}` > 90, selain itu 0 |
| 7 | Tagihan | sum seluruh `Hasil` (termasuk baris `LamaHari` = 0) |

Formula yang dipakai:

- `Hasil` = `{Table.Total}` - `@Pembayaran`.
- `Pembayaran` = jika null maka 0, selain itu `{Table.Pembayaran}`.

Baris dengan `LamaHari` = 0 tidak masuk ke kelompok umur manapun, namun tetap dihitung pada kolom `Tagihan`.

Format A4 landscape dengan font Noto Serif. Angka ditampilkan sebagai bilangan bulat pembulatan dengan pemisah ribuan.

## Template Blade

Template Blade berada di:

```
resources/views/ascends/shared/custom_report/customer_over_limit/pdf.blade.php
```