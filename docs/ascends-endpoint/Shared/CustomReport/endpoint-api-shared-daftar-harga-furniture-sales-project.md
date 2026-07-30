# Dokumentasi Endpoint API Ascend Shared Daftar Harga Furniture Sales Project

Dokumen ini berisi endpoint internal untuk test/render laporan **Daftar Harga Furniture Sales Project** Ascend yang memakai Blade shared.

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

### Daftar Harga Furniture Sales Project

`POST /api/internal/ascends/shared/custom-report/check-price-group-a/daftar-harga-furniture-sales-project/pdf`

**Route name:** `api.internal.ascends.shared.custom-report.check-price-group-a.daftar-harga-furniture-sales-project.pdf`

**Controller:** `AscendXmlTestController::apiSharedCustomReportDaftarHargaFurnitureSalesProjectPdf`

**Deskripsi:** Laporan daftar harga furniture sales project per kategori group (Merona, Merona 2, Mo.re 1, Mo.re 2, Modelux, Grande) beserta level harga konsumen dan tier semi grosir berdasarkan diskon.

## Input

Parameter field utama:

- `xml_file`: file XML dari Ascend (contoh `Custom2084.xml`).
- `DB_CompanyName`: nama/kode perusahaan, contoh `RU`, `GSU`, atau `UC`.
- `Sys_Username`: nama user print, contoh `Ridho`.

Input XML alternatif yang tetap diterima:

- field `xml` berisi string XML
- raw XML body dengan `Content-Type: application/xml`

## Formula & Logika Bisnis

### Record Selection

- `PriceLevelName` harus berawalan `"12. Harga Sales Project"`.
- `FamilyName` harus termasuk dalam `["FURNITURE LIPAT", "PLASTIK FURNITURE 1", "PLASTIK FURNITURE 2", "PLASTIK KABINET 1", "PLASTIK KABINET 2"]`.
- Group `!= 'NOT'`.

### Group

Mengelompokkan item berdasarkan kode produk dalam `PriceGroupName`:

| Group | Kode Produk |
|---|---|
| **MERONA** | 2401, 2402, 2501, 2301, 2302, 2303, 2304 |
| **MERONA 2** | 2502 |
| **MORE 1** | 2801, 2802, 2832, 2870 |
| **MORE 2** | 2814, 2816 |
| **MODELUX** | 2601 |
| **GRANDE** | 53003, 53004, 53014, 53024 |
| **NOT** | Di luar daftar di atas (dilewati) |

### Tier & Diskon

| Group | Qty Tiers | Diskon |
|---|---|---|
| **MERONA / MERONA 2 / MORE 1** | 15-44 Pcs, 45-150 Pcs, > 150 Pcs | DISC 7%, 8%, 10% |
| **MORE 2** | 1-5 Pcs, 6-10 Pcs, 11-20 Pcs, > 20 Pcs | DISC 32%, 35%, 35%+10k, 35%+20k |
| **MODELUX** | 5-44 Pcs, 45-150 Pcs, > 150 Pcs | DISC 20%, 21%, 22% |
| **GRANDE** | 5-19 Dus, 20-49 Dus, > 50 Dus | DISC 14%, 15%, 17% |

### Sorting

`gr_urut` asc (1=Merona/Merona 2, 2=Mo.re 1/2, 3=Modelux, 4=Grande), lalu deskripsi asc (natural case-insensitive).

## Output

Format: PDF (A4, Portrait)

### Catatan Kaki

1. Daftar harga berlaku per Tgl 1 Mei 2026
2. Franco Medan
3. Harga sewaktu waktu bisa berubah / tanpa pemberitahuan terlebih dahulu
4. Dengan berlakunya Price List ini maka pricelist sebelumnya dinyatakan TIDAK BERLAKU
5. harga berdasarkan Level Toko
