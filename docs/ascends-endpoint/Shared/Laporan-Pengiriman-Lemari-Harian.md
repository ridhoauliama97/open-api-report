# API Endpoint: Laporan Pengiriman Lemari (Harian)

## Overview
Endpoint untuk generate PDF laporan pengiriman lemari harian berdasarkan data XML dari Ascends.

## Endpoint Details

### Method
`POST`

### URL Path
```
/api/internal/ascends/shared/custom-report/pengiriman-lemari-harian/pdf
```

### Route Name
`api.internal.ascends.shared.custom-report.pengiriman-lemari-harian.pdf`

## Request Parameters

### Headers
| Header        | Required | Description                                         |
| ------------- | -------- | --------------------------------------------------- |
| Authorization | Yes      | Bearer token (JWT or Sanctum personal access token) |
| Content-Type  | Yes      | multipart/form-data                                 |

### Form Fields
| Field          | Type   | Required | Description                             |
| -------------- | ------ | -------- | --------------------------------------- |
| xml_file       | File   | Yes      | File XML berisi data pengiriman lemari  |
| DB_CompanyName | String | Yes      | Nama perusahaan (GSU, RU, UC)           |
| Sys_Username   | String | No       | Username pengguna yang generate laporan |
| StartDate      | Date   | No       | Tanggal awal filter (YYYY-MM-DD)        |
| EndDate        | Date   | No       | Tanggal akhir filter (YYYY-MM-DD)       |

## Response

### Success Response
- **Status Code**: 200 OK
- **Content-Type**: application/pdf
- **Content-Disposition**: attachment; filename="Laporan Pengiriman Lemari (Harian) [Company].pdf"

### Error Responses
- **422 Unprocessable Entity**: Validasi gagal atau error processing
- **401 Unauthorized**: Token tidak valid atau expired

## Data Formula

### Grp Calculation
```
IF "PLASTIK KABINET PK" IN ItemName AND '3TX6P' IN ItemName THEN '3TX6P'
ELSE IF "PLASTIK KABINET PK" IN ItemName AND '4TX8P' IN ItemName THEN '4TX8P'
ELSE IF "PLASTIK KABINET PK" IN ItemName AND '4TX4P' IN ItemName THEN '4TX4P'
ELSE '-'
```

### Keterangan Filter
```
IF 'PROMO LEM' IN ItemName THEN 'NOT' (EXCLUDE FROM REPORT)
ELSE 'TAMPIL' (INCLUDE IN REPORT)
```

### Selection Criteria
- FamilyID harus 2879 atau 2892 (item lemari/kabinet)
- Keterangan harus 'TAMPIL' (exclude items with 'PROMO LEM')
- Tanggal dalam rentang StartDate dan EndDate (jika diberikan)

## Table Structure

| Column      | Width    | Alignment | Description                                   |
| ----------- | -------- | --------- | --------------------------------------------- |
| Grp         | 3%       | Center    | Kategori produk (3TX6P, 4TX8P, 4TX4P, atau -) |
| Nama Barang | 55%      | Left      | Nama item dengan prefix spasi                 |
| Day Columns | Variable | Right     | Jumlah qty per hari (01, 02, 03, dst)         |
| Total       | 5%       | Right     | Total qty keseluruhan                         |

## Sample Request (cURL)

```bash
curl -X POST "https://your-domain.com/api/internal/ascends/shared/custom-report/pengiriman-lemari-harian/pdf" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: multipart/form-data" \
  -F "xml_file=@/path/to/xml/file.xml" \
  -F "DB_CompanyName=GSU" \
  -F "Sys_Username=admin" \
  -F "StartDate=2026-07-01" \
  -F "EndDate=2026-07-31"
```

## Sample XML Structure

```xml
<?xml version="1.0" standalone="yes"?>
<NewDataSet>
  <Table>
    <No>0</No>
    <DateID>2026-07-01T00:00:00+07:00</DateID>
    <CondItemID>6012</CondItemID>
    <ItemCode>2.1.3.4.01.29</ItemCode>
    <ItemName>GRANDE PLASTIK KABINET PK 3004 4Tx8P SNOW (LP 005)</ItemName>
    <FamilyID>2879</FamilyID>
    <Specification>BIRU</Specification>
    <Qty>1.0000</Qty>
  </Table>
</NewDataSet>
```

## Implementation Files

- **Service**: `app/Services/Ascends/Shared/CustomReport/PengirimanLemariHarianReportService.php`
- **View**: `resources/views/ascends/shared/custom_report/pengiriman_lemari_harian/pdf.blade.php`
- **Controller**: `app/Http/Controllers/AscendXmlTestController.php::apiSharedCustomReportPengirimanLemariHarianPdf()`
- **Route**: `routes/api.php` line ~368

## Notes
- Report menggunakan orientasi landscape untuk menampung semua kolom
- Font size dikurangi menjadi 8px agar muat di satu halaman
- Footer otomatis menambahkan informasi tanggal generate dan nama user
- Row odd/even untuk readability tabel
