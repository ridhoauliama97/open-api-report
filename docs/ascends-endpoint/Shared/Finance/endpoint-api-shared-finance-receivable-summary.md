# Dokumentasi Hit Endpoint API Ascend Shared Finance Receivable Summary

Dokumen ini berisi endpoint internal untuk test/render laporan Receivable Summary Ascend yang memakai Blade shared.

- Base host: `http://192.168.10.100:5006`
- Prefix API: `/api`

## Konsep Shared Receivable Summary

Template shared Receivable Summary dipakai supaya struktur Blade laporan bisa digunakan lintas perusahaan.

## Endpoint Shared Receivable Summary

- Receivable Summary - Laporan Umur Piutang Dagang (RU): `POST http://192.168.10.100:5006/api/internal/ascends/shared/finance/receivable-summary/umur-piutang-ru/pdf`

## Input

Parameter field utama untuk semua endpoint Ascends Shared Finance Receivable Summary:

- `xml_file`: file XML dari Ascend.
- `DB_CompanyName`: nama/kode perusahaan dari parameter Crystal Report Ascend, contoh `RU`.
- `Sys_Username`: nama user print dari parameter Crystal Report Ascend, dipakai untuk footer `Dicetak oleh`, contoh `Ridho`.

Input XML alternatif yang tetap diterima untuk kebutuhan testing:

- field `xml` berisi string XML
- raw XML body dengan `Content-Type: application/xml`

Fallback kompatibilitas lama:

- `company`: fallback internal/test jika `DB_CompanyName` belum dikirim.
- `Sys_UserName`: alias lama untuk nama user print jika `Sys_Username` belum dikirim.

Input tambahan khusus `umur-piutang-ru`:

- `ReceivableSummaryDate.StartDate` + `ReceivableSummaryDate.EndDate`: periode filter data, contoh `2026-06-01` sampai `2026-06-30`.
- Alias tanggal yang diterima: `start_date` + `end_date`, `StartDate` + `EndDate`, `ReceivableSummaryDate.StartDate` + `ReceivableSummaryDate.EndDate`.

Contoh `multipart/form-data`:

```text
DB_CompanyName=RU
Sys_Username=Ridho
ReceivableSummaryDate.StartDate=2026-06-01
ReceivableSummaryDate.EndDate=2026-06-30
xml_file=AnlReports.Finance.ReceivableSummary.xml
```

## Response Sukses

- `200 application/pdf`
- `Content-Disposition: attachment`

Title yang tampil di halaman PDF: `Laporan Umur Piutang Dagang ({company})`

Filename PDF: `Receivable Summary - Laporan Umur Piutang Dagang ({company}).pdf`

## Response Gagal

- `422 application/json` jika XML kosong, tidak valid, atau tidak bisa diproses.

## Template Shared Receivable Summary Tersedia

Template Blade shared Receivable Summary berada di `resources/views/ascends/shared/finance/receivable_summary`.

- `umur_piutang_ru`
