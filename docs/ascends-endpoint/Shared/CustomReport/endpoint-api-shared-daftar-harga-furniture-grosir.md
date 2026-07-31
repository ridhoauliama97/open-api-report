# Dokumentasi Endpoint API Ascend Shared Daftar Harga Furniture

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

### Daftar Harga Furniture

`POST /api/internal/ascends/shared/custom-report/check-price-group-a/daftar-harga-furniture-grosir/pdf`

**Route name:** `api.internal.ascends.shared.custom-report.check-price-group-a.daftar-harga-furniture-grosir.pdf`

**Controller:** `AscendXmlTestController::apiSharedCustomReportDaftarHargaFurnitureGrosirPdf`

**Deskripsi:** Laporan daftar harga furniture per kategori group (Merona, Mo.re, Modelux, Grande, Hana/Rak Susun) beserta harga konsumen dan harga akun spesial diskon 11%.

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

- `FamilyName` harus termasuk dalam `["PLASTIK KABINET 2", "PLASTIK KABINET 1", "FURNITURE LIPAT", "PLASTIK FURNITURE 1", "PLASTIK FURNITURE 2"]`.

### Group

Mengelompokkan item berdasarkan kode produk dalam `PriceGroupName`:

| Group | Kode Produk |
|---|---|
| **MERONA** | 2401, 2402, 2501, 2301, 2302, 2303, 2304, 2502 |
| **MO.RE** | 2801, 2802, 2814, 2816, 2832, 2870 |
| **MODELUX** | 2601 |
| **GRANDE** | 53003, 53004, 53014, 53024 |
| **NOT** | selain kode di atas (mis. HANA 3101-3105, RAK SUSUN 2204) |

Urutan grup: MERONA (1), MO.RE (2), MODELUX (3), GRANDE (4), NOT (5). Nomor urut baris restart dari 1 setiap pergantian grup, dan setiap grup dicetak sebagai tabel tersendiri (tanpa label grup).

### Urut

Urutan item dalam group:

| Urut | Kriteria |
|---|---|
| 1 | Mengandung "Premium" |
| 2 | KM 2401A, KM 2402A, MS 2801A, MS 2802 |
| 3 | KS 2501 A, KS 2502 A |
| 7 | Meja Lipat 4 / Meja Lipat 6 |
| 8 | Meja Lipat 32 |
| 9 | Lainnya |

### Ket (Judul Kolom Isi)

- `Isi Per Bal (Pcs)` untuk grup MERONA, MO.RE, MODELUX.
- `Isi Per Dus (Unit)` untuk grup GRANDE dan NOT.

### Price Levels

Satu `PriceGroupName` dapat memuat beberapa set harga (baris dengan `Price` berbeda pada level yang sama). Set yang ditampilkan adalah set dengan **harga tertinggi**:

| Kolom | Data Source |
|---|---|
| Harga Konsumen | `Price` tertinggi pada grup (seluruh level; fallback ke level lain bila level Retail tidak ada) |
| Akun Spesial Diskon 11% | `PriceAfterDisc` tertinggi dari level `04. Harga Akun Special` (kosong bila level tidak ada) |
| Isi | `PerDus` dari baris dengan `Price` tertinggi |

### Sorting

`gr_urut` asc (1=Merona, 2=Mo.re, 3=Modelux, 4=Grande, 5=Hana/Rak Susun), lalu `urut` asc, lalu deskripsi asc.

## Output

Format: PDF (A4, Portrait)

- Judul: `DAFTAR HARGA FURNITURE` (dengan nama perusahaan di atasnya bila `DB_CompanyName` terisi)
- Sub judul: `04/PRICELIST-PANEN/IX/25`
- Kolom: No | Nama Barang | Isi Per Bal (Pcs) / Isi Per Dus (Unit) | Harga Konsumen | Akun Spesial Diskon 11%

### Catatan Kaki

1. Daftar harga berlaku per Tgl 1 Mei 2026
2. Franco Medan
3. Harga sewaktu waktu bisa berubah / tanpa pemberitahuan terlebih dahulu
4. Dengan berlakunya Price List ini maka pricelist sebelumnya dinyatakan TIDAK BERLAKU
5. harga berdasarkan Level Toko
