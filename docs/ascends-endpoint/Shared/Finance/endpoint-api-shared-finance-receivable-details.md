# Dokumentasi Hit Endpoint API Ascend Shared Finance Receivable Details

Dokumen ini berisi endpoint internal untuk test/render laporan Finance Receivable Details Ascend yang memakai Blade shared.

- Base host: `http://192.168.10.100:5006`
- Prefix API: `/api`

## Konsep Shared Finance Receivable Details

Template shared Finance Receivable Details dipakai supaya struktur Blade laporan bisa digunakan lintas perusahaan:

- `RU`
- `GSU`
- `UC`

Nama perusahaan pada title dan filename dibaca dari parameter field `DB_CompanyName`.
Nama user print pada footer dibaca dari parameter field `Sys_Username`.
Tanggal laporan dibaca dari parameter field `PerDate`.

## Endpoint

1. **Laporan Umur Piutang Diatas 45 Hari**

   `POST http://192.168.10.100:5006/api/internal/ascends/shared/finance/receivable_details/piutang-diatas-45-hari/pdf`

2. **Laporan Umur Piutang Diatas 60 Hari**

   `POST http://192.168.10.100:5006/api/internal/ascends/shared/finance/receivable_details/piutang-diatas-60-hari/pdf`

3. **Laporan Umur Piutang Diatas 120 Hari**

   `POST http://192.168.10.100:5006/api/internal/ascends/shared/finance/receivable_details/piutang-diatas-120-hari/pdf`

4. **Laporan Umur Piutang Semua**

   `POST http://192.168.10.100:5006/api/internal/ascends/shared/finance/receivable_details/piutang-semua/pdf`

5. **Laporan Umur Piutang Cash 14 Hari**

   `POST http://192.168.10.100:5006/api/internal/ascends/shared/finance/receivable_details/piutang-cash-14-hari/pdf`

6. **Laporan Piutang Tak Tertagih Di Atas 90 Hari**

   `POST http://192.168.10.100:5006/api/internal/ascends/shared/finance/receivable_details/piutang-tak-tertagih-90-hari/pdf`

## Input

Parameter field utama untuk semua endpoint Ascends Shared Finance Receivable Details:

- `xml_file`: file XML dari Ascend, file `AnlReports.Finance.ReceivableDetails.xml` berisi data piutang dagang (element `<ar>`).
- `DB_CompanyName`: nama/kode perusahaan dari parameter Crystal Report Ascend, contoh `RU`, `GSU`, atau `UC`.
- `Sys_Username`: nama user print dari parameter Crystal Report Ascend, dipakai untuk footer `Dicetak oleh`, contoh `Ridho`.
- `PerDate`: tanggal laporan, format `YYYY-MM-DD`, contoh `2026-07-14`. Tampil di subtitle PDF dengan format `DD-MMM-YY` (contoh: `14-Jul-26`).

Input XML alternatif yang tetap diterima untuk kebutuhan testing:

- field `xml` berisi string XML
- raw XML body dengan `Content-Type: application/xml`

Fallback kompatibilitas lama:

- `company`: fallback internal/test jika `DB_CompanyName` belum dikirim.
- `Sys_UserName`: alias lama untuk nama user print jika `Sys_Username` belum dikirim.

Catatan: `DB_CompanyName` dipakai lebih dulu dibanding field form `company`.

### Formula Fields per Laporan

#### Laporan Umur Piutang Diatas 45 Hari

Selection: `{ar.Age (Days)} > 44`

Aging buckets:

| Bucket | Formula |
|---|---|
| 45 - 60 Hari | `if {ar.Age (Days)} >= 45 and {ar.Age (Days)} <= 60 then {ar.Balance} else 0` |
| 61 - 90 Hari | `if {ar.Age (Days)} >= 61 and {ar.Age (Days)} <= 90 then {ar.Balance} else 0` |
| 91 - 120 Hari | `if {ar.Age (Days)} >= 91 and {ar.Age (Days)} <= 120 then {ar.Balance} else 0` |
| > 120 Hari | `if {ar.Age (Days)} > 120 then {ar.Balance} else 0` |

