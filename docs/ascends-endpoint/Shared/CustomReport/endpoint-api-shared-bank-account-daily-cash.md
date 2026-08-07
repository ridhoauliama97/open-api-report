# Dokumentasi Endpoint API Ascend Shared Laporan Kas Harian

Dokumen ini berisi endpoint internal untuk test/render laporan **Laporan Kas Harian** Ascend yang memakai Blade shared.

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

### Laporan Kas Harian

`POST /api/internal/ascends/shared/custom-report/bank-account-daily-cash/pdf`

**Route name:** `api.internal.ascends.shared.custom-report.bank-account-daily-cash.pdf`

**Controller:** `AscendXmlTestController::apiBankAccountDailyCashPdf`

**Deskripsi:** Laporan kas harian yang menampilkan pergerakan kas tiap rekening bank dalam satu hari. Data dikelompokkan per bank (contoh `BCA`, `BRI`, `Kas Besar`, `Kas Kecil`, `MANDIRI`) secara alfabetis. Untuk tiap transaksi menampilkan satu baris dengan sisi kiri berisi pemasukan (tanggal, keterangan, jumlah diterima) dan sisi kanan berisi pengeluaran (jumlah dibayar, keterangan, tanggal). Di akhir tiap grup ada baris `Total Pemasukan` dan `Total Pengeluaran`.

## Input

Parameter field utama:

- `xml_file`: file XML dari Ascend (contoh `CustomReport.xml`).
- `DB_CompanyName`: nama/kode perusahaan, contoh `RU`, `GSU`, atau `UC` (**wajib**).
- `Sys_Username`: nama user print, contoh `Ridho`.

Input XML alternatif yang tetap diterima:

- field `xml` berisi string XML
- raw XML body dengan `Content-Type: application/xml`

Fallback kompatibilitas lama:

- `company`: fallback internal/test jika `DB_CompanyName` belum dikirim.
- `Sys_UserName`: alias lama untuk nama user print.

Contoh `multipart/form-data`:

```text
DB_CompanyName=GSU
Sys_Username=Ridho
xml_file=CustomReport.xml
```

## Sumber XML

Setiap record `<Table>` merepresentasikan satu transaksi dengan dua sisi:

- Sisi **pemasukan** (opsional):
  - `ReceiveDate` — tanggal terima.
  - `ReceiveRemarks` — keterangan terima.
  - `ReceiveAmount` — jumlah diterima.
- Sisi **pengeluaran** (opsional):
  - `PaymentDate` — tanggal bayar.
  - `PaymentRemarks` — keterangan bayar.
  - `PaymentAmount` — jumlah dibayar.

Field identitas:

- `BankName` — nama bank/rekening (grup section, dipakai dikelompokkan).
- `BankAccountCode` — kode rekening bank (sebuah grup bisa punya beberapa kode rekening).

Jika satu sisi kosong (misal hanya pemasukan atau hanya pengeluaran), sisi tersebut tetap menjadi satu baris dengan kolom kosong, sesuai referensi.

## Response Sukses

- `200 application/pdf`
- `Content-Disposition: attachment`

Title yang tampil di halaman PDF:

```text
Laporan Kas Harian
```

Filename PDF:

```text
Laporan Kas Harian {company}.pdf
```

## Response Gagal

- `422 application/json` jika XML kosong, tidak valid, atau tidak bisa diproses.
- `422 application/json` jika `DB_CompanyName` tidak dikirim.
- `422 application/json` jika tidak ada transaksi di XML.

## Struktur Laporan

Laporan dikelompokkan berdasarkan **nama bank** (`BankName`, di-trim) diurutkan alfabetis. Untuk setiap bank ditampilkan satu tabel dengan 6 kolom:

```text
Tanggal | Keterangan | Pemasukan | Pengeluaran | Keterangan | Tanggal
```

Per baris transaksi:

- `Tanggal` (kiri) = `ReceiveDate`
- `Keterangan` (kiri) = `ReceiveRemarks`
- `Pemasukan` = `ReceiveAmount`
- `Pengeluaran` = `PaymentAmount`
- `Keterangan` (kanan) = `PaymentRemarks`
- `Tanggal` (kanan) = `PaymentDate`

Baris terakhir tiap grup:

```text
Total Pemasukan | {total ReceiveAmount} | {total PaymentAmount} | Total Pengeluaran
```

Format portrait A4 dengan font Noto Serif. Ukuran font tabel diperkecil (7px) supaya seluruh kolom muat di satu halaman portrait.

Semua tanggal memakai format Indonesia `DD-MMM-YY` (contoh `31-Jul-26`), angka memakai format Indonesia (ribuan memakai titik, desimal memakai koma, contoh `55.323,05`).

## Template Blade

Template Blade berada di:

```
resources/views/ascends/shared/custom_report/bank_account_daily_cash/pdf.blade.php
```
