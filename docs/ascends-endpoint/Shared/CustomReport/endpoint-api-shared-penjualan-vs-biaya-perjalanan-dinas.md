# Dokumentasi Endpoint API Ascend Shared Penjualan VS Biaya Perjalanan Dinas

Dokumen ini berisi endpoint internal untuk test/render laporan **Penjualan VS Biaya Perjalanan Dinas** Ascend yang memakai Blade shared.

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

### Laporan Perjalanan Dinas VS Penjualan (Periode 6 Bulan)

`POST /api/internal/ascends/shared/custom-report/penjualan-vs-biaya-perjalanan-dinas/pdf`

**Route name:** `api.internal.ascends.shared.custom-report.penjualan-vs-biaya-perjalanan-dinas.pdf`

**Controller:** `AscendXmlTestController::apiSharedCustomReportPenjualanVsBiayaPerjalananDinasPdf`

**Deskripsi:** Laporan perbandingan biaya perjalanan dinas terhadap penjualan per salesperson per bulan (periode 6 bulan). Menampilkan penjualan per family, biaya perjalanan dinas, dan persentase biaya terhadap penjualan.

## Input

Parameter field utama:

- `xml_file`: file XML dari Ascend.
- `DB_CompanyName`: nama/kode perusahaan, contoh `RU`, `GSU`, atau `UC`.
- `Sys_Username`: nama user print, contoh `Ridho`.
- `StartDate`: tanggal awal periode (contoh `2026-01-01`).
- `EndDate`: tanggal akhir periode (contoh `2026-06-30`).

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
DB_CompanyName=GSU
Sys_Username=Ridho
StartDate=2026-01-01
EndDate=2026-06-30
xml_file=Custom20.xml
```

## Response Sukses

- `200 application/pdf`
- `Content-Disposition: attachment`

Title yang tampil di halaman PDF:

```text
Laporan Perjalanan Dinas VS Penjualan (Periode 6 Bulan)
```

Filename PDF:

```text
Laporan Perjalanan Dinas VS Penjualan ({company}).pdf
```

## Response Gagal

- `422 application/json` jika XML kosong, tidak valid, atau tidak bisa diproses.
- `422 application/json` jika `DB_CompanyName` tidak dikirim.
- `422 application/json` jika tidak ada data dalam rentang tanggal yang dipilih.

## Struktur Laporan

Laporan dikelompokkan berdasarkan **SalesPerson** dengan urutan:

1. **Section Header** — Nama salesperson (bold italic, warna `#9c111d`)
2. **Penjualan Rows** — Baris per family barang dengan kolom:
   - Nama keluarga barang
   - Nilai penjualan per bulan (6 bulan)
   - Total
   - Rata-rata
   - Terendah
   - Tertinggi
3. **Total Penjualan** — Baris total seluruh family
4. **Biaya Perjalanan Dinas** — Baris biaya perjalanan dinas (jika ada)
5. **Total Biaya** — Baris total biaya (jika ada)
6. **Persentase** — Baris persentase biaya terhadap penjualan per bulan
7. **Grand Total** — Section aggregasi seluruh salesperson (struktur sama)

Filter bawaan:

- Salesperson dengan nama diawali `EDIYANTO` otomatis diexclude.

Format landscape A4 dengan jumlah kolom `1 + jumlah_bulan + 4`.

## Template Blade

Template Blade berada di:

```
resources/views/ascends/shared/custom_report/penjualan_vs_biaya_perjalanan_dinas/pdf.blade.php
```
