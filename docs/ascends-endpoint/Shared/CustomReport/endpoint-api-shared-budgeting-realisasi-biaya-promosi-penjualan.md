# Dokumentasi Endpoint API Ascend Shared Budgeting & Realisasi Biaya Promosi Penjualan

Dokumen ini berisi endpoint internal untuk test/render laporan **Budgeting & Realisasi Biaya Promosi Penjualan** Ascend yang memakai Blade shared.

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

### Laporan Budgeting & Realisasi Biaya Promosi Penjualan (Periode 1 Tahun)

`POST /api/internal/ascends/shared/custom-report/budgeting-realisasi-biaya-promosi-penjualan/pdf`

**Route name:** `api.internal.ascends.shared.custom-report.budgeting-realisasi-biaya-promosi-penjualan.pdf`

**Controller:** `AscendXmlTestController::apiSharedCustomReportBudgetingRealisasiBiayaPromosiPenjualanPdf`

**Deskripsi:** Laporan realisasi biaya promosi penjualan per cost center per bulan (periode 1 tahun). Menampilkan 12 bulan realisasi untuk setiap akun biaya promosi, dengan kolom Budget dan % Realisasi.

## Input

Parameter field utama:

- `xml_file`: file XML dari Ascend.
- `DB_CompanyName`: nama/kode perusahaan, contoh `RU`, `GSU`, atau `UC`.
- `Sys_Username`: nama user print, contoh `Ridho`.
- `StartDate`: tahun periode, contoh `2025`.

Input XML alternatif yang tetap diterima:

- field `xml` berisi string XML
- raw XML body dengan `Content-Type: application/xml`

Fallback kompatibilitas lama:

- `company`: fallback internal/test jika `DB_CompanyName` belum dikirim.
- `Sys_UserName`: alias lama untuk nama user print jika `Sys_Username` belum dikirim.
- `start_date`: alias untuk `StartDate`.

Jika tahun tidak dikirim, sistem menampilkan semua data yang tersedia di XML.

Contoh `multipart/form-data`:

```text
DB_CompanyName=GSU
Sys_Username=Ridho
StartDate=2025
xml_file=Custom21.xml
```

## Response Sukses

- `200 application/pdf`
- `Content-Disposition: attachment`

Title yang tampil di halaman PDF:

```text
Laporan Budgeting & Realisasi Biaya Promosi Penjualan (Periode 1 Tahun)
```

Filename PDF:

```text
Laporan Budgeting & Realisasi Biaya Promosi Penjualan ({company}).pdf
```

## Response Gagal

- `422 application/json` jika XML kosong, tidak valid, atau tidak bisa diproses.
- `422 application/json` jika `DB_CompanyName` tidak dikirim.
- `422 application/json` jika tidak ada data dalam rentang tahun yang dipilih.

## Struktur Laporan

Laporan menampilkan data per **Cost Center (Akun)** dengan 16 kolom:

| Kolom | Deskripsi |
|---|---|
| **Biaya Promosi Penjualan** | Nama akun biaya promosi |
| **Budget** | Nilai budget (data tidak tersedia di XML, ditampilkan `-`) |
| **Januari–Desember** | 12 kolom realisasi per bulan |
| **Total** | Jumlah realisasi 12 bulan |
| **% Realisasi Terhadap Budget** | Persentase realisasi terhadap budget (0.0% karena budget tidak tersedia) |

Data dikelompokkan berdasarkan Cost Center (`KetAkun`) dengan urutan ascending. Baris terakhir adalah **Total Biaya Promosi Penjualan** yang menjumlahkan seluruh akun.

Format landscape A4 dengan 16 kolom tetap (1 + 1 + 12 + 1 + 1).

## Template Blade

Template Blade berada di:

```
resources/views/ascends/shared/custom_report/budgeting_realisasi_biaya_promosi_penjualan/pdf.blade.php
```