Kolom tabel: Nama Pelanggan \| No. Invoice \| Umur \| 45-60 Hari \| 61-90 Hari \| 91-120 Hari \| > 120 Hari \| Saldo Piutang

#### Laporan Umur Piutang Diatas 60 Hari

Selection: `{ar.Age (Days)} > 60`

Aging buckets:

| Bucket | Formula |
|---|---|
| 60 - 75 Hari | `if {ar.Age (Days)} >= 60 and {ar.Age (Days)} <= 75 then {ar.Balance} else 0` |
| 76 - 90 Hari | `if {ar.Age (Days)} >= 76 and {ar.Age (Days)} <= 90 then {ar.Balance} else 0` |
| 91 - 120 Hari | `if {ar.Age (Days)} >= 91 and {ar.Age (Days)} <= 120 then {ar.Balance} else 0` |
| > 120 Hari | `if {ar.Age (Days)} > 120 then {ar.Balance} else 0` |

Kolom tabel: Nama Pelanggan \| Tgl Invoice \| No. Ref \| 60-75 Hari \| 76-90 Hari \| 91-120 Hari \| > 120 Hari

#### Laporan Umur Piutang Diatas 120 Hari

Selection: `{ar.Age (Days)} > 120`

Aging buckets:

| Bucket | Formula |
|---|---|
| 120 - 240 Hari | `if {ar.Age (Days)} >= 120 and {ar.Age (Days)} <= 240 then {ar.Balance} else 0` |
| 241 - 360 Hari | `if {ar.Age (Days)} >= 241 and {ar.Age (Days)} <= 360 then {ar.Balance} else 0` |
| 361 - 480 Hari | `if {ar.Age (Days)} >= 361 and {ar.Age (Days)} <= 480 then {ar.Balance} else 0` |
| 481 - 600 Hari | `if {ar.Age (Days)} >= 481 and {ar.Age (Days)} <= 600 then {ar.Balance} else 0` |
| > 600 Hari | `if {ar.Age (Days)} > 600 then {ar.Balance} else 0` |

Kolom tabel (Detail): Nama Pelanggan \| No. Invoice \| Umur \| 120-240 Hari \| 241-360 Hari \| 361-480 Hari \| 481-600 Hari \| > 600 Hari \| Saldo Piutang

Kolom tabel (Rincian Per Salesman): Nama Salesman \| Total Cust. \| 120-240 Hari \| 241-360 Hari \| 361-480 Hari \| 481-600 Hari \| > 600 Hari \| Total

#### Laporan Umur Piutang Semua

Selection: *No filter — semua record ditampilkan (age 0 s/d >120)*

Aging buckets:

| Bucket | Formula |
|---|---|
| 001 - 044 Hari | `if {ar.Age (Days)} >= 0 and {ar.Age (Days)} <= 44 then {ar.Balance} else 0` |
| 045 - 060 Hari | `if {ar.Age (Days)} >= 45 and {ar.Age (Days)} <= 60 then {ar.Balance} else 0` |
| 061 - 090 Hari | `if {ar.Age (Days)} >= 61 and {ar.Age (Days)} <= 90 then {ar.Balance} else 0` |
| 091 - 120 Hari | `if {ar.Age (Days)} >= 91 and {ar.Age (Days)} <= 120 then {ar.Balance} else 0` |
| > 120 Hari | `if {ar.Age (Days)} > 120 then {ar.Balance} else 0` |

Kolom tabel (Detail): Nama Pelanggan \| No. Invoice \| Umur \| 1-44 Hari \| 45-60 Hari \| 61-90 Hari \| 91-120 Hari \| > 120 Hari \| Saldo Piutang

Kolom tabel (Rincian Per Salesman): Nama Salesman \| Total Cust. \| 01-44 Hari \| 45-60 Hari \| 61-90 Hari \| 91-120 Hari \| > 120 Hari \| Total

#### Laporan Umur Piutang Cash 14 Hari

Selection: `{ar.Invoice TOP} >= 1 and {ar.Invoice TOP} <= 15`

Formula umur: `today - {ar.Item Date}` (hari sejak tanggal invoice)

Kolom tabel: No. Invoice \| Nama Pelanggan \| Tgl Invoice \| Nama Salesman \| TOP \| Umur \| Saldo Piutang

