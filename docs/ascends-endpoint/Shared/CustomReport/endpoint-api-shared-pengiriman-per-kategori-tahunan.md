# Dokumentasi Endpoint API Ascend Shared Laporan Pengiriman Per Kategori (Tahunan)

Dokumen ini berisi endpoint internal untuk test/render laporan **Laporan Pengiriman Per Kategori (Tahunan)** Ascend yang memakai Blade shared.

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

### Laporan Pengiriman Per Kategori (Tahunan)

`POST /api/internal/ascends/shared/custom-report/pengiriman-per-kategori-tahunan/pdf`

**Route name:** `api.internal.ascends.shared.custom-report.pengiriman-per-kategori-tahunan.pdf`

**Controller:** `AscendXmlTestController::apiSharedCustomReportPengirimanPerKategoriTahunanPdf`

**Deskripsi:** Laporan pengiriman barang per kategori dengan breakdown tahunan (bulanan Jan-Dec), menampilkan kuantitas pengiriman per item per bulan dalam format matriks.

## Input

Parameter field utama:

- `xml_file`: file XML dari Ascend (contoh `Custom2080.xml`).
- `DB_CompanyName`: nama/kode perusahaan, contoh `RU`, `GSU`, atau `UC`.
- `Sys_Username`: nama user print, contoh `Ridho`.
- `StartDate`: tanggal awal periode / tahun (contoh `2025-01-01`).

Input XML alternatif yang tetap diterima:

- field `xml` berisi string XML
- raw XML body dengan `Content-Type: application/xml`

Fallback kompatibilitas lama:

- `company`: fallback internal/test jika `DB_CompanyName` belum dikirim.
- `Sys_UserName`: alias lama untuk nama user print jika `Sys_Username` belum dikirim.
- `start_date`: alias untuk `StartDate`.

## Formula & Logika Bisnis

1. **Gab (Filter Item)**:
   - Jika `ItemName` mengandung `'PROMO LEM'`, maka `'NOT'` (dilewati).
   - Selain itu `'TAMPIL'`.
2. **Grp (Subgroup Plastik Kabinet 1)**:
   - Jika `ItemName` mengandung `"PK.53003"`, `"PK 3003"`, atau `"3TX6P"`, maka `'PINTU 6'`.
   - Jika `ItemName` mengandung `"PK.53004"`, `"PK 3004"`, atau `"4TX8P"`, maka `'PINTU 8'`.
   - Selain itu `'-'`.
3. **Record Selection**:
   - Hanya item dengan `{@Gab} startswith "TAMPIL"` yang ditampilkan.

## Kategori & Family ID

- **Enamel**: `FamilyID = 875`
- **Plastik Furniture 1**: `FamilyID = 867`
- **Furniture Lipat**: `FamilyID = 2893`
- **Plastik Furniture 2**: `FamilyID = 2878`
- **Plastik Kabinet 1**: `FamilyID = 2879` (berisi sub-group PINTU 8, PINTU 6, dan lainnya)
- **Plastik Kabinet 2**: `FamilyID = 2892`
