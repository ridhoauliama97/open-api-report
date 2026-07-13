# Audit Laporan — Shared (Multi-Company: RU / GSU / UC)

Dokumen ini mengaudit seluruh laporan yang menggunakan Blade `shared` di `resources/views/ascends/shared/`.

Template shared dipakai agar struktur Blade laporan bisa digunakan lintas perusahaan (RU, GSU, UC).
Nama perusahaan pada title dan filename dibaca dari parameter field `DB_CompanyName`.

---

## Ringkasan

| Metric | Count |
|---|---|
| Total file Blade di `ascends/shared/` | 75 |
| Partial (non-report) | 1 (`report-header.blade.php`) |
| **Total report views** | **74** |
| Orphaned views (tidak tereferensi route aktif) | 2 |
| Service di `App\Services\Ascends\Shared\` | 34+ |
| Service di `App\Services\Ascends\Ru\` | 40+ |

---

## Company: SHARED (Multi-Company: RU / GSU / UC)

### Modul: HRM — Employee List

Route URI: `POST /api/internal/ascends/shared/hrm/{report}/pdf` (parameterized, 1 controller method untuk 16 report)
Controller: `AscendXmlTestController::apiSharedHrmReportPdf`

| # | Report Name | Slug | Blade View | Service Class |
|---|---|---|---|---|
| 1 | List Karyawan | `list-karyawan` | `shared/hrm/employee_list/list_karyawan/pdf` | `App\Services\Ascends\Ru\Hrm\EmployeeListReportService` |
| 2 | Daftar Karyawan | `daftar-karyawan` | `shared/hrm/employee_list/daftar_karyawan/pdf` | `App\Services\Ascends\Ru\Hrm\DaftarKaryawanReportService` |
| 3 | Daftar Karyawan Berdasarkan Abjad | `daftar-karyawan-berdasarkan-abjad` | `shared/hrm/employee_list/daftar_karyawan_berdasarkan_abjad/pdf` | `App\Services\Ascends\Ru\Hrm\DaftarKaryawanBerdasarkanAbjadReportService` |
| 4 | Data Karyawan Berdasarkan Status Kerja | `data-karyawan-status-kerja` | `shared/hrm/employee_list/data_karyawan_status_kerja/pdf` | `App\Services\Ascends\Ru\Hrm\DataKaryawanStatusKerjaReportService` |
| 5 | Karyawan Aktif Per Departemen | `karyawan-aktif-per-departemen` | `shared/hrm/employee_list/karyawan_aktif_per_departemen/pdf` | `App\Services\Ascends\Ru\Hrm\KaryawanAktifPerDepartemenReportService` |
| 6 | Karyawan Masuk Per Departemen Per Tanggal Masuk | `karyawan-masuk-per-departemen-per-tanggal-masuk` | `shared/hrm/employee_list/karyawan_masuk_per_departemen_per_tanggal_masuk/pdf` | `App\Services\Ascends\Ru\Hrm\KaryawanMasukPerDepartemenPerTanggalMasukReportService` |
| 7 | Karyawan Per Agama | `karyawan-per-agama` | `shared/hrm/employee_list/karyawan_per_agama/pdf` | `App\Services\Ascends\Ru\Hrm\KaryawanPerAgamaReportService` |
| 8 | Karyawan Per Departemen Per Jabatan | `karyawan-per-departemen-per-jabatan` | `shared/hrm/employee_list/karyawan_per_departemen_per_jabatan/pdf` | `App\Services\Ascends\Ru\Hrm\KaryawanPerDepartemenPerJabatanReportService` |
| 9 | Karyawan Per Etnis | `karyawan-per-etnis` | `shared/hrm/employee_list/karyawan_per_etnis/pdf` | `App\Services\Ascends\Ru\Hrm\KaryawanPerEtnisReportService` |
| 10 | Karyawan Per Level | `karyawan-per-level` | `shared/hrm/employee_list/karyawan_per_level/pdf` | `App\Services\Ascends\Ru\Hrm\KaryawanPerLevelReportService` |
| 11 | Karyawan Per Masa Kerja | `karyawan-per-masa-kerja` | `shared/hrm/employee_list/karyawan_per_masa_kerja/pdf` | `App\Services\Ascends\Ru\Hrm\KaryawanPerMasaKerjaReportService` |
| 12 | Karyawan Per Umur | `karyawan-per-umur` | `shared/hrm/employee_list/karyawan_per_umur/pdf` | `App\Services\Ascends\Ru\Hrm\KaryawanPerUmurReportService` |
| 13 | Kehadiran KK/KT/ST | `kehadiran-kk-kt-st` | `shared/hrm/employee_list/kehadiran_kk_kt_st/pdf` | `App\Services\Ascends\Ru\Hrm\KehadiranKkKtStReportService` |
| 14 | List Karyawan Habis Kontrak | `list-karyawan-habis-kontrak` | `shared/hrm/employee_list/list_karyawan_habis_kontrak/pdf` | `App\Services\Ascends\Ru\Hrm\ListKaryawanHabisKontrakReportService` |
| 15 | Perbandingan Jumlah Karyawan Tahunan Per Bulan | `perbandingan-jumlah-karyawan-tahunan-per-bulan` | `shared/hrm/employee_list/perbandingan_jumlah_karyawan_tahunan_per_bulan/pdf` | `App\Services\Ascends\Ru\Hrm\PerbandinganJumlahKaryawanTahunanPerBulanReportService` |
| 16 | Usia Generasi Tahun Kelahiran & Masa Kerja | `usia-generasi-tahun-kelahiran-masa-kerja` | `shared/hrm/employee_list/usia_generasi_tahun_kelahiran_masa_kerja/pdf` | `App\Services\Ascends\Ru\Hrm\UsiaGenerasiTahunKelahiranMasaKerjaReportService` |

### Modul: HRM — Employee Termination

Route URI: `POST /api/internal/ascends/shared/hrm/employee-termination/pdf`
Controller: `AscendXmlTestController::apiSharedHrmEmployeeTerminationPdf`

| # | Report Name | Blade View | Service Class |
|---|---|---|---|
| 17 | Employee Termination | `shared/hrm/employee_termination/pdf` | `App\Services\Ascends\Shared\Hrm\EmployeeTerminationReportService` |

### Modul: HRM — Attendance Full

| # | Report Name | Route URI | Controller | Service Class |
|---|---|---|---|---|
| 18 | Absensi Briefing Harian (RU) | `POST .../attendance-full/absensi-briefing-harian-ru/pdf` | `apiSharedHrmAbsensiBriefingHarianPdf` | `AbsensiBriefingHarianReportService` |
| 19 | Absensi Briefing Harian (GSU) | `POST .../attendance-full/absensi-briefing-harian-gsu/pdf` | `apiSharedHrmAbsensiBriefingHarianGsuPdf` | `AbsensiBriefingHarianGsuReportService` |
| 20 | Rekapitulasi Absensi Briefing Harian (RU) | `POST .../attendance-full/rekapitulasi-absensi-briefing-harian-ru/pdf` | `apiSharedHrmRekapitulasiAbsensiBriefingHarianRuPdf` | `RekapitulasiAbsensiBriefingHarianReportService` |
| 21 | Rekapitulasi Absensi Briefing Harian (GSU) | `POST .../attendance-full/rekapitulasi-absensi-briefing-harian-gsu/pdf` | `apiSharedHrmRekapitulasiAbsensiBriefingHarianGsuPdf` | `RekapitulasiAbsensiBriefingHarianGsuReportService` |
| 22 | Absensi Individu | `POST .../attendance-full/absensi-individu/pdf` | `apiSharedHrmAbsensiIndividuPdf` | `AbsensiIndividuReportService` |
| 23 | Kehadiran Kru Stick | `POST .../attendance-full/kehadiran-kru-stick/pdf` | `apiSharedHrmKehadiranKruStickPdf` | `KehadiranKruStickReportService` |
| 24 | Kehadiran Kru Racip | `POST .../attendance-full/kehadiran-kru-racip/pdf` | `apiSharedHrmKehadiranKruRacipPdf` | `KehadiranKruRacipReportService` |
| 25 | Kehadiran Kru Bahan Baku | `POST .../attendance-full/kehadiran-kru-bahan-baku/pdf` | `apiSharedHrmKehadiranKruBahanBakuPdf` | `KehadiranKruBahanBakuReportService` |
| 26 | Data Peserta Makan Siang Ibadah Aula Per Departemen | `POST .../attendance-full/data-peserta-makan-siang-ibadah-aula-per-departemen/pdf` | `apiSharedHrmDataPesertaMakanSiangIbadahAulaPerDepartemenPdf` | `DataPesertaMakanSiangIbadahAulaPerDepartemenReportService` |
| 27 | Data Peserta Makan Siang Shalat Jumat Per Departemen | `POST .../attendance-full/data-peserta-makan-siang-shalat-jumat-per-departemen/pdf` | `apiSharedHrmDataPesertaMakanSiangShalatJumatPerDepartemenPdf` | `DataPesertaMakanSiangShalatJumatPerDepartemenReportService` |
| 28 | Persentase Kehadiran Mingguan Per Departemen | `POST .../attendance-full/persentase-kehadiran-mingguan-per-departemen/pdf` | `apiSharedHrmPersentaseKehadiranMingguanPerDepartemenPdf` | `PersentaseKehadiranMingguanPerDepartemenReportService` |
| 29 | Persentase Kehadiran Bulanan | `POST .../attendance-full/persentase-kehadiran-bulanan/pdf` | `apiSharedHrmPersentaseKehadiranBulananPdf` | `PersentaseKehadiranBulananReportService` |
| 30 | Rekapitulasi Kehadiran < 93% Tahunan | `POST .../attendance-full/rekapitulasi-kehadiran-kurang-93-tahunan/pdf` | `apiSharedHrmRekapitulasiKehadiranKurang93TahunanPdf` | `RekapitulasiKehadiranKurang93TahunanReportService` |
| 31 | Rekapitulasi Pengabaian Keterlambatan Tahunan | `POST .../attendance-full/rekapitulasi-pengabaian-keterlambatan-tahunan/pdf` | `apiSharedHrmRekapitulasiPengabaianKeterlambatanTahunanPdf` | `RekapitulasiPengabaianKeterlambatanTahunanReportService` |
| 32 | Pengabaian Keterlambatan & Kehadiran Manual | `POST .../attendance-full/pengabaian-keterlambatan-kehadiran-manual/pdf` | `apiSharedHrmPengabaianKeterlambatanKehadiranManualPdf` | `PengabaianKeterlambatanKehadiranManualReportService` |

### Modul: HRM — Late Sign In

| # | Report Name | Route URI | Controller | Service Class |
|---|---|---|---|---|
| 33 | Durasi & Denda Keterlambatan Per Departemen | `POST .../late-sign-in/durasi-denda-keterlambatan/pdf` | `apiSharedHrmDurasiDendaKeterlambatanPdf` | `DurasiDendaKeterlambatanReportService` |

### Modul: HRM — Overtime

| # | Report Name | Route URI | Controller | Service Class |
|---|---|---|---|---|
| 34 | Lembur Bulanan Per Departemen | `POST .../overtime/lembur-bulanan/pdf` | `apiSharedHrmLemburBulananPdf` | `LemburBulananReportService` |

### Modul: HRM — Attendance

| # | Report Name | Route URI | Controller | Service Class |
|---|---|---|---|---|
| 35 | Perbandingan Kehadiran Per Bulan | `POST .../attendance/perbandingan-kehadiran-per-bulan/pdf` | `apiSharedHrmPerbandinganKehadiranPerBulanPdf` | `PerbandinganKehadiranPerBulanReportService` |
| 36 | Keterlambatan Kehadiran Briefing Harian | `POST .../attendance/keterlambatan-kehadiran-briefing-harian/pdf` | `apiSharedHrmKeterlambatanKehadiranBriefingHarianPdf` | `KeterlambatanKehadiranBriefingHarianReportService` |

### Modul: HRM — Holiday

| # | Report Name | Route URI | Controller | Service Class |
|---|---|---|---|---|
| 37 | Daftar Libur dan Cuti Bersama | `POST .../holiday/daftar-libur-cuti-bersama/pdf` | `apiSharedHrmDaftarLiburCutiBersamaPdf` | `DaftarLiburCutiBersamaReportService` |

### Modul: HRM — Other Income Deduction

| # | Report Name | Route URI | Controller | Service Class |
|---|---|---|---|---|
| 38 | Pendapatan Lain-Lain | `POST .../other-income-deduction/pendapatan-lain-lain/pdf` | `apiSharedHrmPendapatanLainLainPdf` | `PendapatanLainLainReportService` |

### Modul: HRM — Warning Notice

| # | Report Name | Route URI | Controller | Service Class |
|---|---|---|---|---|
| 39 | Surat Peringatan | `POST .../warning-notice/surat-peringatan/pdf` | `apiSharedHrmSuratPeringatanPdf` | `SuratPeringatanReportService` |

### Modul: HRM — Loss Time

| # | Report Name | Route URI | Controller | Service Class |
|---|---|---|---|---|
| 40 | Loss Time | `POST .../loss-time/pdf` | `apiSharedHrmLossTimePdf` | `LossTimeReportService` |

### Modul: HRM — Absence

| # | Report Name | Route URI | Controller | Service Class |
|---|---|---|---|---|
| 41 | Ketidakhadiran Bulanan | `POST .../absence/ketidakhadiran-bulanan/pdf` | `apiSharedHrmKetidakhadiranBulananPdf` | `KetidakhadiranBulananReportService` |

### Modul: Associate

Route: `POST /api/internal/ascends/shared/associate/{report}/pdf` (4 routes, dedicated controller methods)
Controller: `AscendXmlTestController::apiSharedAssociate*Pdf`

| # | Report Name | Slug | Blade View | Service Class |
|---|---|---|---|---|
| 42 | Customer Modifikasi 6 Bulan | `customer-modifikasi-6-bulan` | `shared/associate/customer_modifikasi_6_bulan_terakhir/pdf` | `App\Services\Ascends\Shared\Associate\CustomerModifikasiReportService` |
| 43 | Customer Baru Per Tahun | `customer-baru-per-tahun` | `shared/associate/customer_baru_per_tahun/pdf` | `App\Services\Ascends\Shared\Associate\CustomerBaruPerTahunReportService` |
| 44 | Customer Baru | `customer-baru` | `shared/associate/customer_baru/pdf` | `App\Services\Ascends\Shared\Associate\CustomerBaruReportService` |
| 45 | List Customer Per Kota | `list-customer-per-kota` | `shared/associate/list_customer/pdf` | `App\Services\Ascends\Shared\Associate\ListCustomerPerKotaReportService` |

### Modul: General Ledger — Journal Details

Route: `POST /api/internal/ascends/shared/general_ledger/journal_details/{report}/pdf` (15 routes)
Controller: `AscendXmlTestController::apiSharedGeneralLedger*Pdf`

| # | Report Name | Route URI | Blade View |
|---|---|---|---|
| 46 | Laporan Laba Rugi (RU) | `journal_details/laporan-laba-rugi-ru/pdf` | `shared/general_ledger/journal_details/laporan_laba_rugi_ru/pdf` |
| 47 | Laporan Laba Rugi (UC) | `journal_details/laporan-laba-rugi-uc/pdf` | `shared/general_ledger/journal_details/laporan_laba_rugi_uc/pdf` |
| 48 | Pendapatan Dan Biaya Lain-Lain | `journal_details/pendapatan-dan-biaya-lain/pdf` | `shared/general_ledger/journal_details/pendapatan_dan_biaya_lain/pdf` |
| 49 | Pendapatan Dan Biaya Lain-Lain Baru | `journal_details/pendapatan-dan-biaya-lain-baru/pdf` | `shared/general_ledger/journal_details/pendapatan_dan_biaya_lain_baru/pdf` |
| 50 | Beban Umum (UC) | `journal_details/beban-umum-uc/pdf` | `shared/general_ledger/journal_details/beban_umum_uc/pdf` |
| 51 | Beban Umum (RU) | `journal_details/beban-umum-ru/pdf` | `shared/general_ledger/journal_details/beban_umum_ru/pdf` |
| 52 | Beban Umum (GSU) | `journal_details/beban-umum-gsu/pdf` | `shared/general_ledger/journal_details/beban_umum_gsu/pdf` |
| 53 | Beban | `journal_details/beban/pdf` | `shared/general_ledger/journal_details/beban/pdf` |
| 54 | Beban Penjualan | `journal_details/beban-penjualan/pdf` | `shared/general_ledger/journal_details/beban_penjualan/pdf` |
| 55 | Beban Penjualan Summary | `journal_details/beban-penjualan-summary/pdf` | `shared/general_ledger/journal_details/beban_penjualan_summary/pdf` |
| 56 | Biaya Upah Langsung | `journal_details/biaya-upah-langsung-detail/pdf` | `shared/general_ledger/journal_details/biaya_upah_langsung_detail/pdf` |
| 57 | Biaya Beban Umum | `journal_details/beban-umum-baru/pdf` | `shared/general_ledger/journal_details/beban_umum_baru/pdf` |
| 58 | Biaya Produksi Tidak Langsung | `journal_details/biaya-produksi-tidak-langsung/pdf` | `shared/general_ledger/journal_details/biaya_produksi_tidak_langsung/pdf` |
| 59 | Biaya Produksi | `journal_details/biaya-produksi/pdf` | `shared/general_ledger/journal_details/biaya_produksi/pdf` |
| 60 | Ringkasan Aktiva Dalam Proses | `journal_details/ringkasan-aktiva/pdf` | `shared/general_ledger/journal_details/ringkasan_aktiva/pdf` |
| 61 | Perhitungan Bunga (RU) | `journal_details/perhitungan-bunga-ru/pdf` | `shared/general_ledger/journal_details/perhitungan_bunga_ru/pdf` |
| 62 | Perhitungan Bunga (GSU) | `journal_details/perhitungan-bunga-gsu/pdf` | `shared/general_ledger/journal_details/perhitungan_bunga_gsu/pdf` |
| 63 | Laba Kotor (RU) | `journal_details/laba-kotor-ru/pdf` | `shared/general_ledger/journal_details/laba_kotor_ru/pdf` |
| 64 | Laba Kotor Per Kategori | `journal_details/laba-kotor-per-kategori/pdf` | `shared/general_ledger/journal_details/laba_kotor_per_kategori/pdf` |
| 65 | Laba Kotor Tahunan (GSU) | `journal_details/laba-kotor-gsu/pdf` | `shared/general_ledger/journal_details/laba_kotor_gsu/pdf` |

### Modul: General Ledger — Trial Balance Monthly

| # | Report Name | Route URI | Blade View |
|---|---|---|---|
| 66 | Laba Rugi Multi Periode | `trial_balance_monthly/laba-rugi-multi-periode/pdf` | `shared/general_ledger/trial_balance_monthly/laba_rugi_multi_periode/pdf` |
| 67 | Laba Rugi Multi Periode (Tahunan) | `trial_balance_monthly/laba-rugi-multi-periode-tahunan/pdf` | `shared/general_ledger/trial_balance_monthly/laba_rugi_multi_periode_tahunan/pdf` |
| 68 | Pendukung Arus Kas | `trial_balance_monthly/pendukung-arus-kas/pdf` | `shared/general_ledger/trial_balance_monthly/pendukung_arus_kas/pdf` |
| 69 | Arus Kas | `trial_balance_monthly/arus-kas-uc/pdf` | `shared/general_ledger/trial_balance_monthly/arus_kas_uc/pdf` |

### Modul: General Ledger — Trial Balance

| # | Report Name | Route URI | Blade View |
|---|---|---|---|
| 69 | Saldo Bank | `trial_balance/saldo-bank/pdf` | `shared/general_ledger/trial_balance/saldo_bank/pdf` |
| 70 | Neraca Per Bulan | `trial_balance/neraca-per-bulan/pdf` | `shared/general_ledger/trial_balance/neraca_per_bulan/pdf` |
| 71 | Hutang UC | `trial_balance/hutang-uc/pdf` | `shared/general_ledger/trial_balance/hutang_uc/pdf` |
| 72 | Hutang Lain-Lain | `trial_balance/hutang-lain-lain/pdf` | `shared/general_ledger/trial_balance/hutang_lainnya/pdf` |
| 73 | Biaya Dibayar Dimuka | `trial_balance/biaya-bayar-dimuka/pdf` | `shared/general_ledger/trial_balance/biaya_bayar_dimuka/pdf` |
| 74 | Ringkasan Hutang Bank | `trial_balance/ringkasan-hutang-bank/pdf` | `shared/general_ledger/trial_balance/ringkasan_hutang_bank/pdf` |

### Modul: Fixed Asset — Asset Summary

| # | Report Name | Route URI | Blade View |
|---|---|---|---|
| 75 | Penyusutan Aktiva Tetap | `fixed_asset/asset_summary/penyusutan_aktiva/pdf` | `shared/fixed_asset/asset_summary/penyusutan_aktiva/pdf` |

### Modul: SALES — Sales Invoice (Orphaned)

Tidak ada route aktif yang mereferensi view ini. Status: **stale / orphaned**.

| # | Report Name | Blade View | Status |
|---|---|---|---|
| — | Sales Invoice (normal) | `shared/sales/sales_invoice/normal-pdf` | Orphaned |
| — | Sales Invoice (panjang) | `shared/sales/sales_invoice/panjang-pdf` | Orphaned |

---

## Anomalies

1. **Duplicate route** — Report `karyawan-masuk-per-departemen-per-tanggal-masuk` punya 2 route: satu parameterized (`{report}/pdf`) dan satu dedicated. Route dedicated lebih spesifik, jadi dipilih lebih dulu.

2. **Orphaned sales views** — `shared/sales/sales_invoice/normal-pdf.blade.php` dan `panjang-pdf.blade.php` tidak tereferensi route mana pun. View aktif ada di `ascends/ru/sales/sales_invoice/` dan `ascends/gsu/sales/sales_invoice/`.

3. **Namespace service tidak konsisten** — Hanya `EmployeeTerminationReportService` yang berada di `App\Services\Ascends\Shared\Hrm\`. Semua service shared lainnya untuk HRM berada di `App\Services\Ascends\Ru\Hrm\` meskipun Blade-nya di folder `shared/`. Sebaliknya, service untuk Associate, GeneralLedger, dan FixedAsset sudah konsisten di `App\Services\Ascends\Shared\`.

4. **Laba Rugi RU/UC filename tanpa prefix** — Berbeda dengan Journal Details lainnya yang pakai prefix `Journal Details - `, Laporan Laba Rugi (RU dan UC) juga pakai prefix yang sama. Sementara report Trial Balance Monthly dan Trial Balance tidak memakai prefix section di filename-nya.
