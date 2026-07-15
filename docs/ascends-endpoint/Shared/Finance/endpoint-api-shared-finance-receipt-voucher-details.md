# Dokumentasi Hit Endpoint API Ascend Shared Finance Receipt Voucher Details

Dokumen ini berisi endpoint internal untuk test/render laporan Finance Receipt Voucher Details Ascend yang memakai Blade shared.

- Base host: `http://192.168.10.100:5006`
- Prefix API: `/api`

## Konsep Shared Finance Receipt Voucher Details

Template shared Finance Receipt Voucher Details dipakai supaya struktur Blade laporan bisa digunakan lintas perusahaan:

- `RU`
- `GSU`
- `UC`

Nama perusahaan pada title dan filename dibaca dari parameter field `DB_CompanyName`.
Nama user print pada footer dibaca dari parameter field `Sys_Username`.

## Endpoint

1. **Laporan Penerimaan Voucher (Intensif Penagihan)**

   `POST http://192.168.10.100:5006/api/internal/ascends/shared/finance/receipt_voucher_details/penerimaan-voucher/pdf`

## Input

Parameter field utama:

- `xml_file`: file XML dari Ascend, file `AnlReports.Finance.ReceiptVoucherDetails.xml` berisi data voucher penerimaan (element `<Sukamu>`).
- `DB_CompanyName`: nama/kode perusahaan dari parameter Crystal Report Ascend, contoh `RU`, `GSU`, atau `UC`.
- `Sys_Username`: nama user print dari parameter Crystal Report Ascend, dipakai untuk footer `Dicetak oleh`, contoh `Ridho`.
- `ReceiptVoucherDate.StartDate` + `ReceiptVoucherDate.EndDate`: periode laporan, contoh `01/06/2026` sampai `30/06/2026`.
  - Alias yang diterima: `StartDate` + `EndDate`.

Input XML alternatif yang tetap diterima untuk kebutuhan testing:

- field `xml` berisi string XML
- raw XML body dengan `Content-Type: application/xml`

Fallback kompatibilitas lama:

- `company`: fallback internal/test jika `DB_CompanyName` belum dikirim.
- `Sys_UserName`: alias lama untuk nama user print jika `Sys_Username` belum dikirim.

### Struktur Data

Setiap record XML `<Sukamu>` adalah alokasi pembayaran voucher untuk satu invoice. Struktur laporan:

- **Collector** → **Customer** → **Invoice** (info + sub-tabel voucher) → Total + Sisa

### Contoh Request

```text
DB_CompanyName=GSU
Sys_Username=Ridho
ReceiptVoucherDate.StartDate=01/06/2026
ReceiptVoucherDate.EndDate=30/06/2026
xml_file=AnlReports.Finance.ReceiptVoucherDetails.xml
```

## Response Sukses

- `200 application/pdf`
- `Content-Disposition: attachment`

Filename PDF:

- `Laporan Penerimaan Voucher (Intensif Penagihan) - {company}.pdf`

## Response Gagal

- `422 application/json` jika XML kosong, tidak valid, atau tidak bisa diproses.
- `422 application/json` jika data tidak ditemukan untuk periode yang diminta.

## Template Blade Tersedia

Template Blade shared Finance Receipt Voucher Details berada di `resources/views/ascends/shared/finance/receipt_voucher_details`.

- `receipt_voucher_details/penerimaan_voucher` — Laporan Penerimaan Voucher (Intensif Penagihan) (group by collector → customer → invoice, sub-tabel 4 kolom)

Catatan: XML menjadi sumber data laporan, sedangkan parameter `DB_CompanyName` menjadi sumber label perusahaan pada title dan filename. Field form `company` hanya fallback jika `DB_CompanyName` belum dikirim.
