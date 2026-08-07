# Dokumentasi Endpoint API Ascend Shared Laporan Pembelian Kayu Per Periode

Dokumen ini berisi endpoint internal untuk test/render laporan **Laporan Pembelian Kayu Per Periode** Ascend yang memakai Blade shared.

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

### Laporan Pembelian Kayu Per Periode

`POST /api/internal/ascends/shared/custom-report/pembelian-kayu-per-periode/pdf`

**Route name:** `api.internal.ascends.shared.custom-report.pembelian-kayu-per-periode.pdf`

**Controller:** `AscendXmlTestController::apiSharedCustomReportPembelianKayuPerPeriodePdf`

**Deskripsi:** Laporan pembelian kayu bulat per periode, dikelompokkan per satuan (UOM: `KG`, `TON`). Untuk tiap satuan menampilkan tabel detail (per supplier & per item kayu), baris subtotal per supplier dengan persen kontribusi, total satuan, lalu tabel rangkuman per supplier.

## Input

Parameter field utama:

- `xml_file`: file XML dari Ascend (contoh `CustomReport.xml`).
- `DB_CompanyName`: nama/kode perusahaan, contoh `RU`, `GSU`, atau `UC` (**wajib**).
- `Sys_Username`: nama user print, contoh `Ridho`.
- `StartDate`: tanggal awal periode (contoh `2026-07-01`).
- `EndDate`: tanggal akhir periode (contoh `2026-07-31`).

Input XML alternatif yang tetap diterima:

- field `xml` berisi string XML
- raw XML body dengan `Content-Type: application/xml`

Fallback kompatibilitas lama:

- `company`: fallback internal/test jika `DB_CompanyName` belum dikirim.
- `Sys_UserName`: alias lama untuk nama user print.
- `start_date` + `end_date`: alias untuk `StartDate` + `EndDate`.

Contoh `multipart/form-data`:

```text
DB_CompanyName=RU
Sys_Username=Ridho
StartDate=2026-07-01
EndDate=2026-07-31
xml_file=CustomReport.xml
```

## Sumber XML

Setiap record `<Table>` memuat minimal field berikut:

- `PurchaseDate` — tanggal pembelian (dipakai filter rentang tanggal).
- `SupplierName` — nama supplier (grup baris).
- `ItemName` — nama item kayu (baris detail).
- `Quantity` — jumlah (dipakai agregasi qty).
- `UOMCode` — satuan, `KG` / `TON` (grup section).
- `Hasil` — total nilai setelah diskon (dipakai agregasi total).

Field `Hasil` (bukan `LineTotal`) yang dipakai untuk kolom Total, konsisten dengan PDF referensi.

## Response Sukses

- `200 application/pdf`
- `Content-Disposition: attachment`

Title yang tampil di halaman PDF:

```text
Laporan Pembelian Kayu Per Periode
```

Filename PDF:

```text
Laporan Pembelian Kayu Per Periode {company}.pdf
```

## Response Gagal

- `422 application/json` jika XML kosong, tidak valid, atau tidak bisa diproses.
- `422 application/json` jika `DB_CompanyName` tidak dikirim.
- `422 application/json` jika tidak ada data dalam rentang tanggal yang dipilih.

## Struktur Laporan

Laporan dikelompokkan berdasarkan **satuan** (`UOMCode`, urutan kemunculan pertama: `KG`, `TON`). Untuk setiap satuan:

1. **Section Header** — label `Satuan : {UOM}` dan `Periode : {bulan} - {tahun}` (bulan dari data terawal, contoh `Jul - 2026`).
2. **Detail Table** (5 kolom: `Supplier | Item Name | Quantity | UOM | Total`):
   - Dikelompokkan per supplier; item diurutkan berdasarkan **qty menurun**.
   - Kolom Supplier hanya diisi di baris pertama tiap grup supplier.
   - Baris subtotal: `Total {supplier} {persen}%` (persen 1 desimal = qty supplier / qty total satuan × 100), lalu Qty, UOM, Total.
   - Grand total: baris `Total` dan baris `Total Satuan {UOM}` (keduanya bernilai sama, sesuai referensi).
3. **Rangkuman Table** — kolom `Supplier Name | % | Quantity | Total`:
   - Persen 2 desimal per supplier.
   - Baris `Total` (tanpa persen).
4. Baris-baris digabung: qty per supplier = `SUM(Quantity)`, total per item = `SUM(Hasil)`.

Urutan supplier dalam section juga berdasarkan **qty menurun** (cocok dengan PDF referensi).

Format portrait A4 dengan font Noto Serif.

## Template Blade

Template Blade berada di:

```
resources/views/ascends/shared/custom_report/pembelian_kayu_per_periode/pdf.blade.php
```

Semua tanggal pada PDF memakai format Indonesia (`Dari DD-MMM-YY s/d DD-MMM-YY`), angka memakai format Indonesia (ribuan memakai titik, desimal memakai koma).

## Formula Field

- `Persen = Sum({Table.Quantity}, {Table.SupplierName}) / Sum({Table.Quantity}, {Table.PurchaseDate}, "monthly") × 100`
- `Suppl = {Table.SupplierName}`