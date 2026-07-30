# Dokumentasi Endpoint API Ascend Shared Daftar Harga Furniture (ALL)

Dokumen ini berisi endpoint internal untuk test/render laporan **Daftar Harga Furniture** Ascend yang memakai Blade shared.

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

### Daftar Harga Furniture (ALL)

`POST /api/internal/ascends/shared/custom-report/check-price-group-a/daftar-harga-furniture-all/pdf`

**Route name:** `api.internal.ascends.shared.custom-report.check-price-group-a.daftar-harga-furniture-all.pdf`

**Controller:** `AscendXmlTestController::apiSharedCustomReportDaftarHargaFurnitureAllPdf`

**Deskripsi:** Laporan daftar harga furniture per kategori group (Merona, Mo.re, Modelux, Grande) beserta level harga konsumen, semi grosir, grosir, dan akun spesial.

## Input

Parameter field utama:

- `xml_file`: file XML dari Ascend (contoh `Custom2084.xml`).
- `DB_CompanyName`: nama/kode perusahaan, contoh `RU`, `GSU`, atau `UC`.
- `Sys_Username`: nama user print, contoh `Ridho`.

Input XML alternatif yang tetap diterima:

- field `xml` berisi string XML
- raw XML body dengan `Content-Type: application/xml`

## Formula & Logika Bisnis

1. **Group**:
   - `MERONA`: Berdasarkan kode/nama produk (2401, 2402, 2501, 2301, 2302, 2303, 2304, 2502).
   - `MO.RE`: Berdasarkan kode/nama produk (2801, 2802, 2814, 2816, 2832, 2870).
   - `MODELUX`: Berdasarkan kode/nama produk (2601).
   - `GRANDE`: Berdasarkan kode/nama produk (53003, 53004, 53014, 53024).
   - `NOT`: Di luar daftar di atas (dilewati).
2. **Record Selection**:
   - FamilyName harus termasuk dalam `["PLASTIK KABINET 2", "PLASTIK KABINET 1", "FURNITURE LIPAT", "PLASTIK FURNITURE 1", "PLASTIK FURNITURE 2"]` dan Group `!= 'NOT'`.
