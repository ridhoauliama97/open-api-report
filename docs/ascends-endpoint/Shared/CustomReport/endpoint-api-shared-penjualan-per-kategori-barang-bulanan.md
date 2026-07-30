# Dokumentasi Endpoint API Ascend Shared Laporan Penjualan Per Kategori Barang Bulanan (Breakdown Perhari)

Dokumen ini berisi endpoint internal untuk test/render laporan **Laporan Penjualan Per Kategori Barang Bulanan (Breakdown Perhari)** Ascend yang memakai Blade shared.

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

### Laporan Penjualan Per Kategori Barang Bulanan (Breakdown Perhari)

`POST /api/internal/ascends/shared/custom-report/penjualan-per-kategori-barang-bulanan/pdf`

**Route name:** `api.internal.ascends.shared.custom-report.penjualan-per-kategori-barang-bulanan.pdf`

**Controller:** `AscendXmlTestController::apiSharedCustomReportPenjualanPerKategoriBarangBulananPdf`

**Deskripsi:** Laporan penjualan per kategori barang per salesperson dengan breakdown harian, menampilkan Qty, Rp, dan Lebih (Kurang) terhadap target harian (berdasarkan jumlah hari kerja).

## Input

Parameter field utama:

- `xml_file`: file XML dari Ascend (contoh `Custom23.xml`).
- `DB_CompanyName`: nama/kode perusahaan, contoh `RU`, `GSU`, atau `UC`.
- `Sys_Username`: nama user print, contoh `Ridho`.
- `StartDate`: tanggal awal periode (contoh `2026-06-01`).
- `EndDate`: tanggal akhir periode (contoh `2026-06-30`).
- `JumlahHariKerja`: jumlah hari kerja dalam sebulan (contoh `25`).

Input XML alternatif yang tetap diterima:

- field `xml` berisi string XML
- raw XML body dengan `Content-Type: application/xml`

Fallback kompatibilitas lama:

- `company`: fallback internal/test jika `DB_CompanyName` belum dikirim.
- `Sys_UserName`: alias lama untuk nama user print jika `Sys_Username` belum dikirim.
- `start_date` + `end_date`: alias untuk `StartDate` + `EndDate`.

Contoh `multipart/form-data`:

```text
DB_CompanyName=GSU
Sys_Username=Ridho
StartDate=2026-06-01
EndDate=2026-06-30
JumlahHariKerja=25
xml_file=Custom23.xml
```

## Response Sukses

- `200 application/pdf`
- `Content-Disposition: attachment`

Title yang tampil di halaman PDF:

```text
Laporan Penjualan Per Kategori Barang Bulanan (Breakdown Perhari)
```

Filename PDF:

```text
Laporan Penjualan Per Kategori Barang Bulanan {company}.pdf
```

## Response Gagal

- `422 application/json` jika XML kosong, tidak valid, atau tidak bisa diproses.
- `422 application/json` jika `DB_CompanyName` tidak dikirim.
- `422 application/json` jika tidak ada data dalam rentang tanggal yang dipilih.

## Struktur Laporan

Laporan dikelompokkan berdasarkan **Salesperson** (`SP Name`) dengan rincian:

1. **Salesperson Header** — Nama salesperson (bold italic, warna `#9c111d`)
2. **Summary Rows** — Dua baris di atas tabel:
   - Baris 1: Total penjualan Rp per kategori dan kolom Total
   - Baris 2: Target per hari per kategori dan kolom Total
3. **Daily Breakdown Table** — Per baris tanggal (Tgl), dengan 6 grup kolom:
   - Plastik Furniture 1 & 2 (Qty, Rp, Lebih (Kurang))
   - Plastik Kabinet 1 (Qty, Rp, Lebih (Kurang))
   - Plastik Kabinet 2 (Qty, Rp, Lebih (Kurang))
   - Enamel (Qty, Rp, Lebih (Kurang))
   - Furniture Lipat (Qty, Rp, Lebih (Kurang))
   - **Total** (Qty, Rp, Lebih (Kurang))
4. **Subtotal Row** — Total Qty, Rp, dan Lebih (Kurang) per kategori di akhir tiap salesperson
5. **Weekly Analysis Table** — Analisa penjualan per minggu (Minggu 1-5) dengan kolom Target, Penjualan, dan % per kategori barang + Total
6. **Analisa Penjualan Harian Table** — Rata-rata, Terendah, dan Tertinggi penjualan per kategori barang + Total
7. **Grand Total Section** (jika >1 salesperson) — Seluruh tabel di atas (1-6) diagregasi untuk semua salesperson, dengan header kolom Rp diganti menjadi **Penjualan**

Format landscape A4 dengan font Noto Serif.

## Template Blade

Template Blade berada di:

```
resources/views/ascends/shared/custom_report/penjualan_per_kategori_barang_bulanan/pdf.blade.php
```
