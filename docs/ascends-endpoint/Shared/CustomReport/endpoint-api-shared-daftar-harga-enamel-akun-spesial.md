# Dokumentasi Endpoint API Ascend Shared Daftar Harga Enamel Akun Spesial

Dokumen ini berisi endpoint internal untuk test/render laporan **Daftar Harga Enamel Akun Spesial** Ascend yang memakai Blade shared.

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

### Daftar Harga Enamel Akun Spesial

`POST /api/internal/ascends/shared/custom-report/check-price-group-a/daftar-harga-enamel-akun-spesial/pdf`

**Route name:** `api.internal.ascends.shared.custom-report.check-price-group-a.daftar-harga-enamel-akun-spesial.pdf`

**Controller:** `AscendXmlTestController::apiSharedCustomReportDaftarHargaEnamelAkunSpesialPdf`

**Deskripsi:** Laporan daftar harga enamel level akun spesial (harga konsumen + harga akun spesial diskon 9%).

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

Mengelompokkan item berdasarkan `PriceGroupName` (kecuali `NAMPAN 60 CM` yang dicek pada `PriceGroupDescription`):

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

Menandai item yang termasuk GSU (non-returneable) dengan tanda `*` di nomor:

- `SEKAR CANGKIR 7` → Bnt = 1
- `SEKAR NAMPAN` → Bnt = 1
- `SEKAR PIRING SOUP` → Bnt = 1
- Selain itu → Bnt = 0

### gR uRUT (Urutan Grup)

- `Merona` → 1, `Mo.re` → 2, `Modelux` → 3, selain itu → 999.
- Untuk family ENAMEL semua item bernilai 999, sehingga urutan murni berdasarkan nama barang (natural sort).

### Harga

| Kolom            | Data Source                                                         |
| ---------------- | ------------------------------------------------------------------- |
| Isi/Dus          | `PerDus` (level `04. Harga Akun Special`, dibulatkan tanpa desimal) |
| Harga Konsumen   | `PriceBeforeDisc(04. Harga Akun Special) / Conversion`              |
| AK Spesial (9 %) | `PriceAfterDisc(04. Harga Akun Special) / Conversion`               |

Catatan:
- Jika terdapat lebih dari satu baris `04. Harga Akun Special` untuk item yang sama, baris terakhir yang menang (contoh `SEKAR NAMPAN 30 CM DECO` memiliki dua baris level 04, baris terakhir `Conversion = 5` menghasilkan 160,000 / 145,600 sesuai referensi).
- Nama barang ditampilkan apa adanya dari `PriceGroupDescription`. Formula legacy `nAMEuRYT` (yang mengubah `SEKAR CANGKIR TUTUP 9 CM M/MH` menjadi `SEKAR CANGKIR TUTUP 09 CM M/`) **tidak diterapkan** karena referensi menampilkan nama asli.
- Header kolom AK Spesial menampilkan `9 %` (dari formula `HargaAkunSpesial 2` untuk group selain Sekar2).

## Output

Format: PDF (A4, Portrait)

### Tabel

5 kolom:

| No  | Nama Barang | Isi/Dus | Harga Konsumen | AK Spesial (9 %) |
| --- | ----------- | ------- | -------------- | ---------------- |

Kolom nama barang pada referensi tidak memiliki header. Nomor diberi tanda `*` untuk item GSU (bnt = 1).

### Urutan Baris

Diurutkan berdasarkan nama barang (natural sort, misal `CANGKIR TUTUP 9` sebelum `CANGKIR TUTUP 10`).

### Catatan Kaki

1. Daftar harga berlaku per Tgl 6 April 2026
2. Franco Medan
3. Harga sewaktu waktu bisa berubah / tanpa pemberitahuan terlebih dahulu
4. Dengan berlaku nya Price List ini maka Price List sebelumnya tidak berlaku lagi
5. Harga berdasarkan Level Toko
6. Produk GSU tidak bisa diretur (tanda * di nomor)
