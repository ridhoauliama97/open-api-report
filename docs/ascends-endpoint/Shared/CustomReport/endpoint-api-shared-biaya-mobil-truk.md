# Dokumentasi Endpoint API Ascend Shared Laporan Biaya Mobil / Truk (Periode 6 Bulanan)

Dokumen ini berisi endpoint internal untuk test/render laporan **Laporan Biaya Mobil / Truk (Periode 6 Bulanan)** Ascend yang memakai Blade shared.

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

### Laporan Biaya Mobil / Truk (Periode 6 Bulanan)

`POST /api/internal/ascends/shared/custom-report/biaya-mobil-truk/pdf`

**Route name:** `api.internal.ascends.shared.custom-report.biaya-mobil-truk.pdf`

**Controller:** `AscendXmlTestController::apiSharedCustomReportBiayaMobilTrukPdf`

**Deskripsi:** Laporan biaya mobil / truk per unit / cost center (LowestDescription) per akun per bulan selama periode 6 bulan. Menampilkan nilai per bulan, total, rata-rata, terendah, dan tertinggi.

## Input

Parameter field utama:

- `xml_file`: file XML dari Ascend (contoh `Custom22.xml`).
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
xml_file=Custom22.xml
```

## Response Sukses

- `200 application/pdf`
- `Content-Disposition: attachment`

Title yang tampil di halaman PDF:

```text
Laporan Biaya Mobil / Truk (Periode 6 Bulanan)
```

Filename PDF:

```text
Laporan Biaya Mobil Truk {company}.pdf
```

## Response Gagal

- `422 application/json` jika XML kosong, tidak valid, atau tidak bisa diproses.
- `422 application/json` jika `DB_CompanyName` tidak dikirim.
- `422 application/json` jika tidak ada data dalam rentang tanggal yang dipilih.

## Struktur Laporan

Laporan dikelompokkan berdasarkan **LowestDescription** (Kendaraan / Cost Center) dengan urutan:

1. **Section Header** — Nama kendaraan / cost center (bold italic, warna `#9c111d`)
2. **Account Rows** — Baris per akun biaya dengan kolom:
   - Akun (AccountName)
   - 6 kolom bulan (January s.d. June)
   - Total
   - Rata - Rata
   - Terendah
   - Tertinggi
3. **Sub Total** — Baris subtotal untuk setiap kendaraan / cost center
4. **Grand Total** — Baris grand total di bagian akhir laporan

Format landscape A4 dengan font Noto Serif.

## Template Blade

Template Blade berada di:

```
resources/views/ascends/shared/custom_report/biaya_mobil_truk/pdf.blade.php
```
