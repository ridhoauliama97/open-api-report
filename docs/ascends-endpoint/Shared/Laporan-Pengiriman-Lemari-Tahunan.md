# API Endpoint: Laporan Pengiriman Lemari (Tahunan)

## Overview
Endpoint untuk generate PDF laporan pengiriman lemari tahunan berdasarkan data XML dari Ascends.

## Endpoint Details

### Method
`POST`

### URL Path
```
/api/internal/ascends/shared/custom-report/pengiriman-lemari-tahunan/pdf
```

### Route Name
`api.internal.ascends.shared.custom-report.pengiriman-lemari-tahunan.pdf`

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
| StartDate      | Date   | No       | Tanggal/tahun awal filter (YYYY-MM-DD)  |

## Response

### Success Response
- **Status Code**: 200 OK
- **Content-Type**: application/pdf
- **Content-Disposition**: attachment; filename="Laporan Pengiriman Lemari (Tahunan) [Company].pdf"

### Error Responses
- **422 Unprocessable Entity**: Validasi gagal atau error processing
- **401 Unauthorized**: Token tidak valid atau expired

## Data Formula

### Grp Calculation
```
IF "PK.53003" IN ItemName OR "PK 3003" IN ItemName OR "3TX6P" IN ItemName THEN 'PINTU 6'
ELSE IF "PK.53004" IN ItemName OR "PK 3004" IN ItemName OR "4TX8P" IN ItemName THEN 'PINTU 8'
ELSE '-'
```

### Gab Filter
```
IF 'PROMO LEM' IN ItemName THEN 'NOT' (EXCLUDE FROM REPORT)
ELSE 'TAMPIL' (INCLUDE IN REPORT)
```

### Selection Criteria
- FamilyID harus 2879 (Plastik Kabinet 1) atau 2892 (Plastik Kabinet 2)
- Gab harus 'TAMPIL' (exclude items with 'PROMO LEM')
- Tanggal dalam tahun StartDate (jika diberikan)

## Table Structure

| Column      | Width    | Alignment | Description                                    |
| ----------- | -------- | --------- | ---------------------------------------------- |
| Grp         | 3%       | Center    | Group pintu (PINTU 8, PINTU 6, atau -)         |
| Tahun Total | 53%      | Left      | Nama barang dengan prefix spasi                |
| Bulan (1-12)| Variable | Right     | Total qty per bulan (Jan, Feb, Mar, ..., Des)  |
| Total       | 6%       | Right     | Total qty tahunan per item                     |

## Sample Request (cURL)

```bash
curl -X POST "https://your-domain.com/api/internal/ascends/shared/custom-report/pengiriman-lemari-tahunan/pdf" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: multipart/form-data" \
  -F "xml_file=@/path/to/xml/file.xml" \
  -F "DB_CompanyName=GSU" \
  -F "Sys_Username=admin" \
  -F "StartDate=2025-01-01"
```

## Sample XML Structure

```xml
<?xml version="1.0" standalone="yes"?>
<NewDataSet>
  <Table>
    <No>0</No>
    <DateID>2025-01-09T00:00:00+07:00</DateID>
    <ItemID>6012</ItemID>
    <ItemCode>2.1.3.4.01.29</ItemCode>
    <ItemName>GRANDE PLASTIK KABINET PK 3004 4Tx8P SNOW (LP 005)</ItemName>
    <FamilyID>2879</FamilyID>
    <Specification>BIRU</Specification>
    <Qty>7.0000</Qty>
  </Table>
</NewDataSet>
```
