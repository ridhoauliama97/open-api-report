# Dokumentasi Hit Endpoint API Ascend Shared Fixed Asset

Dokumen ini berisi endpoint internal untuk test/render laporan Fixed Asset Ascend yang memakai Blade shared.

- Base host: `http://192.168.10.100:5006`
- Prefix API: `/api`

## Konsep Shared Fixed Asset

Template shared Fixed Asset dipakai supaya struktur Blade laporan bisa digunakan lintas perusahaan:

- `RU`
- `GSU`
- `UC`

Nama perusahaan pada title dan filename dibaca dari parameter field `DB_CompanyName`.
Nama user print pada footer dibaca dari parameter field `Sys_Username`.

## Endpoint Shared Fixed Asset

- Asset Summary - Laporan Daftar Penyusutan Aktiva Tetap: `POST http://192.168.10.100:5006/api/internal/ascends/shared/fixed_asset/asset_summary/penyusutan_aktiva/pdf`

## Input

Parameter field utama untuk semua endpoint Ascends Shared Fixed Asset:

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

### Filter Periode

- `Date.StartDate` + `Date.EndDate`: periode laporan penyusutan, contoh `2026-01-01` sampai `2026-01-31`.
- Alias yang diterima: `Date_StartDate` + `Date_EndDate`, `StartDate` + `EndDate`, `start_date` + `end_date`.
- Jika tidak dikirim, sistem memakai tanggal paling awal sampai paling akhir yang tersedia di data XML.

Catatan: Kategori `MESIN & PERLATAN PABRIK` tidak ditampilkan. Kode aset dengan prefix `KP-004`, `MS-1360`, `MSP-141`, dan `TN-1010` juga tidak ditampilkan.

Contoh `multipart/form-data`:

```text
DB_CompanyName=RU
Sys_Username=Ridho
Date.StartDate=2026-01-01
Date.EndDate=2026-01-31
xml_file=AnlReports.FixedAsset.AssetSummary.xml
```

Contoh tanpa parameter tanggal (periode otomatis dari data):

```text
DB_CompanyName=RU
Sys_Username=Ridho
xml_file=AnlReports.FixedAsset.AssetSummary.xml
```

## Response Sukses

- `200 application/pdf`
- `Content-Disposition: attachment`

Title yang tampil di halaman PDF tetap memakai nama laporan. Nilai `{company}` berasal dari parameter `DB_CompanyName`:

```text
{Nama Laporan} ({company})
```

Filename PDF memakai prefix section folder:

```text
Asset Summary - {Nama Laporan} ({company}).pdf
```

Contoh:

- `Asset Summary - Laporan Daftar Penyusutan Aktiva Tetap (RU).pdf`

## Response Gagal

- `422 application/json` jika XML kosong, tidak valid, atau tidak bisa diproses.
- `422 application/json` jika data aktiva tetap tidak ditemukan pada XML.

## Template Shared Fixed Asset Tersedia

Template Blade shared Fixed Asset berada di `resources/views/ascends/shared/fixed_asset/asset_summary`.

- `fixed_asset/asset_summary/penyusutan_aktiva`

Catatan: semua endpoint di atas memakai pola shared yang sama. XML menjadi sumber data laporan, sedangkan parameter `DB_CompanyName` menjadi sumber label perusahaan pada title dan filename. Field form `company` hanya fallback jika `DB_CompanyName` belum dikirim.
