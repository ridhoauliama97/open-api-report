# Catatan Laporan Financial Rasio / Ratio

## Laporan yang dibuat
- **UC**: `financial_rasio_uc` — Laporan Financial Rasio (7 rasio)
- **RU**: `financial_rasio_ru` — Laporan Financial Ratio (8 rasio, +Inventory Turnover)
- **GSU**: `financial_rasio_gsu` — Laporan Financial Ratio (6 rasio, +DER, Receivable Turnover, Inventory Turnover, tanpa ROA & DAR)

## Formula perhitungan

### AktivaLancar
- **UC**: `111.101, 111.102, 111.103, 111.105, 111.200, 111.300, 111.400` + `121.300.101`
- **RU**: `111.101, 111.102, 111.103, 111.105, 111.200, 111.400` + `121.300.101` (tanpa `111.300`)
- **GSU**: `111.101, 111.102, 111.105, 111.200, 111.300, 111.400` (tanpa `111.103`, tanpa `121.300.101`)

### NilaiHutang (sama untuk UC, RU, GSU)
`211.100, 211.200, 211.300, 211.400, 211.500, 212.100, 222.000`

### Pendapatan
- **UC**: prefix3 `421`
- **RU**: prefix3 `411` + `412`
- **GSU**: prefix3 `411` (saja, tanpa potongan/retur)

### Laba Bersih
- **UC**: `Pendapatan(421) + OtherIncome(800) - BebanUsaha(721) - BebanLain(900)`
- **RU**: `Pendapatan(411+412) + OtherIncome(800) + Potongan(621) - HPP(516) - BebanPenjualan(711) - BebanAdm(721) - BebanLain(900)` (tanpa 500.x)
- **GSU**: `Pendapatan(411) + OtherIncome(800) + Potongan(621) - PotPenjualan(431) - ReturPenjualan(451) - HPP(516) - BebanProduksi(501+512+514+642) - BebanPenjualan(711+712) - BebanAdm(721+722) - BebanLain(900)`

### Operating Expense
- **UC**: `721.x` dikurangi akun non-operasional (`201A-D, 251, 259, 260, 268-270`)
- **RU**: `711.x + 721.x + selected 500.x` (positive 500.x kecuali `500.001, 500.003, 500.018`)
- **GSU**: `711 + 721 + 514` (Beban Penjualan + Beban Adm + Beban Pabrik)

### DER
- **UC**: Total Liabilitas / Equitas
- **RU**: Hutang Lancar / Equitas
- **GSU**: Hutang Lancar / Equitas (tapi kolom label di PDF terbalik: "Nilai Kewajiban Lancar" = Equitas, "Nilai Equitas" = HL)

### Struktur prefix khusus
| Prefix | UC | RU | GSU |
|---|---|---|---|
| Akum. Penyusutan | di `121.x` (Credit) | di `121.x` (Credit) | **`131.x`** (terpisah) |
| Revenue | `421` | `411+412` | `411` |
| Beban Penjualan | — | `711` | `711+712` |
| Beban Adm | `721` | `721` | `721+722` |
| HPP | — | `516` | `516` |
| Biaya Produksi | — | `500` | `501+512+514+642` |

## Catatan anomaly
- **Operating Expense RU April**: selisih ~36jt (1.8%) dari PDF referensi.
- **Laba & OE GSU**: Formula hanya terverifikasi cocok untuk Jan-Feb. Maret-Juli ada perbedaan karena data inventory adjustment (111.400) yang sangat besar mengganggu perhitungan. Kemungkinan ada record selection formula Crystal Reports yang tidak dapat direverse-engineer dari XML uji.
- **Nilai angka**: Format angka tanpa desimal (integer) sesuai format PDF referensi.
- **Subtitle UC/RU**: Menggunakan bulan saat ini (`now()`). **GSU**: Menggunakan max periode dari data XML.

## File terkait
| File | Path |
|---|---|
| Service UC | `app/Services/.../TrialBalanceMonthly/FinancialRasioUcReportService.php` |
| Service RU | `app/Services/.../TrialBalanceMonthly/FinancialRasioRuReportService.php` |
| Service GSU | `app/Services/.../TrialBalanceMonthly/FinancialRasioGsuReportService.php` |
| View UC | `resources/views/.../financial_rasio_uc/pdf.blade.php` |
| View RU | `resources/views/.../financial_rasio_ru/pdf.blade.php` |
| View GSU | `resources/views/.../financial_rasio_gsu/pdf.blade.php` |
| Controller | `app/Http/Controllers/AscendXmlTestController.php` |
| Routes | `routes/api.php` |
