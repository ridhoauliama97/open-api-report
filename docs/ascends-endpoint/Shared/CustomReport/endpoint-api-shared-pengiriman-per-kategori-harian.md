# Dokumentasi Endpoint API Ascend Shared Laporan Pengiriman Per Kategori (Harian)

Dokumen ini berisi endpoint internal untuk test/render laporan **Laporan Pengiriman Per Kategori (Harian)** Ascend yang memakai Blade shared.

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

### Laporan Pengiriman Per Kategori (Harian)

`POST /api/internal/ascends/shared/custom-report/pengiriman-per-kategori-harian/pdf`

**Route name:** `api.internal.ascends.shared.custom-report.pengiriman-per-kategori-harian.pdf`

**Controller:** `AscendXmlTestController::apiSharedCustomReportPengirimanPerKategoriHarianPdf`

**Deskripsi:** Laporan pengiriman barang per kategori dengan breakdown harian, menampilkan kuantitas pengiriman per item per hari dalam format matriks.

## Input

Parameter field utama:

- `xml_file`: file XML dari Ascend (contoh `Custom2079.xml`).
- `DB_CompanyName`: nama/kode perusahaan, contoh `RU`, `GSU`, atau `UC`.
- `Sys_Username`: nama user print, contoh `Ridho`.
- `StartDate`: tanggal awal periode (contoh `2026-06-01`).
- `EndDate`: tanggal akhir periode (contoh `2026-06-30`).

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
xml_file=Custom2079.xml
```

## Response Sukses

- `200 application/pdf`
- `Content-Disposition: attachment`

Title yang tampil di halaman PDF:

```text
Laporan Pengiriman Per Kategori (Harian)
```

Filename PDF:

```text
Laporan Pengiriman Per Kategori Harian {company}.pdf
```

## Response Gagal

- `422 application/json` jika XML kosong, tidak valid, atau tidak bisa diproses.
- `422 application/json` jika `DB_CompanyName` tidak dikirim.
- `422 application/json` jika tidak ada data dalam rentang tanggal yang dipilih.

## Struktur Laporan

Laporan dikelompokkan berdasarkan **Kategori Barang** dalam format matriks harian:

1. **Header** — `Tanggal` (item name), `Total` (kolom pertama), dan kolom per tanggal (01 s.d. 30/31) sesuai hari kerja
2. **Grouping Kategori:**
   - **Enamel** (FamilyID 875) — item langsung di bawah kategori
   - **Plastik Furniture 1** (FamilyID 867) — item langsung di bawah kategori
   - **Plastik Furniture 2** (FamilyID 2878, 2893) — item langsung di bawah kategori
   - **Plastik Kabinet 1** (FamilyID 2879) — item dikelompokkan berdasarkan `Grp`:
     - `3TX6P` — GRANDE PLASTIK KABINET PK 3003
     - `4TX8P` — GRANDE PLASTIK KABINET PK 3004
     - Masing-masing grup memiliki subtotal
   - **Plastik Kabinet 2** (FamilyID 2892) — item langsung tanpa grup
3. **Subtotal** per grup (khusus Kabinet 1) dan **Total** per kategori
4. **Page footer** — printed by, timestamp, page number

Data difilter dengan formula Crystal:

- `Keterangan`: hanya menampilkan item dengan `Keterangan = 'TAMPIL'` (PROMO LEM, PROMO MORE, PROMO 6 BH di-skip)
- `Grp`: pengelompokan item Kabinet berdasarkan tipe kemasan
- `Name`: `" "` + ItemName

Format landscape A4-L dengan font Noto Serif.

## Template Blade

Template Blade berada di:

```
resources/views/ascends/shared/custom_report/pengiriman_per_kategori_harian/pdf.blade.php
```
