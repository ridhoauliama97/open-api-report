# Dokumentasi Endpoint API Ascend Shared Laporan Item Tidak ada Penjualan Dan Tidak Ada Produksi

Dokumen ini berisi endpoint internal untuk test/render laporan **Laporan Item Tidak ada Penjualan Dan Tidak Ada Produksi** Ascend yang memakai Blade shared.

- Base host: `http://192.168.10.100:5006`
- Prefix API: `/api`

## Konsep Shared

Template shared ini dipakai supaya struktur struktur laporan bisa digunakan lintas perusahaan:

- `RU`
- `GSU`
- `UC`

Nama perusahaan pada title dan filename dibaca dari parameter field `DB_CompanyName`.
Nama user print pada footer dibaca dari parameter field `Sys_Username`.

## Endpoint

### Laporan Item Tidak ada Penjualan Dan Tidak Ada Produksi

`POST /api/internal/ascends/shared/custom-report/cek-produksi-gsu/pdf`

**Route name:** `api.internal.ascends.shared.custom-report.cek-produksi-gsu.pdf`

**Controller:** `AscendXmlTestController::apiSharedCustomReportCekProduksiGsuPdf`

**Deskripsi:** Laporan item yang tidak ada penjualan dan tidak ada produksi (stok awal > 0 dan aktivitas total = 0, mengecualikan item tertentu) dikelompokkan berdasarkan kategori stok.

## Input

Parameter field utama:

- `xml_file`: file XML dari Ascend (contoh `Custom9.xml`).
- `DB_CompanyName`: nama/kode perusahaan, contoh `RU`, `GSU`, atau `UC`.
- `Sys_Username`: nama user print, contoh `Ridho`.
- `StartDate`: tanggal awal periode (contoh `2026-08-01`).
- `EndDate`: tanggal akhir periode (contoh `2026-08-02`).

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
StartDate=2026-08-01
EndDate=2026-08-02
xml_file=Custom9.xml
```

## Response Sukses

- `200 application/pdf`
- `Content-Disposition: attachment`

Title yang tampil di halaman PDF:

```text
Laporan Item Tidak ada Penjualan Dan Tidak Ada Produksi
```

Filename PDF:

```text
Laporan Item Tidak ada Penjualan Dan Tidak Ada Produksi {company}.pdf
```

## Response Gagal

- `422 application/json` jika XML kosong, tidak valid, atau tidak bisa diproses.
- `422 application/json` jika `DB_CompanyName` tidak dikirim.
- `422 application/json` jika tidak ada data yang sesuai dengan filter laporan.

## Struktur Laporan

Laporan dikelompokkan berdasarkan **StockCategoryName** (urutan abjad) dengan susunan:

1. **Section Header** — Nama kategori stok (bold italic, warna `#9c111d`)
2. **Item Rows** — Baris per item dengan kolom:
   - Item Code
   - Item Name
   - Category Name
   - Family Name
   - Saldo Awal (`HasilStokBegining`)
   - Qty Sales (`-`)
   - Qty Prod (`-`)

Format A4 portrait dengan font Noto Serif.

## Template Blade

Template Blade berada di:

```
resources/views/ascends/shared/custom_report/cek_produksi_gsu/pdf.blade.php
```