#### Laporan Piutang Tak Tertagih Di Atas 90 Hari

Selection: `{ar.Age (Days)} > 90`

Formula persen: `({ar.Balance (Local)} / Sum({ar.Balance (Local)})) * 100`

Kolom tabel: No \| Nama Pelanggan \| Umur \| Status \| % \| Saldo Piutang

### Contoh Request

#### Laporan Umur Piutang Diatas 45 Hari

Contoh `multipart/form-data`:

```text
DB_CompanyName=GSU
Sys_Username=Ridho
PerDate=2026-07-14
xml_file=AnlReports.Finance.ReceivableDetails.xml
```

#### Laporan Umur Piutang Diatas 60 Hari

Contoh `multipart/form-data`:

```text
DB_CompanyName=GSU
Sys_Username=Ridho
PerDate=2026-07-14
xml_file=AnlReports.Finance.ReceivableDetails.xml
```

#### Laporan Umur Piutang Diatas 120 Hari

Contoh `multipart/form-data`:

```text
DB_CompanyName=GSU
Sys_Username=Ridho
PerDate=2026-07-14
xml_file=AnlReports.Finance.ReceivableDetails.xml
```

#### Laporan Umur Piutang Semua

Contoh `multipart/form-data`:

```text
DB_CompanyName=GSU
Sys_Username=Ridho
PerDate=2026-07-14
xml_file=AnlReports.Finance.ReceivableDetails.xml
```

#### Laporan Umur Piutang Cash 14 Hari

Contoh `multipart/form-data`:

```text
DB_CompanyName=GSU
Sys_Username=Ridho
PerDate=2026-07-14
xml_file=AnlReports.Finance.ReceivableDetails.xml
```

#### Laporan Piutang Tak Tertagih Di Atas 90 Hari

Contoh `multipart/form-data`:

```text
DB_CompanyName=GSU
Sys_Username=Ridho
PerDate=2026-07-14
xml_file=AnlReports.Finance.ReceivableDetails.xml
```

## Response Sukses

- `200 application/pdf`
- `Content-Disposition: attachment`

Filename PDF:

- `Laporan Umur Piutang Diatas 45 Hari - {company}.pdf`
- `Laporan Umur Piutang Diatas 60 Hari - {company}.pdf`
- `Laporan Umur Piutang Diatas 120 Hari - {company}.pdf`
- `Laporan Umur Piutang Semua - {company}.pdf`
- `Laporan Umur Piutang Cash 14 Hari - {company}.pdf`
- `Laporan Piutang Tak Tertagih Di Atas 90 Hari - {company}.pdf`

## Response Gagal

- `422 application/json` jika XML kosong, tidak valid, atau tidak bisa diproses.
- `422 application/json` jika data tidak ditemukan untuk umur piutang yang diminta.

## Template Blade Tersedia

Template Blade shared Finance Receivable Details berada di `resources/views/ascends/shared/finance/receivable_details`.

- `receivable_details/piutang_diatas_45_hari` — Laporan Umur Piutang Diatas 45 Hari (8 kolom, portrait, group by customer)
- `receivable_details/piutang_diatas_60_hari` — Laporan Umur Piutang Diatas 60 Hari (7 kolom, portrait, group by salesman)
- `receivable_details/piutang_diatas_120_hari` — Laporan Umur Piutang Diatas 120 Hari (9 kolom detail + 8 kolom salesman, portrait, group by customer)
- `receivable_details/piutang_semua` — Laporan Umur Piutang Semua (9 kolom detail + 8 kolom salesman, portrait, group by customer, tanpa filter umur)
- `receivable_details/piutang_cash_14_hari` — Laporan Umur Piutang Cash 14 Hari (7 kolom flat list, portrait, filter TOP 1-15)
- `receivable_details/piutang_tak_tertagih_90_hari` — Laporan Piutang Tak Tertagih Di Atas 90 Hari (6 kolom, portrait, filter Age > 90, persentase)

Catatan: semua endpoint di atas memakai pola shared yang sama. XML menjadi sumber data laporan, sedangkan parameter `DB_CompanyName` menjadi sumber label perusahaan pada title dan filename. Field form `company` hanya fallback jika `DB_CompanyName` belum dikirim.
