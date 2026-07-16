# Dokumentasi Hit Endpoint API Ascend Shared GL Laba Rugi UC

Dokumen ini berisi endpoint internal untuk test/render laporan Laba Rugi UC yang memakai Blade shared.

- Base host: `http://192.168.10.100:5006`
- Prefix API: `/api`

## Konsep

Template shared Laba Rugi UC dipakai untuk menampilkan laporan laba/rugi dengan perbandingan dua bulan:

- Bulan saat ini (kolom pertama, misal: `Jun - 2026`)
- Bulan sebelumnya (kolom kedua, misal: `May - 2026`)
- Rasio terhadap total pendapatan dan selisih persentase (% BEDA)

## Endpoint

1. **Laporan Laba Rugi UC**

   `POST http://192.168.10.100:5006/api/internal/ascends/shared/general-ledger/journal-details/laba-rugi-uc/pdf`

## Input

Parameter field utama:

- `xml_file`: file XML dari Ascend, file `GL.AnalysisReports.JournalDetails.xml` berisi data jurnal (element `<invoices>`).
- `DB_CompanyName`: nama/kode perusahaan, contoh `UC`.
- `Sys_Username`: nama user print, contoh `Ridho`.
- `Date.StartDate` + `Date.EndDate`: periode laporan, contoh `01/06/2026` sampai `30/06/2026`.
  - Alias: `StartDate` + `EndDate`.

### Struktur Data

Data dari XML diklasifikasikan menggunakan **Account Code** dengan formula:

1. **AKM** — mapping 3-7 digit prefix account code ke kategori (PENJUALAN, BEBAN UMUM, BEBAN DIREKSI, PENDAPATAN JASA SEWA, dll.)
2. **AKL** — mapping AKM ke section laporan (PENDAPATAN, HARGA POKOK PENJUALAN, BEBAN USAHA, PENDAPATAN DAN BEBAN LAINNYA)
3. **Dua periode**: berdasarkan bulan voucher date vs start/end month
4. **Hierarki**: Section → Category → Item (3 level)
5. **Calculation row**: LABA KOTOR, LABA USAHA, LABA SEBELUM PAJAK, PAJAK, LABA BERSIH

### Contoh Request

```text
DB_CompanyName=UC
Sys_Username=Ridho
Date.StartDate=01/06/2026
Date.EndDate=30/06/2026
xml_file=GL.AnalysisReports.JournalDetails.xml
```

## Response Sukses

- `200 application/pdf`
- `Content-Disposition: attachment`

Filename PDF:

- `Laporan Laba Rugi - {company}.pdf`

## Response Gagal

- `422 application/json` jika XML kosong, tidak valid, atau tidak bisa diproses.

## Template Blade Tersedia

Template Blade berada di `resources/views/ascends/shared/general_ledger/journal_details/laporan_laba_rugi_uc`.

- `pdf` — Laporan Laba Rugi UC (6 kolom, landscape)
