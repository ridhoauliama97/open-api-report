# Dokumentasi Endpoint API Ascend Shared Daftar Harga Furniture Semi Grosir

Dokumen ini berisi endpoint internal untuk test/render laporan **Daftar Harga Furniture Semi Grosir** Ascend yang memakai Blade shared.

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

### Daftar Harga Furniture Semi Grosir

`POST /api/internal/ascends/shared/custom-report/check-price-group-a/daftar-harga-furniture-semi-grosir/pdf`

**Route name:** `api.internal.ascends.shared.custom-report.check-price-group-a.daftar-harga-furniture-semi-grosir.pdf`

**Controller:** `AscendXmlTestController::apiSharedCustomReportDaftarHargaFurnitureSemiGrosirPdf`

**Deskripsi:** Laporan daftar harga furniture semi grosir per kategori group (Merona, Mo.re, Modelux, Grande, Hana/Rak Susun) beserta level harga konsumen dan semi grosir diskon 7%.

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

- FamilyName harus termasuk dalam `["PLASTIK KABINET 2", "PLASTIK KABINET 1", "FURNITURE LIPAT", "PLASTIK FURNITURE 1", "PLASTIK FURNITURE 2"]`.
- Group `!= 'NOT'`.

### Group

Mengelompokkan item berdasarkan kode produk dalam `PriceGroupName`:

| Group | Kode Produk |
|---|---|
| **MERONA** | 2401, 2402, 2501, 2301, 2302, 2303, 2304, 2502 |
| **MO.RE** | 2801, 2802, 2814, 2816, 2832, 2870 |
| **MODELUX** | 2601 |
| **GRANDE** | 53003, 53004, 53014, 53024 |
| *(HANA/RAK SUSUN)* | 3101, 3102, 3103, 3104, 3105, 2204 (tanpa category header) |
| **NOT** | Di luar daftar di atas (dilewati) |

### Urut

Urutan item dalam group:

| Urut | Kriteria |
|---|---|
| 1 | Mengandung "Premium" |
| 2 | KM 2401A, KM 2402A, MS 2801A, MS 2802 |
| 3 | KS 2501 A, KS 2502 A |
| 7 | Meja Lipat 4/6 |
| 8 | Meja Lipat 32 |
| 9 | Lainnya |

### Price Levels

| Kolom | Data Source |
|---|---|
| Harga Konsumen | `Price` dari level `01. Harga Retail` (jika tidak ada → blank) |
| Semi Grosir Diskon 7% | `PriceAfterDisc` dari level `02. Harga Semi Grosir`; fallback ke `Max(Price)` semua level jika tidak ada data semi grosir |
| Isi | `PerDus` dari level `02. Harga Semi Grosir` (override) |

### Sorting

`gr_urut` asc (1=Merona, 2=Mo.re, 3=Modelux, 4=Grande, 5=HANA), lalu `urut` asc, lalu deskripsi asc.

## Output

Format: PDF (A4, Portrait)

### Tabel

5 kolom:

| No | Nama Barang | Isi Per Bal (Pcs) / Isi Per Dus (Unit) | Harga Konsumen | Semi Grosir Diskon 7% |
|---|---|---|---|---|

Setiap group memiliki tabel sendiri dengan nomor reset ke 1.

### Catatan Kaki

1. Daftar harga berlaku per Tgl 1 Mei 2026
2. Franco Medan
3. Harga sewaktu waktu bisa berubah / tanpa pemberitahuan terlebih dahulu
4. Dengan berlakunya Price List ini maka pricelist sebelumnya dinyatakan TIDAK BERLAKU
5. harga berdasarkan Level Toko
