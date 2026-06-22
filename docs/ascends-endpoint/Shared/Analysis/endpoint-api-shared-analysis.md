# Dokumentasi Hit Endpoint API Ascend Shared Analysis

Dokumen ini berisi endpoint internal untuk test/render laporan Analysis Ascend yang memakai Blade shared.

- Base host: `http://192.168.10.100:5006`
- Prefix API: `/api`

## Konsep Shared Analysis

Template shared Analysis dipakai supaya struktur Blade laporan bisa digunakan lintas perusahaan:

- `RU`
- `GSU`
- `UC`

Nama perusahaan pada title dan filename dibaca dari parameter field `DB_CompanyName`.
Nama user print pada footer dibaca dari parameter field `Sys_Username`.

## Endpoint Shared Adjustment By Item

- Adjustment By Item - Laporan Penyesuaian Persediaan: `POST http://192.168.10.100:5006/api/internal/ascends/shared/analysis/adjustment-by-item/penyesuaian-persediaan/pdf`

## Endpoint Shared Adjustment Kursi

- Adjustment By Item - Laporan Adjustment Selisih Kursi: `POST http://192.168.10.100:5006/api/internal/ascends/shared/analysis/adjustment-by-item/adjustment/khusus-kursi/pdf`

## Endpoint Shared Adjustment Lemari

- Adjustment By Item - Laporan Adjustment Selisih Lemari: `POST http://192.168.10.100:5006/api/internal/ascends/shared/analysis/adjustment-by-item/adjustment/khusus-lemari/pdf`

## Input

Parameter field utama untuk semua endpoint Ascends Shared Analysis:

- `xml_file`: file XML dari Ascend.
- `DB_CompanyName`: nama/kode perusahaan dari parameter Crystal Report Ascend, contoh `RU`, `GSU`, atau `UC`.
- `Sys_Username`: nama user print dari parameter Crystal Report Ascend, dipakai untuk footer `Dicetak oleh`, contoh `Ridho`.

Input XML alternatif yang tetap diterima untuk kebutuhan testing:

- field `xml` berisi string XML
- raw XML body dengan `Content-Type: application/xml`

Fallback kompatibilitas lama:

- `company`: fallback internal/test jika `DB_CompanyName` belum dikirim.
- `Sys_UserName`: alias lama untuk nama user print jika `Sys_Username` belum dikirim.

Catatan: `DB_CompanyName` dipakai lebih dulu dibanding field form `company`.

Input tambahan khusus `penyesuaian-persediaan`, `khusus-kursi`, dan `khusus-lemari`:

- `AdjustmentDate.StartDate` + `AdjustmentDate.EndDate`: periode filter data adjustment, contoh `2026-05-10` sampai `2026-05-31`.
- Alias tanggal yang diterima: `start_date` + `end_date`, `StartDate` + `EndDate`, `TglAwal` + `TglAkhir`, `date_start` + `date_end`, `dari_tanggal` + `sampai_tanggal`, dan `AdjustmentDate.StartDatee` (typo variant).
- Jika periode tidak dikirim, sistem memakai tanggal paling awal sampai paling akhir yang tersedia di data XML.

Contoh `multipart/form-data`:

```text
DB_CompanyName=GSU
Sys_Username=Ridho
AdjustmentDate.StartDate=2026-05-10
AdjustmentDate.EndDate=2026-05-31
xml_file=AnlReports.Inventory.AdjustmentByItem.xml
```

Contoh tanpa parameter tanggal (periode otomatis dari data):

```text
DB_CompanyName=GSU
Sys_Username=Ridho
xml_file=AnlReports.Inventory.AdjustmentByItem.xml
```

## Response Sukses

- `200 application/pdf`
- `Content-Disposition: inline`

Title yang tampil di halaman PDF tetap memakai nama laporan tanpa prefix kategori. Nilai `{company}` berasal dari parameter `DB_CompanyName`, atau fallback field form `company` jika parameter tersebut tidak ada:

```text
{Nama Laporan} ({company})
```

Filename PDF memakai prefix section folder:

```text
{Section} - {Nama Laporan} ({company}).pdf
```

Contoh:

- `Adjustment By Item - Laporan Penyesuaian Persediaan (GSU).pdf`
- `Adjustment By Item - Laporan Penyesuaian Persediaan (RU).pdf`
- `Adjustment By Item - Laporan Adjustment Selisih Kursi (GSU).pdf`
- `Adjustment By Item - Laporan Adjustment Selisih Lemari (GSU).pdf`

## Response Gagal

- `422 application/json` jika XML kosong, tidak valid, atau tidak bisa diproses.

## Template Shared Analysis Tersedia

Template Blade shared Adjustment By Item berada di `resources/views/ascends/shared/analysis/adjustment_by_item`.

- `adjustment_by_item/penyesuaian_persediaan`
- `adjustment_by_item/adjustment/khusus_kursi`
- `adjustment_by_item/adjustment/khusus_lemari`

Catatan: semua endpoint di atas memakai pola shared yang sama. XML menjadi sumber data laporan, sedangkan parameter `DB_CompanyName` menjadi sumber label perusahaan pada title dan filename. Field form `company` hanya fallback jika `DB_CompanyName` belum dikirim.
