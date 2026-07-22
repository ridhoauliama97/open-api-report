# Dokumentasi Hit Endpoint API Ascend Shared Sales By Item

Dokumen ini berisi endpoint internal untuk test/render laporan Sales By Item Ascend yang memakai Blade shared.

- Base host: `http://192.168.10.100:5006`
- Prefix API: `/api`

## Konsep Shared Sales By Item

Template shared Sales By Item dipakai supaya struktur Blade laporan bisa digunakan lintas perusahaan:

- `RU`
- `GSU`
- `UC`

Nama perusahaan pada title dan filename dibaca dari parameter field `DB_CompanyName`.
Nama user print pada footer dibaca dari parameter field `Sys_Username`.

## Endpoint

- Sales By Item - Laporan Penjualan Per Item Family: `POST http://192.168.10.100:5006/api/internal/ascends/shared/inventory_analysis/sales-by-item/penjualan-per-group-bulanan-ru/pdf`
- Sales By Item - Laporan Persentase HPP Penjualan Per Item Family: `POST http://192.168.10.100:5006/api/internal/ascends/shared/inventory_analysis/sales-by-item/persentase-hpp-penjualan-per-item-family-ru/pdf`

## Input

Parameter field utama:

- `xml_file`: file XML dari Ascend (`AnlReports.Inventory.SalesByItem.xml`).
- `DB_CompanyName`: nama/kode perusahaan, contoh `RU`, `GSU`, atau `UC`.
- `Sys_Username`: nama user print, contoh `Ridho`.
- `SalesDate.StartDate`: tanggal awal periode (contoh `2026-06-01`).
- `SalesDate.EndDate`: tanggal akhir periode (contoh `2026-06-30`).

Input XML alternatif yang tetap diterima:

- field `xml` berisi string XML
- raw XML body dengan `Content-Type: application/xml`

Fallback kompatibilitas lama:

- `company`: fallback internal/test jika `DB_CompanyName` belum dikirim.
- `Sys_UserName`: alias lama untuk nama user print jika `Sys_Username` belum dikirim.

Input tambahan:

- `SalesDate.StartDate` + `SalesDate.EndDate`: periode filter data penjualan.
- Alias tanggal yang diterima: `start_date` + `end_date`, `StartDate` + `EndDate`, `SalesDate.StartDate` + `SalesDate.EndDate`, dan variasi lainnya.
- Jika periode tidak dikirim, sistem memakai semua data yang tersedia di XML.

Contoh `multipart/form-data`:

```text
DB_CompanyName=RU
Sys_Username=Ridho
SalesDate.StartDate=2026-06-01
SalesDate.EndDate=2026-06-30
xml_file=AnlReports.Inventory.SalesByItem.xml
```

## Response Sukses

- `200 application/pdf`
- `Content-Disposition: attachment`

Title yang tampil di halaman PDF:

```text
Laporan Penjualan Per Item Family ({company})
```

Filename PDF:

```text
Sales By Item - Laporan Penjualan Per Item Family ({company}).pdf
```

Contoh:

- `Sales By Item - Laporan Penjualan Per Item Family (RU).pdf`
- `Sales By Item - Laporan Persentase HPP Penjualan Per Item Family (RU).pdf`

## Response Gagal

- `422 application/json` jika XML kosong, tidak valid, atau tidak bisa diproses.

## Struktur Laporan

Laporan dikelompokkan berdasarkan **Item Family Name** dengan urutan:

1. **Section Header** — Nama group (Item Family Name)
2. **Item Rows** — Detail per item dengan kolom:
   - Nama Item
   - Qty (dalam M3, format 4 desimal)
   - Penjualan (COGS + Laba Kotor)
   - HPP (COGS)
   - Laba Kotor
   - Total (%) (Laba Kotor / Penjualan * 100)
3. **Subtotal** — Total per group
4. **Grand Total** — Total keseluruhan

## Struktur Laporan Persentase HPP Penjualan Per Item Family

Laporan menampilkan 3 bulan berturut-turut berdasarkan `SalesDate.StartDate`:
- **Month2** = bulan dari StartDate
- **Month3** = Month2 + 1 bulan
- **Month4** = Month2 + 2 bulan

Setiap record dialokasikan ke bucket bulan berdasarkan `Invoice_Date`.

Kolom tabel (10 kolom):

| Nama Barang | {M2} Qty | {M2} (%) | {M3} Qty | {M3} (%) | {M4} Qty | {M4} (%) | Rata-rata | Min | Max |

**3 Level Perhitungan:**

| Level | Persen per Bulan | Rata-rata | Min | Max |
|---|---|---|---|---|
| **Item** (kode + nama) | RpGross item / RpMonth item * 100 | FrAvg (non-zero) | FrMin | FrMax |
| **Family** (by Item Family) | RpGross family / RpMonth family * 100 | AFrAvg 2 | AFrMin 2 | AFrMax 2 |
| **Grand Total** | RpGross total / RpMonth total * 100 | BFrAvg 3 | BFrMin 3 | BFrMax 3 |

- `RpMonth` = Penjualan (COGS + Laba Kotor)
- `RpGross` = Laba Kotor

## Template Blade

Template Blade berada di:

```
resources/views/ascends/shared/inventory_analysis/sales_by_item/penjualan_per_group_bulanan_ru/pdf.blade.php
resources/views/ascends/shared/inventory_analysis/sales_by_item/persentase_hpp_pernjualan_per_item_family_ru/pdf.blade.php
```
