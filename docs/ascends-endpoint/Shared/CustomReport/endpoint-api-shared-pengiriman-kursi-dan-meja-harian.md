# Dokumentasi Endpoint API Ascend Shared Laporan Pengiriman Kursi Makan, Kursi Santai, Kursi Cafe Dan Meja Santai (Harian)

Dokumen ini berisi endpoint internal untuk test/render laporan **Laporan Pengiriman Kursi Makan, Kursi Santai, Kursi Cafe Dan Meja Santai (Harian)** Ascend yang memakai Blade shared.

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

`POST /api/internal/ascends/shared/custom-report/pengiriman-kursi-dan-meja-harian/pdf`

**Route name:** `api.internal.ascends.shared.custom-report.pengiriman-kursi-dan-meja-harian.pdf`

**Controller:** `AscendXmlTestController::apiSharedCustomReportPengirimanKursiDanMejaHarianPdf`

**Deskripsi:** Laporan pengiriman kursi makan, kursi santai, kursi cafe, dan meja santai dengan breakdown harian, menampilkan kuantitas pengiriman per item per hari dalam format matriks.

## Input

Parameter field utama:

- `xml_file`: file XML dari Ascend (contoh `Custom2049.xml`).
- `DB_CompanyName`: nama/kode perusahaan, contoh `RU`, `GSU`, atau `UC`.
- `Sys_Username`: nama user print, contoh `Ridho`.
- `StartDate`: tanggal awal periode (contoh `2026-07-01`).
- `EndDate`: tanggal akhir periode (contoh `2026-07-31`).

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
StartDate=2026-07-01
EndDate=2026-07-31
xml_file=Custom2049.xml
```

## Response Sukses

- `200 application/pdf`
- `Content-Disposition: attachment`

Title yang tampil di halaman PDF:

```text
Laporan Pengiriman Kursi Makan, Kursi Santai, Kursi Cafe Dan Meja Santai(Harian)
```

Subtitle periode:

```text
Dari 01-Jul-26 s/d 31-Jul-26
```

Filename PDF:

```text
Laporan Pengiriman Kursi Makan, Kursi Santai, Kursi Cafe Dan Meja Santai(Harian) {company}.pdf
```

## Response Gagal

- `422 application/json` jika XML kosong, tidak valid, atau tidak bisa diproses.
- `422 application/json` jika `DB_CompanyName` tidak dikirim.
- `422 application/json` jika tidak ada data dalam rentang tanggal yang dipilih.

## Struktur Laporan

Laporan dikelompokkan berdasarkan **FamilyID** dalam format matriks harian:

1. **Header** — corner cell berisi nama kategori, `Tanggal` membentang kolom per hari (01 s.d. 31 sesuai hari yang memiliki data), dan kolom `Total` di paling kanan.
2. **Grouping Kategori:**
   - **Plastik Furniture 1** (FamilyID 867) — kursi bakso dan kursi makan
   - **Plastik Furniture 2** (FamilyID 2878) — kursi santai, kursi cafe, dan meja santai
   - Masing-masing kategori dimulai di halaman baru dan ditutup dengan baris **Total**
3. **Baris** — nama item (dengan spasi di awal sesuai formula `Name`), nilai kuantitas per hari, dan total per item di kolom paling kanan.
4. **Page footer** — printed by, timestamp, page number

Data difilter dengan formula Crystal:

- `Keterangan`: hanya menampilkan item dengan `Keterangan = 'TAMPIL'` (item yang mengandung `PROMO KURSI` di-skip)
- `ID`: `FamilyID = 867` → `PF1`, `FamilyID = 2878` → `PF2`; family lain tidak ditampilkan
- `Name`: `" "` + ItemName

Format landscape A4 dengan font Noto Serif; font diperkecil agar seluruh kolom hari muat dalam satu halaman.

## Template Blade

Template Blade berada di:

```
resources/views/ascends/shared/custom_report/pengiriman_kursi_dan_meja_harian/pdf.blade.php
```
