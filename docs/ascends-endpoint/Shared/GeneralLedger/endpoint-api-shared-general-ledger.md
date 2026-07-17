# Dokumentasi Hit Endpoint API Ascend Shared General Ledger

Dokumen ini berisi endpoint internal untuk test/render laporan General Ledger Ascend yang memakai Blade shared.

- Base host: `http://192.168.10.100:5006`
- Prefix API: `/api`

## Konsep Shared General Ledger

Template shared General Ledger dipakai supaya struktur Blade laporan bisa digunakan lintas perusahaan:

- `RU`
- `GSU`
- `UC`

Nama perusahaan pada title dan filename dibaca dari parameter field `DB_CompanyName`.
Nama user print pada footer dibaca dari parameter field `Sys_Username`.

## Endpoint Shared Journal Details

- Laporan Laba Rugi (RU): `POST http://192.168.10.100:5006/api/internal/ascends/shared/general_ledger/journal_details/laporan-laba-rugi-ru/pdf`
- Laporan Laba Rugi (UC): `POST http://192.168.10.100:5006/api/internal/ascends/shared/general_ledger/journal_details/laporan-laba-rugi-uc/pdf`
- Laporan Pendapatan Dan Biaya Lain-Lain: `POST http://192.168.10.100:5006/api/internal/ascends/shared/general_ledger/journal_details/pendapatan-dan-biaya-lain/pdf`
- Laporan Pendapatan Dan Biaya Lain-Lain Baru: `POST http://192.168.10.100:5006/api/internal/ascends/shared/general_ledger/journal_details/pendapatan-dan-biaya-lain-baru/pdf`
- Laporan Beban Umum (UC): `POST http://192.168.10.100:5006/api/internal/ascends/shared/general_ledger/journal_details/beban-umum-uc/pdf`
- Laporan Beban Umum (RU): `POST http://192.168.10.100:5006/api/internal/ascends/shared/general_ledger/journal_details/beban-umum-ru/pdf`
- Laporan Beban Umum (GSU): `POST http://192.168.10.100:5006/api/internal/ascends/shared/general_ledger/journal_details/beban-umum-gsu/pdf`
- Laporan Beban: `POST http://192.168.10.100:5006/api/internal/ascends/shared/general_ledger/journal_details/beban/pdf`
- Laporan Beban Penjualan: `POST http://192.168.10.100:5006/api/internal/ascends/shared/general_ledger/journal_details/beban-penjualan/pdf`
- Laporan Beban Penjualan Summary: `POST http://192.168.10.100:5006/api/internal/ascends/shared/general_ledger/journal_details/beban-penjualan-summary/pdf`
- Laporan Biaya Upah Langsung: `POST http://192.168.10.100:5006/api/internal/ascends/shared/general_ledger/journal_details/biaya-upah-langsung-detail/pdf`
- Laporan Biaya Beban Umum: `POST http://192.168.10.100:5006/api/internal/ascends/shared/general_ledger/journal_details/beban-umum-baru/pdf`
- Laporan Biaya Produksi Tidak Langsung: `POST http://192.168.10.100:5006/api/internal/ascends/shared/general_ledger/journal_details/biaya-produksi-tidak-langsung/pdf`
- Laporan Biaya Produksi: `POST http://192.168.10.100:5006/api/internal/ascends/shared/general_ledger/journal_details/biaya-produksi/pdf`
- Laporan Ringkasan Aktiva Dalam Proses: `POST http://192.168.10.100:5006/api/internal/ascends/shared/general_ledger/journal_details/ringkasan-aktiva/pdf`
- Laporan Piutang & Perhitungan Bunga (RU): `POST http://192.168.10.100:5006/api/internal/ascends/shared/general_ledger/journal_details/perhitungan-bunga-ru/pdf`
- Laporan Piutang & Perhitungan Bunga (GSU): `POST http://192.168.10.100:5006/api/internal/ascends/shared/general_ledger/journal_details/perhitungan-bunga-gsu/pdf`
- Laporan Laba Kotor (RU): `POST http://192.168.10.100:5006/api/internal/ascends/shared/general_ledger/journal_details/laba-kotor-ru/pdf`
- Laporan Laba Kotor Per Kategori: `POST http://192.168.10.100:5006/api/internal/ascends/shared/general_ledger/journal_details/laba-kotor-per-kategori/pdf`
- Laporan Laba Kotor Tahunan (GSU): `POST http://192.168.10.100:5006/api/internal/ascends/shared/general_ledger/journal_details/laba-kotor-gsu/pdf`
- Laporan Laba Kotor (Periode 12 Bulan/Tahunan) (RU): `POST http://192.168.10.100:5006/api/internal/ascends/shared/general_ledger/journal_details/laba-kotor-ru-12-bulan/pdf`

## Endpoint Shared Trial Balance Monthly

