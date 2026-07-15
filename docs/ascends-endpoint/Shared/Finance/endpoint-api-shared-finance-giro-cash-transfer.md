# Dokumentasi Hit Endpoint API Ascend Shared Finance Giro Cash Transfer

Dokumen ini berisi endpoint internal untuk test/render laporan Pembayaran Cash/Giro/Transfer Ascend yang memakai Blade shared.

- Base host: `http://192.168.10.100:5006`
- Prefix API: `/api`

## Konsep Shared Finance Giro Cash Transfer

Template shared Finance Giro Cash Transfer dipakai supaya struktur Blade laporan bisa digunakan lintas perusahaan:

- `RU`
- `GSU`
- `UC`

Nama perusahaan pada title dan filename dibaca dari parameter field `DB_CompanyName`.
Nama user print pada footer dibaca dari parameter field `Sys_Username`.

## Endpoint

1. **Laporan Pembayaran {PaymentMethod}**

   `POST http://192.168.10.100:5006/api/internal/ascends/shared/finance/receipt_voucher_details/giro-cash-transfer/pdf`

## Input

Parameter field utama:

- `xml_file`: file XML dari Ascend, file `AnlReports.Finance.ReceiptVoucherDetails.xml` berisi data voucher penerimaan (element `<Sukamu>`).
- `DB_CompanyName`: nama/kode perusahaan dari parameter Crystal Report Ascend, contoh `RU`, `GSU`, atau `UC`.
- `Sys_Username`: nama user print dari parameter Crystal Report Ascend, dipakai untuk footer `Print by`, contoh `Ridho`.
- `PymentMethod`: metode pembayaran, nilai yang diterima: `Cash`, `Giro`, `Transfer`.
- `ReceiptVoucherDate.StartDate` + `ReceiptVoucherDate.EndDate`: periode laporan berdasarkan tanggal voucher, contoh `01/07/2026` sampai `15/07/2026`.
  - Alias yang diterima: `StartDate` + `EndDate`.

Input XML alternatif yang tetap diterima untuk kebutuhan testing:

- field `xml` berisi string XML
- raw XML body dengan `Content-Type: application/xml`

Fallback kompatibilitas lama:

- `company`: fallback internal/test jika `DB_CompanyName` belum dikirim.
- `Sys_UserName`: alias lama untuk nama user print jika `Sys_Username` belum dikirim.

### Struktur Data

Setiap record XML `<Sukamu>` adalah alokasi pembayaran voucher untuk satu invoice. Data dikelompokkan berdasarkan `Voucher No.` dan ditampilkan satu baris per voucher:

- **Grouped list** diurutkan berdasarkan Sales Person → No Voucher
- Satu baris per voucher (multiple invoice allocations untuk voucher yang sama digabung)
- Kelompok subtotal per Sales Person
- Kolom: No Voucher, Nama Customer, Tgl Invoice, Tgl Voucher, Hari, Total
- Grand Total di akhir

### Selection Formula - Payment Method Filter

| Parameter Value | Filter |
|---|---|
| `Cash` | `{Payment Method} = "Cash"` |
| `Giro` | `{Payment Method} = "Check"` |
| `Transfer` | `{Payment Method} = "Transfer"` |

### Formula Fields (Crystal → PHP)

| Crystal Formula | PHP Implementation |
|---|---|
| `TglVoucher = if Giro then Check Due Date else Voucher Date` | `$tglVoucher = $isGiro ? $checkDueDate : $voucherDate` |
| `TglPelunasan = Voucher Date - Item Date (or Check Due Date for Giro)` | `$hari = $voucherCarbon->diffInDays($itemCarbon)` |
| `Sort = "A" for AR Sales Return, "B" for AR Sales` | Sorting within voucher group untuk menentukan last record |
| `Total = Total Amount Paid (Local)` | `$total` dari field voucher-level |

### Contoh Request (Cash)

```text
DB_CompanyName=GSU
Sys_Username=Ridho
PymentMethod=Cash
ReceiptVoucherDate.StartDate=01/07/2026
ReceiptVoucherDate.EndDate=15/07/2026
xml_file=AnlReports.Finance.ReceiptVoucherDetails.xml
```

### Contoh Request (Transfer)

```text
DB_CompanyName=GSU
Sys_Username=Ridho
PymentMethod=Transfer
ReceiptVoucherDate.StartDate=01/07/2026
ReceiptVoucherDate.EndDate=15/07/2026
xml_file=AnlReports.Finance.ReceiptVoucherDetails.xml
```

## Response Sukses

- `200 application/pdf`
- `Content-Disposition: attachment`

Filename PDF:

- `Laporan Pembayaran Cash - {company}.pdf`
- `Laporan Pembayaran Transfer - {company}.pdf`
- `Laporan Pembayaran Giro - {company}.pdf`

## Response Gagal

- `422 application/json` jika XML kosong, tidak valid, atau tidak bisa diproses.
- `422 application/json` jika data tidak ditemukan untuk periode yang diminta.
- `422 application/json` jika PymentMethod tidak valid.

## Template Blade Tersedia

Template Blade shared Finance Giro Cash Transfer berada di `resources/views/ascends/shared/finance/receipt_voucher_details/giro_cash_transfer`.

- `pdf` — Laporan Pembayaran Cash/Giro/Transfer (grouped list, 6 kolom)
