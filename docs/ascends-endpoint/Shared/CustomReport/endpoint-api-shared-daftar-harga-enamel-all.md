# Dokumentasi Endpoint API Ascend Shared Daftar Harga Enamel (ALL)

Dokumen ini berisi endpoint internal untuk test/render laporan **Daftar Harga Enamel (ALL)** Ascend yang memakai Blade shared.

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

### Daftar Harga Enamel (ALL)

`POST /api/internal/ascends/shared/custom-report/check-price-group-a/daftar-harga-enamel-all/pdf`

**Route name:** `api.internal.ascends.shared.custom-report.check-price-group-a.daftar-harga-enamel-all.pdf`

**Controller:** `AscendXmlTestController::apiSharedCustomReportDaftarHargaEnamelAllPdf`

**Deskripsi:** Laporan daftar harga enamel per kategori (Sekar1) beserta level harga konsumen, retail, semi grosir, grosir, dan akun spesial.

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

- `FamilyName` harus diawali dengan `"ENAMEL"`.
- `Group` harus termasuk dalam `["Sekar1", "Sekar2"]` (selain itu dilewati).

### Group

Mengelompokkan item berdasarkan `PriceGroupName`:

**Sekar1** (54 items):
- Baskom Biasa (30, 40 M/MH, 40 DECO, 60)
- Baskom Dalam (16, 18, 20, 22, 24, 26, 28, 30, 40 M/MH, 40 PLS)
- Baskom Victory (18, 20, 22, 24, 26, 28, 30, 34, 36, 40)
- Cangkir (7, 9, 10, 12, Tutup, Ring)
- Kobokan, Kuali Hitam 40/45, Kuali/Wajan 40/45
- Nampan (30, 40, 45, 52, 60)
- Panci 40, Piring Soup 22
- Mug Rim, Mug With Rim

**Z** (filtered out):
- Item di luar daftar Sekar1, contoh: `SEKAR BASKOM DALAM 40 CM DECO`, `SEKAR KUALI HITAM 37 CM`

### Bnt (GSU Mark)

Menandai item yang termasuk GSU (non-returneable) dengan tanda `*`:
- `SEKAR CANGKIR 7` → Bnt = 1
- `SEKAR NAMPAN` → Bnt = 1
- `SEKAR PIRING SOUP` → Bnt = 1
- Selain itu → Bnt = 0

### Price Levels

| Level | Disc | Data Source |
|---|---|---|
| Harga Konsumen | - | `Price` (harga per unit/lusin) |
| Retail | 3% | `PriceAfterDisc(01. Harga Retail) / Conversion` |
| Semi Grosir | 5% | `PriceAfterDisc(02. Harga Semi Grosir) / Conversion` |
| Grosir | 7% | `PriceAfterDisc(03. Harga Grosir) / Conversion` |
| Akun Spesial | 9% | `PriceAfterDisc(04. Harga Akun Special) / Conversion` |

## Output

Format: PDF (A4, Portrait)

### Tabel

8 kolom:

| No | Nama Barang | Isi/Dus | Harga Konsumen | Retail Diskon 3% | Semi Grosir Diskon 5% | Grosir Diskon 7% | Akun Spesial Diskon 9% |
|---|---|---|---|---|---|---|---|

### Catatan Kaki

1. Daftar harga berlaku per Tgl ...
2. Franco Medan
3. Harga sewaktu waktu bisa berubah / tanpa pemberitahuan terlebih dahulu
4. Dengan berlakunya Price List ini maka pricelist sebelumnya tidak berlaku lagi
5. Harga berdasarkan Level Toko
6. Produk GSU tidak bisa diretur (tanda * di nomor)
