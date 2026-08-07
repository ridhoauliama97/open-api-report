# Dokumentasi Endpoint API Ascend Shared Laporan Item Discontinue

Dokumen ini berisi endpoint internal untuk test/render laporan **Laporan Item Discontinue** Ascend yang memakai Blade shared.

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

### Laporan Item Discontinue

`POST /api/internal/ascends/shared/custom-report/item-discontinue/pdf`

**Route name:** `api.internal.ascends.shared.custom-report.item-discontinue.pdf`

**Controller:** `AscendXmlTestController::apiSharedCustomReportItemDiscontinuePdf`

**Deskripsi:** Laporan stok item yang di-discontinue, dikelompokkan per kategori stok (`BAHAN BAKU`, `BAHAN PENDUKUNG`, `BARANG DAGANG`, `BARANG JADI`, `WORK IN PROGRESS`). Untuk tiap item menampilkan baris `Item Code | Item Name | Category Name | Family Name | Saldo Awal | Masuk | Keluar | Akhir`.

## Input

Parameter field utama:

- `xml_file`: file XML dari Ascend (contoh `CustomReport.xml`).
- `DB_CompanyName`: nama/kode perusahaan, contoh `RU`, `GSU`, atau `UC` (**wajib**).
- `Sys_Username`: nama user print, contoh `Ridho`.
- `StartDate`: tanggal awal periode (contoh `2026-07-01`).
- `EndDate`: tanggal akhir periode (contoh `2026-07-31`).

Input XML alternatif yang tetap diterima:

- field `xml` berisi string XML
- raw XML body dengan `Content-Type: application/xml`

Fallback kompatibilitas lama:

- `company`: fallback internal/test jika `DB_CompanyName` belum dikirim.
- `Sys_UserName`: alias lama untuk nama user print.
- `start_date` + `end_date`: alias untuk `StartDate` + `EndDate`.

Contoh `multipart/form-data`:

```text
DB_CompanyName=RU
Sys_Username=Ridho
StartDate=2026-07-01
EndDate=2026-07-31
xml_file=CustomReport.xml
```

## Sumber XML

Setiap record `<Table>` memuat minimal field berikut:

- `ItemCode` — kode item.
- `ItemName` — nama item.
- `StockCategoryName` — kategori stok (grup section).
- `FamilyName` — family item.

Field stok (semua opsional, missing/null dianggap `0`):

- `Sawal`, `Good`, `Broken` — saldo awal.
- `QtyAdjusIn`, `Retur`, `QtyPrcIn` — penambah saldo awal.
- `Sales`, `QtyAdjusOut`, `Material`, `QtyUsg`, `QtyPrcOut` — pengurang saldo awal.
- `PrcIN`, `AdjusIn`, `UsageIn`, `QtyProd` — komponen kolom Masuk.
- `Qty`, `QtyMatrl`, `AdjusOut` — komponen kolom Keluar.

## Response Sukses

- `200 application/pdf`
- `Content-Disposition: attachment`

Title yang tampil di halaman PDF:

```text
Laporan Item Discontinue
```

Filename PDF:

```text
Laporan Item Discontinue {company}.pdf
```

## Response Gagal

- `422 application/json` jika XML kosong, tidak valid, atau tidak bisa diproses.
- `422 application/json` jika `DB_CompanyName` tidak dikirim.
- `422 application/json` jika tidak ada record pada XML.

## Struktur Laporan

Laporan dikelompokkan berdasarkan **kategori stok** (`StockCategoryName`) dengan urutan tetap:

1. `BAHAN BAKU`
2. `BAHAN PENDUKUNG`
3. `BARANG DAGANG`
4. `BARANG JADI`
5. `WORK IN PROGRESS`

Setiap kategori menampilkan tabel dengan 8 kolom:

```text
Item Code | Item Name | Category Name | Family Name | Saldo Awal | Masuk | Keluar | Akhir
```

Perhitungan tiap baris item:

```text
Saldo Awal = Sawal + Good + Broken + QtyAdjusIn + Retur − Sales − QtyAdjusOut − Material
             + QtyPrcIn − QtyUsg − QtyPrcOut
Masuk      = PrcIN + AdjusIn + UsageIn + QtyProd
Keluar     = Qty + QtyMatrl + AdjusOut
Akhir      = Saldo Awal + Masuk − Keluar
```

Format portrait A4 dengan font Noto Serif.

Subtitle periode memakai format Indonesia: `Dari {StartDate} s/d {EndDate}` (contoh `Dari 01-Jul-26 s/d 31-Jul-26`).

## Template Blade

Template Blade berada di:

```
resources/views/ascends/shared/custom_report/item_discontinue/pdf.blade.php
```

## Formula Field

- `Saldo Awal` (tiap komponen dijalankan terhadap `isnull(...,0)`):
  - `HasilStokBegining = {Table.Sawal} + {Table.Good} + {Table.Broken} + {Table.QtyAdjusIn} + {Table.Retur} − {Table.Sales} − {Table.QtyAdjusOut} − {Table.Material} + {Table.QtyPrcIn} − {Table.QtyUsg} − {Table.QtyPrcOut}`
- `StokMasuk = {Table.PrcIN} + {Table.AdjusIn} + {Table.UsageIn} + {Table.QtyProd}`
- `StokKeluar = {Table.Qty} + {Table.QtyMatrl} + {Table.AdjusOut}`
- `StokAkhir = HasilStokBegining + StokMasuk − StokKeluar`