- Laporan Laba Rugi Multi Periode: `POST http://192.168.10.100:5006/api/internal/ascends/shared/general_ledger/trial_balance_monthly/laba-rugi-multi-periode/pdf`
- Laporan Laba Rugi Multi Periode (Tahunan): `POST http://192.168.10.100:5006/api/internal/ascends/shared/general_ledger/trial_balance_monthly/laba-rugi-multi-periode-tahunan/pdf`
- Laporan Pendukung Arus Kas: `POST http://192.168.10.100:5006/api/internal/ascends/shared/general_ledger/trial_balance_monthly/pendukung-arus-kas/pdf`
- Laporan Arus Kas: `POST http://192.168.10.100:5006/api/internal/ascends/shared/general_ledger/trial_balance_monthly/arus-kas-uc/pdf`

## Endpoint Shared Trial Balance

- Laporan Saldo Bank: `POST http://192.168.10.100:5006/api/internal/ascends/shared/general_ledger/trial_balance/saldo-bank/pdf`
- Neraca Per Bulan: `POST http://192.168.10.100:5006/api/internal/ascends/shared/general_ledger/trial_balance/neraca-per-bulan/pdf`
- Laporan Hutang UC: `POST http://192.168.10.100:5006/api/internal/ascends/shared/general_ledger/trial_balance/hutang-uc/pdf`
- Laporan Hutang Lain-Lain: `POST http://192.168.10.100:5006/api/internal/ascends/shared/general_ledger/trial_balance/hutang-lain-lain/pdf`
- Laporan Biaya Dibayar Dimuka: `POST http://192.168.10.100:5006/api/internal/ascends/shared/general_ledger/trial_balance/biaya-bayar-dimuka/pdf`
- Laporan Ringkasan Hutang Bank: `POST http://192.168.10.100:5006/api/internal/ascends/shared/general_ledger/trial_balance/ringkasan-hutang-bank/pdf`

## Input

Parameter field utama untuk semua endpoint Ascends Shared General Ledger:

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

### Filter Periode (Semua Endpoint)

Semua endpoint General Ledger menerima parameter periode dengan format yang sama:

- `Date.StartDate` + `Date.EndDate`: periode laporan, contoh `2026-01-01` sampai `2026-01-31`.
- Alias yang diterima: `Date_StartDate` + `Date_EndDate`, `StartDate` + `EndDate`, `start_date` + `end_date`.
- Jika tidak dikirim, sistem memakai tanggal paling awal sampai paling akhir yang tersedia di data XML.

### Filter Khusus Perhitungan Bunga (RU dan GSU)

Input tambahan khusus `perhitungan-bunga-ru` dan `perhitungan-bunga-gsu`:

- `SaldoAwal` / `saldo_awal`: saldo awal piutang untuk perhitungan bunga, dengan suku bunga tetap 1% per bulan.
- Jika tidak dikirim, nilai default `0`.

### Filter Khusus Laba Rugi RU dan UC

Input tambahan khusus `laporan-laba-rugi-ru` dan `laporan-laba-rugi-uc`:

- Periode `EndDate` menentukan bulan laporan (Bulan B). Bulan A dihitung otomatis sebagai Bulan B - 1 bulan.
- Data difilter menurut mapping kode akun (prefix `411.000`/`412.000` untuk Penjualan, `516.000` untuk HPP, `621.000` untuk Pembelian, dll).

### Filter Khusus Laba Rugi Multi Periode dan Multi Periode Tahunan

- Tidak ada parameter filter tambahan.
- Periode diambil otomatis dari rentang data XML yang tersedia.

### Filter Khusus Pendukung Arus Kas

- `PeriodStart` + `PeriodEnd`: parameter untuk menentukan dua periode yang dibandingkan, format `YYYY-MM`.
- Alias yang diterima: `period_start` + `period_end`, `start_date` + `end_date`, `StartDate` + `EndDate`.
- **Jika tidak dikirim**, sistem otomatis mengambil bulan paling awal dan paling akhir yang tersedia di data XML.
- Catatan: parameter `System_Username` / `Sys_Username` dibaca dari data XML (field `Last_x0020_Modified_x0020_By` record pertama).

Contoh `multipart/form-data` (Laporan Laba Rugi RU):

```text
DB_CompanyName=RU
Sys_Username=Ridho
Date.StartDate=2026-01-01
Date.EndDate=2026-01-31
xml_file=AnlReports.GeneralLedger.JournalDetails.xml
```

Contoh `multipart/form-data` (Laporan Beban Umum):

```text
DB_CompanyName=RU
Date.StartDate=2026-01-01
Date.EndDate=2026-01-31
xml_file=AnlReports.GeneralLedger.JournalDetails.xml
```

Contoh `multipart/form-data` (Perhitungan Bunga RU):

```text
DB_CompanyName=RU
SaldoAwal=100000000
Date.StartDate=2026-01-01
Date.EndDate=2026-01-31
xml_file=AnlReports.GeneralLedger.JournalDetails.xml
```

Contoh `multipart/form-data` (Pendukung Arus Kas):

```text
DB_CompanyName=UC
PeriodStart=2026-01
PeriodEnd=2026-02
Sys_Username=Ridho
xml_file=AnlReports.GeneralLedger.TrialBalanceMonthly.xml
```

