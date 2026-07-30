# Dokumentasi Endpoint API Ascend Shared Penjualan Per Item Barang & Analisa SKU

Dokumen ini berisi endpoint internal untuk test/render laporan **Penjualan Per Item Barang & Analisa SKU** Ascend yang memakai Blade shared.

- Base host: `http://192.168.10.100:5006`
- Prefix API: `/api`

## Konsep Shared

Template shared ini dipakai supaya struktur Blade laporan bisa digunakan lintas perusahaan:

- `RU`
- `GSU`
- `UC`

Nama perusahaan pada title dan filename dibaca dari parameter field `DB_CompanyName`.
Nama user print pada footer dibaca dari parameter field `Sys_Username`.

## Endpoints

### 1. Penjualan Per Item Barang & Analisa SKU

`POST /api/internal/ascends/shared/custom-report/penjualan-per-item-analisa-sku/pdf`

**Route name:** `api.internal.ascends.shared.custom-report.penjualan-per-item-analisa-sku.pdf`

**Controller:** `AscendXmlTestController::apiSharedCustomReportPenjualanPerItemAnalisaSkuPdf`

**Deskripsi:** Laporan analisa SKU per item barang per bulan. Setiap baris menampilkan jumlah SKU, capaian, dan persentase per bulan per family barang.

### 2. Penjualan Per Item Barang & Analisa SKU (Detail)

`POST /api/internal/ascends/shared/custom-report/penjualan-per-item-analisa-sku-detail/pdf`

**Route name:** `api.internal.ascends.shared.custom-report.penjualan-per-item-analisa-sku-detail.pdf`

**Controller:** `AscendXmlTestController::apiSharedCustomReportPenjualanPerItemAnalisaSkuDetailPdf`

**Deskripsi:** Laporan penjualan per item barang detail. Setiap family memiliki section header, diikuti item-item dengan Qty dan Penjualan per bulan, serta grand total.

## Input

Parameter field utama:

- `xml_file`: file XML dari Ascend.
- `DB_CompanyName`: nama/kode perusahaan, contoh `RU`, `GSU`, atau `UC`.
- `Sys_Username`: nama user print, contoh `Ridho`.
- `StartDate`: tanggal awal periode (contoh `2026-01-01`).
- `EndDate`: tanggal akhir periode (contoh `2026-12-31`).

Input XML alternatif yang tetap diterima:

- field `xml` berisi string XML
- raw XML body dengan `Content-Type: application/xml`

Fallback kompatibilitas lama:

- `company`: fallback internal/test jika `DB_CompanyName` belum dikirim.
- `Sys_UserName`: alias lama untuk nama user print jika `Sys_Username` belum dikirim.
- `start_date` + `end_date`: alias untuk `StartDate` + `EndDate`.

Jika tanggal tidak dikirim, sistem memakai semua data yang tersedia di XML.

Contoh `multipart/form-data`:

```text
DB_CompanyName=RU
Sys_Username=Ridho
StartDate=2026-01-01
EndDate=2026-12-31
xml_file=penjualan_per_item.xml
```

## Response Sukses

- `200 application/pdf`
- `Content-Disposition: attachment`

Title yang tampil di halaman PDF:

```text
Laporan Penjualan Per Item Barang & Analisa SKU ({company})
Laporan Penjualan Per Item Barang ({company})
```

Filename PDF:

```text
Laporan Penjualan Per Item Barang & Analisa SKU ({company}).pdf
Laporan Penjualan Per Item Barang ({company}).pdf
```

## Response Gagal

- `422 application/json` jika XML kosong, tidak valid, atau tidak bisa diproses.
- `422 application/json` jika `DB_CompanyName` tidak dikirim.

## Struktur Laporan (Analisa SKU)

Laporan menampilkan data per **Family Name** dengan 3 kolom per bulan:

| Kolom | Deskripsi |
|---|---|
| **SKU** | Jumlah SKU (Item) yang terjual |
| **Capai** | Jumlah capaian |
| **%** | Persentase capaian terhadap SKU |

Baris total di bagian bawah menjumlahkan seluruh family.

## Struktur Laporan (Detail)

Laporan dikelompokkan berdasarkan **Family Name** dengan urutan:

1. **Section Header** — Nama family (bold italic, warna `#9c111d`)
2. **Item Rows** — Detail per item barang dengan kolom:
   - Nama Barang
   - Qty per bulan
   - Penjualan per bulan
   - Total Qty (seluruh bulan)
   - Total Penjualan (seluruh bulan)
3. **Grand Total Row** — Total keseluruhan Qty dan Penjualan

Format landscape A4 dengan jumlah kolom menyesuaikan jumlah bulan.

## Template Blade

Template Blade berada di:

```
resources/views/ascends/shared/custom_report/penjualan_per_item_analisa_sku/pdf.blade.php
resources/views/ascends/shared/custom_report/penjualan_per_item_analisa_sku_detail/pdf.blade.php
```
