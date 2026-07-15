# Dokumentasi Hit Endpoint API Ascend Shared Finance Pelunasan

Dokumen ini berisi endpoint internal untuk test/render laporan Pelunasan Ascend yang memakai Blade shared.

- Base host: `http://192.168.10.100:5006`
- Prefix API: `/api`

## Konsep Shared Finance Pelunasan

Template shared Finance Pelunasan dipakai supaya struktur Blade laporan bisa digunakan lintas perusahaan:

- `RU`
- `GSU`
- `UC`

Nama perusahaan pada title dan filename dibaca dari parameter field `DB_CompanyName`.
Nama user print pada footer dibaca dari parameter field `Sys_Username`.

## Endpoint

1. **Laporan Pelunasan**

   `POST http://192.168.10.100:5006/api/internal/ascends/shared/finance/receipt_voucher_details/pelunasan/pdf`

## Input

Parameter field utama:

- `xml_file`: file XML dari Ascend, file `AnlReports.Finance.ReceiptVoucherDetails.xml` berisi data voucher penerimaan (element `<Sukamu>`).
- `DB_CompanyName`: nama/kode perusahaan dari parameter Crystal Report Ascend, contoh `RU`, `GSU`, atau `UC`.
- `Sys_Username`: nama user print dari parameter Crystal Report Ascend, dipakai untuk footer `Print by`, contoh `Ridho`.
- `ItemDate.StartDate` + `ItemDate.EndDate`: periode laporan berdasarkan tanggal invoice, contoh `01/07/2026` sampai `15/07/2026`.
  - Alias yang diterima: `StartDate` + `EndDate`.

Input XML alternatif yang tetap diterima untuk kebutuhan testing:

- field `xml` berisi string XML
- raw XML body dengan `Content-Type: application/xml`

Fallback kompatibilitas lama:

- `company`: fallback internal/test jika `DB_CompanyName` belum dikirim.
- `Sys_UserName`: alias lama untuk nama user print jika `Sys_Username` belum dikirim.

### Struktur Data

Setiap record XML `<Sukamu>` adalah alokasi pembayaran voucher untuk satu invoice. Data dikelompokkan berdasarkan `Item Ref` (No Invoice) dan hanya menampilkan record dengan `Item Type = "AR Sales"`:

- **Grouped list** diurutkan berdasarkan Customer Name → Tanggal Invoice
- Satu baris per invoice (multiple payment untuk invoice yang sama digabung)
- Kolom: Customer, No Invoice, Tgl Invoice, Tgl Voucher, Line Total, Total Voucher, Age, Ket Hari, Status
- Grand Total di akhir (Total Line Total + Total Voucher)

### Formula Fields (Crystal → PHP)

| Crystal Formula | PHP Implementation |
|---|---|
| `Gab = Item Amount Paid - Item Debit Note + Item Credit Note` | `$gab = $amtPaid - $debitNote + $creditNote` |
| `SUM1 = IF CNT=1 THEN Item Amount` | `item_amount` dari first record dalam grup |
| `HasilHari = Max(Voucher Date) - Item Date` | `$hari = diffInDays(item_date, max(voucher_dates))` |
| `Ket Lunas = IF Item Amount - Sum(Gab) = 0 THEN 'Lunas'` | `$status = abs($itemAmount - $sumGab) < 0.01 ? 'Lunas' : 'Belum Lunas'` |
| `KetHari = ...` | Aging bucket based on HasilHari |

### Contoh Request

```text
DB_CompanyName=GSU
Sys_Username=Ridho
ItemDate.StartDate=01/07/2026
ItemDate.EndDate=15/07/2026
xml_file=AnlReports.Finance.ReceiptVoucherDetails.xml
```

## Response Sukses

- `200 application/pdf`
- `Content-Disposition: attachment`

Filename PDF:

- `Laporan Pelunasan - {company}.pdf`

## Response Gagal

- `422 application/json` jika XML kosong, tidak valid, atau tidak bisa diproses.
- `422 application/json` jika data tidak ditemukan untuk periode yang diminta.

## Template Blade Tersedia

Template Blade shared Finance Pelunasan berada di `resources/views/ascends/shared/finance/receipt_voucher_details/pelunasan`.

- `pdf` — Laporan Pelunasan (grouped list, 9 kolom)