Contoh `multipart/form-data` (Pendukung Arus Kas — auto-detect periode dari XML):

```text
DB_CompanyName=UC
Sys_Username=Ridho
xml_file=AnlReports.GeneralLedger.TrialBalanceMonthly.xml
```

Contoh `multipart/form-data` (Neraca Per Bulan):

```text
DB_CompanyName=RU
Date.StartDate=2026-01-01
Date.EndDate=2026-01-31
xml_file=AnlReports.GeneralLedger.TrialBalance.xml
```

Contoh tanpa parameter tanggal (periode otomatis dari data):

```text
DB_CompanyName=RU
Sys_Username=Ridho
xml_file=AnlReports.GeneralLedger.JournalDetails.xml
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

Contoh filename:

- `Journal Details - Laporan Laba Rugi (RU).pdf`
- `Journal Details - Laporan Pendapatan Dan Biaya Lain-Lain (RU).pdf`
- `Journal Details - Laporan Beban (RU).pdf`
- `Journal Details - Laporan Beban Penjualan (RU).pdf`
- `Journal Details - Laporan Beban Penjualan Summary (RU).pdf`
- `Journal Details - Laporan Biaya Upah Langsung (RU).pdf`
- `Journal Details - Laporan Biaya Beban Umum (RU).pdf`
- `Journal Details - Laporan Biaya Produksi Tidak Langsung (RU).pdf`
- `Journal Details - Laporan Biaya Produksi (RU).pdf`
- `Journal Details - Laporan Ringkasan Aktiva Dalam Proses (RU).pdf`
- `Journal Details - Laporan Piutang & Perhitungan Bunga RU (RU).pdf`
- `Journal Details - Laporan Piutang & Perhitungan Bunga GSU (GSU).pdf`
- `Journal Details - Laporan Laba Kotor (RU).pdf`
- `Journal Details - Laporan Laba Kotor Per Kategori (RU).pdf`
- `Journal Details - Laporan Laba Kotor Tahunan (GSU).pdf`
- `Laporan Laba Rugi Multi Periode (RU).pdf`
- `Laporan Laba Rugi Multi Periode Tahunan (RU).pdf`
- `Laporan Pendukung Arus Kas (RU).pdf`
- `Laporan Saldo Bank (RU).pdf`
- `Neraca Per Bulan (RU).pdf`
- `Laporan Hutang UC (UC).pdf`
- `Laporan Hutang Lain-Lain (RU).pdf`
- `Laporan Biaya Bayar Dimuka (RU).pdf`
- `Laporan Ringkasan Hutang Bank (RU).pdf`

## Response Gagal

- `422 application/json` jika XML kosong, tidak valid, atau tidak bisa diproses.
- `422 application/json` jika data tidak ditemukan untuk periode yang diminta.

## Template Shared General Ledger Tersedia

Template Blade shared Journal Details berada di `resources/views/ascends/shared/general_ledger/journal_details`.

- `journal_details/laporan_laba_rugi_ru`
- `journal_details/laporan_laba_rugi_uc`
- `journal_details/pendapatan_dan_biaya_lain`
- `journal_details/pendapatan_dan_biaya_lain_baru`
- `journal_details/beban_umum_ru`
- `journal_details/beban_umum_uc`
- `journal_details/beban_umum_gsu`
- `journal_details/beban_umum_baru`
- `journal_details/beban`
- `journal_details/beban_penjualan`
- `journal_details/beban_penjualan_summary`
- `journal_details/biaya_upah_langsung_detail`
- `journal_details/biaya_produksi_tidak_langsung`
- `journal_details/biaya_produksi`
- `journal_details/ringkasan_aktiva`
- `journal_details/perhitungan_bunga_ru`
- `journal_details/perhitungan_bunga_gsu`
- `journal_details/laba_kotor_ru`
- `journal_details/laba_kotor_per_kategori`
- `journal_details/laba_kotor_gsu`

Template Blade shared Trial Balance Monthly berada di `resources/views/ascends/shared/general_ledger/trial_balance_monthly`.

- `trial_balance_monthly/laba_rugi_multi_periode`
- `trial_balance_monthly/laba_rugi_multi_periode_tahunan`
- `trial_balance_monthly/pendukung_arus_kas`

Template Blade shared Trial Balance berada di `resources/views/ascends/shared/general_ledger/trial_balance`.

- `trial_balance/saldo_bank`
- `trial_balance/neraca_per_bulan`
- `trial_balance/hutang_uc`
- `trial_balance/hutang_lainnya`
- `trial_balance/biaya_bayar_dimuka`
- `trial_balance/ringkasan_hutang_bank`

Catatan: semua endpoint di atas memakai pola shared yang sama. XML menjadi sumber data laporan, sedangkan parameter `DB_CompanyName` menjadi sumber label perusahaan pada title dan filename. Field form `company` hanya fallback jika `DB_CompanyName` belum dikirim.
