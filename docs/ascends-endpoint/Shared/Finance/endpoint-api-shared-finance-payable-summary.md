# Dokumentasi Hit Endpoint API Ascend Shared Finance Payable Summary

Dokumen ini berisi endpoint internal untuk test/render laporan Payable Summary Ascend yang memakai Blade shared.

- Base host: `http://192.168.10.100:5006`
- Prefix API: `/api`

## Konsep Shared Payable Summary

Template shared Payable Summary dipakai supaya struktur Blade laporan bisa digunakan lintas perusahaan.

## Endpoint Shared Payable Summary

- Payable Summary - Laporan Saldo Hutang (RU): `POST http://192.168.10.100:5006/api/internal/ascends/shared/finance/payable-summary/saldo-hutang-ru/pdf`

## Input

Parameter field utama untuk semua endpoint Ascends Shared Finance Payable Summary:

- `xml_file`: file XML dari Ascend.
- `DB_CompanyName`: nama/kode perusahaan dari parameter Crystal Report Ascend, contoh `RU`.
- `Sys_Username`: nama user print dari parameter Crystal Report Ascend, dipakai untuk footer `Dicetak oleh`, contoh `Ridho`.

Input XML alternatif yang tetap diterima untuk kebutuhan testing:

- field `xml` berisi string XML
- raw XML body dengan `Content-Type: application/xml`

Fallback kompatibilitas lama:

- `company`: fallback internal/test jika `DB_CompanyName` belum dikirim.
- `Sys_UserName`: alias lama untuk nama user print jika `Sys_Username` belum dikirim.

Input tambahan khusus `saldo-hutang-ru`:

- `PayableSummaryDate.StartDate` + `PayableSummaryDate.EndDate`: periode filter data, contoh `2026-06-01` sampai `2026-06-30`.
- Alias tanggal yang diterima: `start_date` + `end_date`, `StartDate` + `EndDate`, `PayableSummaryDate.StartDate` + `PayableSummaryDate.EndDate`.

Contoh `multipart/form-data`:

```text
DB_CompanyName=RU
Sys_Username=Ridho
PayableSummaryDate.StartDate=2026-06-01
PayableSummaryDate.EndDate=2026-06-30
xml_file=AnlReports.Finance.PayableSummary.xml
```

## Response Sukses

- `200 application/pdf`
- `Content-Disposition: attachment`

Title yang tampil di halaman PDF: `Laporan Saldo Hutang ({company})`

Filename PDF: `Payable Summary - Laporan Saldo Hutang ({company}).pdf`

## Response Gagal

- `422 application/json` jika XML kosong, tidak valid, atau tidak bisa diproses.

## Template Shared Payable Summary Tersedia

Template Blade shared Payable Summary berada di `resources/views/ascends/shared/finance/payable_summary`.

- `saldo_hutang_ru`
