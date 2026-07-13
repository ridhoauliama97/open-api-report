# Dokumentasi Hit Endpoint API Ascend Shared Associate

Dokumen ini berisi endpoint internal untuk test/render laporan Associate Ascend yang memakai Blade shared.

- Base host: `http://192.168.10.100:5006`
- Prefix API: `/api`

## Konsep Shared Associate

Template shared Associate dipakai supaya struktur Blade laporan bisa digunakan lintas perusahaan:

- `RU`
- `GSU`
- `UC`

Nama perusahaan pada title dan filename dibaca dari parameter field `DB_CompanyName`.
Nama user print pada footer dibaca dari parameter field `Sys_Username`.

## Endpoint Shared Associate

- Customer Modifikasi 6 Bulan - Laporan Customer (Periode 1 Tahun): `POST http://192.168.10.100:5006/api/internal/ascends/shared/associate/customer-modifikasi-6-bulan/pdf`
- Customer Baru Per Tahun - Laporan Penambahan Customer Baru (Periode 1 Tahun): `POST http://192.168.10.100:5006/api/internal/ascends/shared/associate/customer-baru-per-tahun/pdf`
- Customer Baru - Laporan Customer Baru: `POST http://192.168.10.100:5006/api/internal/ascends/shared/associate/customer-baru/pdf`
- List Customer Per Kota - Laporan Data Customer Per Kota: `POST http://192.168.10.100:5006/api/internal/ascends/shared/associate/list-customer-per-kota/pdf`

## Input

Parameter field utama untuk semua endpoint Ascends Shared Associate:

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

Input tambahan khusus `customer-modifikasi-6-bulan`:

- `per_date`: tanggal referensi untuk menentukan batas 6 bulan terakhir, contoh `2026-06-30`.
- Alias yang diterima: `per_date`, `PerDate`, `Per Date`, `Per_x0020_Date`, `perDate`, `tanggal`, `Tanggal`, `date`, `Date`.
- Jika tidak dikirim, sistem memakai tanggal server saat ini.
- Data difilter: `Sales Person Code` tidak boleh kosong dan tidak diawali `SP-0011`.
- Laporan dibagi 2 section: Data Customer Yang Di Modifikasi 6 Bulan Terakhir (filter modified date >= 6 bulan sebelum per_date) dan Data Customer (sisanya).

Input tambahan khusus `customer-baru-per-tahun`:

- `tahun`: **wajib** — tahun laporan dalam format YYYY, contoh `2025`.
- Hanya menampilkan customer dengan `Status = Active` dan `Created Date/Time` berada di tahun tersebut.
- `Sales Person Code` tidak boleh kosong dan tidak diawali `SP-0011`.

Input tambahan khusus `customer-baru`:

- `tanggal`: **wajib** — tanggal referensi dalam format YYYY-MM-DD, contoh `2026-06-30`.
- Menampilkan customer baru dalam rentang 1 bulan setelah tanggal referensi.
- Hanya menampilkan customer dengan `Status = Active`.

Input tambahan khusus `list-customer-per-kota`:

- Tidak ada parameter filter tambahan.
- Hanya menampilkan customer dengan `Status = Active`.
- Data dikelompokkan per kota (`Billing City`), diurutkan berdasarkan kota kemudian kode customer.

Contoh `multipart/form-data` untuk Customer Modifikasi 6 Bulan:

```text
DB_CompanyName=RU
Sys_Username=Ridho
xml_file=AnlReports.Associate.Customer.xml
```

Contoh `multipart/form-data` untuk Customer Modifikasi 6 Bulan dengan tanggal referensi:

```text
DB_CompanyName=GSU
Sys_Username=Ridho
per_date=2026-06-30
xml_file=AnlReports.Associate.Customer.xml
```

Contoh `multipart/form-data` untuk Customer Baru Per Tahun 2025:

```text
DB_CompanyName=RU
tahun=2025
xml_file=AnlReports.Associate.Customer.xml
```

Contoh `multipart/form-data` untuk Customer Baru per 30 Juni 2026:

```text
DB_CompanyName=RU
tanggal=2026-06-30
xml_file=AnlReports.Associate.Customer.xml
```

Contoh `multipart/form-data` untuk List Customer Per Kota:

```text
DB_CompanyName=RU
xml_file=AnlReports.Associate.Customer.xml
```

## Response Sukses

- `200 application/pdf`
- `Content-Disposition: attachment`

Title yang tampil di halaman PDF memakai nama laporan. Nilai `{company}` berasal dari parameter `DB_CompanyName`, atau fallback field form `company` jika parameter tersebut tidak ada:

```text
{Nama Laporan} ({company})
```

Filename PDF memakai prefix section folder:

```text
Associate - {Nama Laporan} ({company}).pdf
```

Contoh:

- `Associate - Laporan Customer Modifikasi 6 Bulan Terakhir (Periode 1 Tahun) (RU).pdf`
- `Associate - Laporan Penambahan Customer Baru (Periode 1 Tahun) (RU).pdf`
- `Associate - Laporan Customer Baru (RU).pdf`
- `Associate - Laporan Data Customer Per Kota (RU).pdf`

## Response Gagal

- `422 application/json` jika XML kosong, tidak valid, atau tidak bisa diproses.
- `422 application/json` jika parameter wajib (`tahun`, `tanggal`) tidak dikirim.
- `422 application/json` jika data customer tidak ditemukan pada XML.

## Template Shared Associate Tersedia

Template Blade shared Associate berada di `resources/views/ascends/shared/associate`.

- `associate/customer_modifikasi_6_bulan_terakhir`
- `associate/customer_baru_per_tahun`
- `associate/customer_baru`
- `associate/list_customer`

Catatan: semua endpoint di atas memakai pola shared yang sama. XML menjadi sumber data laporan, sedangkan parameter `DB_CompanyName` menjadi sumber label perusahaan pada title dan filename. Field form `company` hanya fallback jika `DB_CompanyName` belum dikirim.
