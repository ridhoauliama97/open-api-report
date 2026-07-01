# Dokumentasi Hit Endpoint API Ascend Shared HRM

Dokumen ini berisi endpoint internal untuk test/render laporan HRM Ascend yang memakai Blade shared.

- Base host: `http://192.168.10.100:5006`
- Prefix API: `/api`

## Konsep Shared HRM

Template shared HRM dipakai supaya struktur Blade laporan bisa digunakan lintas perusahaan:

- `RU`
- `GSU`
- `UC`

Nama perusahaan pada title dan filename dibaca dari parameter field `DB_CompanyName`.
Nama user print pada footer dibaca dari parameter field `Sys_Username`.

## Endpoint Shared Employee List

- Employee List - List Karyawan: `POST http://192.168.10.100:5006/api/internal/ascends/shared/hrm/list-karyawan/pdf`
- Employee List - Laporan Daftar Karyawan: `POST http://192.168.10.100:5006/api/internal/ascends/shared/hrm/daftar-karyawan/pdf`
- Employee List - Laporan Daftar Karyawan Berdasarkan Abjad: `POST http://192.168.10.100:5006/api/internal/ascends/shared/hrm/daftar-karyawan-berdasarkan-abjad/pdf`
- Employee List - Laporan Data Karyawan Berdasarkan Status Kerja: `POST http://192.168.10.100:5006/api/internal/ascends/shared/hrm/data-karyawan-status-kerja/pdf`
- Employee List - Laporan Karyawan Aktif Per Departemen: `POST http://192.168.10.100:5006/api/internal/ascends/shared/hrm/karyawan-aktif-per-departemen/pdf`
- Employee List - Laporan Karyawan Masuk Per Departemen Per Tanggal Masuk: `POST http://192.168.10.100:5006/api/internal/ascends/shared/hrm/karyawan-masuk-per-departemen-per-tanggal-masuk/pdf`
- Employee List - Laporan Karyawan Per Agama: `POST http://192.168.10.100:5006/api/internal/ascends/shared/hrm/karyawan-per-agama/pdf`
- Employee List - Laporan Karyawan Per Departemen Per Jabatan: `POST http://192.168.10.100:5006/api/internal/ascends/shared/hrm/karyawan-per-departemen-per-jabatan/pdf`
- Employee List - Laporan Karyawan Per Etnis: `POST http://192.168.10.100:5006/api/internal/ascends/shared/hrm/karyawan-per-etnis/pdf`
- Employee List - Laporan Karyawan Per Level: `POST http://192.168.10.100:5006/api/internal/ascends/shared/hrm/karyawan-per-level/pdf`
- Employee List - Laporan Karyawan Per Masa Kerja: `POST http://192.168.10.100:5006/api/internal/ascends/shared/hrm/karyawan-per-masa-kerja/pdf`
- Employee List - Laporan Karyawan Per Umur: `POST http://192.168.10.100:5006/api/internal/ascends/shared/hrm/karyawan-per-umur/pdf`
- Employee List - Laporan Kehadiran KK/KT/ST: `POST http://192.168.10.100:5006/api/internal/ascends/shared/hrm/kehadiran-kk-kt-st/pdf`
- Employee List - Laporan List Karyawan Habis Kontrak: `POST http://192.168.10.100:5006/api/internal/ascends/shared/hrm/list-karyawan-habis-kontrak/pdf`
- Employee List - Laporan Perbandingan Jumlah Karyawan Tahunan Per Bulan: `POST http://192.168.10.100:5006/api/internal/ascends/shared/hrm/perbandingan-jumlah-karyawan-tahunan-per-bulan/pdf`
- Employee List - Laporan Usia Generasi Berdasakan Tahun Kelahiran dan Masa Kerja: `POST http://192.168.10.100:5006/api/internal/ascends/shared/hrm/usia-generasi-tahun-kelahiran-masa-kerja/pdf`
- Employee List - Laporan THR (Idul Fitri/Natal/Imlek): `POST http://192.168.10.100:5006/api/internal/ascends/shared/hrm/thr/pdf`
- Employee List - Laporan Diagram Karyawan Per Departemen: `POST http://192.168.10.100:5006/api/internal/ascends/shared/hrm/employee-list/diagram-karyawan-per-departemen/pdf`
- Employee List - Laporan Diagram Karyawan Per Divisi: `POST http://192.168.10.100:5006/api/internal/ascends/shared/hrm/employee-list/diagram-karyawan-per-divisi/pdf`
- Employee List - Laporan Diagram Karyawan Per Masa Kerja: `POST http://192.168.10.100:5006/api/internal/ascends/shared/hrm/employee-list/diagram-karyawan-per-masa-kerja/pdf`
- Employee List - Laporan Diagram Karyawan Per Level: `POST http://192.168.10.100:5006/api/internal/ascends/shared/hrm/employee-list/diagram-karyawan-per-level/pdf`
- Employee List - Laporan Diagram Karyawan Per Strata Pendidikan: `POST http://192.168.10.100:5006/api/internal/ascends/shared/hrm/employee-list/diagram-karyawan-per-strata-pendidikan/pdf`
- Employee List - Laporan Diagram Karyawan Per Jenis Kelamin: `POST http://192.168.10.100:5006/api/internal/ascends/shared/hrm/employee-list/diagram-karyawan-per-jenis-kelamin/pdf`
- Employee List - Laporan Diagram Karyawan Per Usia Generasi: `POST http://192.168.10.100:5006/api/internal/ascends/shared/hrm/employee-list/diagram-karyawan-per-usia-generasi/pdf`

## Endpoint Custom Reports

Custom reports mempunyai endpoint khusus dengan parameter filter sendiri:

- Karyawan Masuk - Laporan Karyawan Masuk Per Departemen Per Tanggal Masuk: `POST http://192.168.10.100:5006/api/internal/ascends/shared/hrm/custom-reports/karyawan-masuk/pdf`
- Karyawan Keluar - Laporan Karyawan Keluar Per Departemen Per Tanggal Keluar: `POST http://192.168.10.100:5006/api/internal/ascends/shared/hrm/custom-reports/karyawan-keluar/pdf`
- Karyawan Keluar Tahunan - Laporan Karyawan Keluar Tahunan ({Status}): `POST http://192.168.10.100:5006/api/internal/ascends/shared/hrm/custom-reports/karyawan-keluar-tahunan/pdf`

Input tambahan khusus `karyawan-masuk` dan `karyawan-keluar`:

- `StartDate` + `EndDate`: periode tanggal masuk/keluar, contoh `2026-01-01` sampai `2026-06-30`.
- Alias yang diterima: `date_start` + `date_end`, `start_date` + `end_date`, `DateRange.StartDate` + `DateRange.EndDate`.
- Jika tidak dikirim, periode dari tanggal paling awal sampai paling akhir di XML.

Input tambahan khusus `karyawan-keluar-tahunan`:

- `PeriodStart` + `PeriodEnd`: periode tahun laporan, contoh `2025-01-01` sampai `2026-12-31`.
- Alias yang diterima: `period_start` + `period_end`, `Period.PeriodStart` + `Period.PeriodEnd`.
- `Status`: tipe karyawan dari Crystal Report Ascend, contoh `KK/KT` atau `ST`. Nilai ini muncul di title dan filename. Jika tidak dikirim, title menjadi `Laporan Karyawan Keluar Tahunan` (tanpa status).
- Alias yang diterima: `status`.

Input tambahan khusus `perbandingan-jumlah-karyawan-tahunan-per-bulan`:

- `PerDate`: tanggal referensi periode laporan, contoh `2026-04-30`. Menentukan tahun (PerDate-1 dan PerDate) dan bulan (Januari sampai bulan PerDate).
- Alias yang diterima: `per_date`, `perdate`.
- Jika tidak dikirim, sistem memakai tanggal server saat ini.

Contoh `multipart/form-data` untuk Karyawan Masuk periode Januari-Juni 2026:

```text
DB_CompanyName=RU
StartDate=2026-01-01
EndDate=2026-06-30
xml_file=AnlReports.HRM.EmployeeList.xml
```

Contoh `multipart/form-data` untuk Karyawan Keluar Tahunan (KK/KT) periode 2025-2026:

```text
DB_CompanyName=RU
PeriodStart=2025-01-01
PeriodEnd=2026-12-31
Status=KK/KT
xml_file=AnlReports.HRM.EmployeeList.xml
```

Contoh `multipart/form-data` untuk Karyawan Keluar Tahunan (ST) periode 2025-2026:

```text
DB_CompanyName=RU
PeriodStart=2025-01-01
PeriodEnd=2026-12-31
Status=ST
xml_file=AnlReports.HRM.EmployeeList.xml
```

Input tambahan khusus untuk semua laporan Diagram Karyawan (`diagram-karyawan-per-departemen`, `diagram-karyawan-per-divisi`, `diagram-karyawan-per-masa-kerja`, `diagram-karyawan-per-level`, `diagram-karyawan-per-strata-pendidikan`, `diagram-karyawan-per-jenis-kelamin`, `diagram-karyawan-per-usia-generasi`):

- `PerDate`: tanggal referensi data karyawan aktif, contoh `2026-05-01`. Menentukan subtitle laporan.
- Alias yang diterima: `per_date`, `perdate`.
- Jika tidak dikirim, sistem memakai tanggal server saat ini.

Contoh `multipart/form-data` untuk Diagram Karyawan Per Departemen per 30 April 2026:

```text
DB_CompanyName=UC
PerDate=2026-04-30
xml_file=AnlReports.HRM.EmployeeList.xml
```

Contoh `multipart/form-data` untuk Diagram Karyawan Per Divisi per 30 April 2026:

```text
DB_CompanyName=UC
PerDate=2026-04-30
xml_file=AnlReports.HRM.EmployeeList.xml
```

Contoh `multipart/form-data` untuk Diagram Karyawan Per Jenis Kelamin per 30 April 2026:

```text
DB_CompanyName=RU
PerDate=2026-04-30
xml_file=AnlReports.HRM.EmployeeList.xml
```

Contoh `multipart/form-data` untuk Perbandingan Jumlah Karyawan Tahunan Per Bulan per 30 April 2026:

```text
DB_CompanyName=UC
PerDate=2026-04-30
xml_file=AnlReports.HRM.EmployeeList.xml
```

## Endpoint Shared Attendance Full

- Attendance Full - Laporan Absensi Briefing Harian (RU): `POST http://192.168.10.100:5006/api/internal/ascends/shared/hrm/attendance-full/absensi-briefing-harian-ru/pdf`
- Attendance Full - Laporan Absensi Briefing Harian (GSU): `POST http://192.168.10.100:5006/api/internal/ascends/shared/hrm/attendance-full/absensi-briefing-harian-gsu/pdf`
- Attendance Full - Laporan Rekapitulasi Absensi Briefing Harian (RU): `POST http://192.168.10.100:5006/api/internal/ascends/shared/hrm/attendance-full/rekapitulasi-absensi-briefing-harian-ru/pdf`
- Attendance Full - Laporan Rekapitulasi Absensi Briefing Harian (GSU): `POST http://192.168.10.100:5006/api/internal/ascends/shared/hrm/attendance-full/rekapitulasi-absensi-briefing-harian-gsu/pdf`
- Attendance Full - Data Peserta Penerima Makan Siang Ibadah Di Aula Per Departemen: `POST http://192.168.10.100:5006/api/internal/ascends/shared/hrm/attendance-full/data-peserta-makan-siang-ibadah-aula-per-departemen/pdf`
- Attendance Full - Laporan Data Peserta Penerima Makan Siang Shalat Jumat Per Departemen: `POST http://192.168.10.100:5006/api/internal/ascends/shared/hrm/attendance-full/data-peserta-makan-siang-shalat-jumat-per-departemen/pdf`
- Attendance Full - Laporan Absensi Individu: `POST http://192.168.10.100:5006/api/internal/ascends/shared/hrm/attendance-full/absensi-individu/pdf`
- Attendance Full - Laporan Kehadiran Kru Stick: `POST http://192.168.10.100:5006/api/internal/ascends/shared/hrm/attendance-full/kehadiran-kru-stick/pdf`
- Attendance Full - Laporan Kehadiran Kru Racip Dorong Dan Kru Racip Sambut: `POST http://192.168.10.100:5006/api/internal/ascends/shared/hrm/attendance-full/kehadiran-kru-racip/pdf`
- Attendance Full - Laporan Kehadiran Kru Bahan Baku: `POST http://192.168.10.100:5006/api/internal/ascends/shared/hrm/attendance-full/kehadiran-kru-bahan-baku/pdf`
- Attendance Full - Laporan Persentase Kehadiran Mingguan Per Departemen: `POST http://192.168.10.100:5006/api/internal/ascends/shared/hrm/attendance-full/persentase-kehadiran-mingguan-per-departemen/pdf`
- Attendance Full - Laporan Persentase Kehadiran Bulanan: `POST http://192.168.10.100:5006/api/internal/ascends/shared/hrm/attendance-full/persentase-kehadiran-bulanan/pdf`
- Attendance Full - Laporan Rekapitulasi Kehadiran < 93 % Tahunan: `POST http://192.168.10.100:5006/api/internal/ascends/shared/hrm/attendance-full/rekapitulasi-kehadiran-kurang-93-tahunan/pdf`
- Attendance Full - Laporan Rekapitulasi Pengabaian Keterlambatan Tahunan: `POST http://192.168.10.100:5006/api/internal/ascends/shared/hrm/attendance-full/rekapitulasi-pengabaian-keterlambatan-tahunan/pdf`
- Attendance Full - Laporan Pengabaian Keterlambatan & Kehadiran Manual Per Departemen: `POST http://192.168.10.100:5006/api/internal/ascends/shared/hrm/attendance-full/pengabaian-keterlambatan-kehadiran-manual/pdf`

## Endpoint Shared Late Sign In

- Late Sign In - Laporan Durasi & Denda Keterlambatan Per Departemen: `POST http://192.168.10.100:5006/api/internal/ascends/shared/hrm/late-sign-in/durasi-denda-keterlambatan/pdf`

## Endpoint Shared Overtime

- Overtime - Laporan Lembur Bulanan Per Departemen: `POST http://192.168.10.100:5006/api/internal/ascends/shared/hrm/overtime/lembur-bulanan/pdf`

## Endpoint Shared Attendance

- Attendance - Laporan Perbandingan Kehadiran Per Bulan: `POST http://192.168.10.100:5006/api/internal/ascends/shared/hrm/attendance/perbandingan-kehadiran-per-bulan/pdf`
- Attendance - Laporan Keterlambatan Kehadiran Briefing Harian: `POST http://192.168.10.100:5006/api/internal/ascends/shared/hrm/attendance/keterlambatan-kehadiran-briefing-harian/pdf`

Parameter tambahan:

- `start_date` + `end_date`: periode laporan attendance, contoh `2026-01-01` sampai `2026-06-12`. Jika tidak dikirim, periode diambil dari tanggal data XML.

## Endpoint Shared Holiday

- Holiday - Daftar Libur Dan Cuti Bersama: `POST http://192.168.10.100:5006/api/internal/ascends/shared/hrm/holiday/daftar-libur-cuti-bersama/pdf`

## Endpoint Shared Other Income Deduction

- Other Income Deduction - Laporan Pendapatan Lain-Lain: `POST http://192.168.10.100:5006/api/internal/ascends/shared/hrm/other-income-deduction/pendapatan-lain-lain/pdf`

## Endpoint Shared Warning Notice

- Warning Notice - Laporan Surat Peringatan: `POST http://192.168.10.100:5006/api/internal/ascends/shared/hrm/warning-notice/surat-peringatan/pdf`

## Endpoint Shared Loss Time

- Loss Time - Laporan Loss Time: `POST http://192.168.10.100:5006/api/internal/ascends/shared/hrm/loss-time/pdf`

## Endpoint Shared Absence

- Absence - Laporan Ketidakhadiran Bulanan: `POST http://192.168.10.100:5006/api/internal/ascends/shared/hrm/absence/ketidakhadiran-bulanan/pdf`

## Endpoint Shared Employee Termination

- Employee Termination - Laporan Karyawan Keluar Per Departemen Per Tanggal Keluar: `POST http://192.168.10.100:5006/api/internal/ascends/shared/hrm/employee-termination/pdf`

## Input

Parameter field utama untuk semua endpoint Ascends Shared HRM:

- `xml_file`: file XML dari Ascend.
- `DB_CompanyName`: nama/kode perusahaan dari parameter Crystal Report Ascend, contoh `RU`, `GSU`, atau `UC`.
- `Sys_Username`: nama user print dari parameter Crystal Report Ascend, dipakai untuk footer `Dicetak oleh`, contoh `Windi`.

Input XML alternatif yang tetap diterima untuk kebutuhan testing:

- field `xml` berisi string XML
- raw XML body dengan `Content-Type: application/xml`

Catatan: file `AnlReports.HRM.AttendanceFull.xml` bisa berukuran lebih dari 100 MB, jadi limit PHP/web server perlu minimal mengikuti konfigurasi upload 200 MB.

Fallback kompatibilitas lama:

- `company`: fallback internal/test jika `DB_CompanyName` belum dikirim.
- `Sys_UserName`: alias lama untuk nama user print jika `Sys_Username` belum dikirim.

Catatan: `DB_CompanyName` dipakai lebih dulu dibanding field form `company`.

Input tambahan khusus `list-karyawan-habis-kontrak`:

- `month` + `year`: filter karyawan yang `Expiry Date`-nya berada di bulan/tahun tersebut.
- Alternatif: `start_date` + `end_date` untuk filter rentang `Expiry Date` tertentu.
- Alias yang diterima: `bulan` + `tahun`, atau `TglAwal` + `TglAkhir`.

Input tambahan khusus `absensi-briefing-harian-ru`:

- `Pilih Group`: parameter Crystal untuk group/divisi RU, contoh `RU vacuum & KD`.
- `start_date` + `end_date`: periode data attendance, contoh `2026-06-01` sampai `2026-06-05`.
- `report_date`: fallback untuk filter satu tanggal saja, contoh `2026-06-04`.
- `penanggung_jawab`: optional, contoh `SRO,`.
- `tema`: optional.
- Opsi `Pilih Group`: `PBB`, `RU vacuum & KD`, `RU vacuum & KD (Shift II)`, `RU vacuum & KD (Shift III)`, `RU Hulu 1`, `RU Hulu 2`, `RU Hilir`, `RU Hilir (Shift II)`, `RU Hilir (Shift III)`, `Office & Tally`, `SGR`, `BandSaw`, `Finger Joint A`, `Finger Joint B`, `SLP`.
- Alias yang diterima: `group`, `division`/`divisi`, `tanggal`/`date`, `responsible_person`, dan `theme`.

Input tambahan khusus `absensi-briefing-harian-gsu`:

- `Pilih Group`: parameter Crystal untuk group/divisi GSU, contoh `Bahan Baku, Washing & Broker`.
- `start_date` + `end_date`: periode data attendance, contoh `2026-06-01` sampai `2026-06-05`.
- `report_date`: fallback untuk filter satu tanggal saja, contoh `2026-06-04`.
- `penanggung_jawab`: optional.
- `tema`: optional.
- Opsi `Pilih Group`: `Bahan Baku, Washing & Broker`, `Sales`, `GSU Warehouse & Spare Part`, `Prod Regu A`, `Prod Regu B`, `Prod Regu C`, `Prod Regu D`, `Prod Ekstrusi Pagi`, `Prod Ekstrusi Sore`, `Prod Ekstrusi Malam`, `PIN HULU & HILIR`.
- Alias yang diterima: `group`, `division`/`divisi`, `tanggal`/`date`, `responsible_person`, dan `theme`.

Input tambahan khusus `rekapitulasi-absensi-briefing-harian-ru`:

- `start_date` + `end_date`: periode rekap data attendance, contoh `2026-06-01` sampai `2026-06-05`.
- `group`: optional, untuk membatasi rekap pada kode/nama group atau divisi tertentu.
- `report_date`: fallback untuk rekap satu tanggal saja.
- Alias yang diterima: `TglAwal` + `TglAkhir`, `division`/`divisi`, dan `tanggal`/`date`.
- Jika `group` tidak dikirim, report merekap seluruh divisi/group yang ada di XML.
- Parameter field utama dari Ascend: `DB_CompanyName` dan `Sys_UserName`.
- Rekap divisi mengikuti formula `sorting` Crystal; status hadir/telat dihitung dari `TimeNew` dan formula `Shift`.

Input tambahan khusus `rekapitulasi-absensi-briefing-harian-gsu`:

- `start_date` + `end_date`: periode rekap data attendance, contoh `2026-05-01` sampai `2026-05-31`.
- `report_date`: fallback untuk rekap satu bulan dari tanggal tersebut.
- Alias yang diterima: `TglAwal` + `TglAkhir`, dan `tanggal`/`date`.
- Jika hanya satu tanggal dikirim, sistem memakai satu bulan penuh dari tanggal tersebut.
- Parameter field utama dari Ascend: `DB_CompanyName` dan `Sys_UserName`.
- Rekap divisi mengikuti formula `sorting`, `NameDivisi`, dan `initialDivs` Crystal khusus GSU.

Input tambahan khusus `data-peserta-makan-siang-ibadah-aula-per-departemen`:

- `month` + `year`: periode bulan laporan, contoh `month=5` dan `year=2026`.
- Alternatif: `start_date` + `end_date`, contoh `2026-05-01` sampai `2026-05-31`.
- Alias yang diterima: `bulan` + `tahun`, atau `TglAwal` + `TglAkhir`.
- Jika periode tidak dikirim, sistem memakai bulan dari tanggal paling awal sampai paling akhir yang tersedia di XML.
- Kolom tanggal diambil dari hari Jumat dalam periode laporan, masing-masing memiliki subkolom `Cek` dan `Terima`.
- Record Selection mengikuti formula Crystal: hanya hari yang tampil untuk ibadah mingguan (hari Jumat) dan `Religion = Kristen`.
- Laporan dipisah 1 halaman untuk 1 departemen/divisi berdasarkan formula `InitianDept`.
- `PJ Penerima` mengikuti formula `InitianPJ`, misalnya `PHI = Difa Alamsah`, `PHU 1 = Yazuwar`, `PHU 2 = Tin Meilysa`, `VKD = Sihardel`, dan `KRUT = LRU`.

Input tambahan khusus `data-peserta-makan-siang-shalat-jumat-per-departemen`:

- `month` + `year`: periode bulan laporan, contoh `month=5` dan `year=2026`.
- Alternatif: `start_date` + `end_date`, contoh `2026-05-01` sampai `2026-05-31`.
- Alias yang diterima: `bulan` + `tahun`, atau `TglAwal` + `TglAkhir`.
- Jika periode tidak dikirim, sistem memakai bulan dari tanggal paling awal sampai paling akhir yang tersedia di XML.
- Kolom tanggal diambil dari hari Jumat dalam periode laporan, masing-masing memiliki subkolom `Cek` dan `Terima`.
- Record Selection mengikuti formula Crystal: `Religion = Islam`, `Sex = Male`, dan hari Jumat.
- Laporan dipisah 1 halaman untuk 1 departemen/divisi berdasarkan formula `InitianDept`.
- Untuk RU, `PJ Penerima` mengikuti formula `InitianPJ`, misalnya `PKB & SML = Rafi Prawira & SFD`, `VKD = SRO & Taufik Subiakto`, `PHI = Edi Sutoyo`, dan `PHU & KRUT = RZA`.
- Untuk GSU, `PJ Penerima` mengikuti formula `InitianPJ`, misalnya `WNB = SUM`, `WHS = Eko Herianto`, dan `PIN HULU/PIN HILIR = Marisa`.

Input tambahan khusus `absensi-individu`:

- `employee_code`: kode karyawan yang ingin ditampilkan.
- `employee_name`: alternatif filter nama karyawan.
- `start_date` + `end_date`: periode data attendance, contoh `2026-05-05` sampai `2026-06-04`.
- Alias yang diterima: `kode_karyawan`, `nama_karyawan`, `TglAwal` + `TglAkhir`.
- Jika `employee_code` dan `employee_name` tidak dikirim, sistem menampilkan seluruh karyawan dalam XML/periode, dengan tampilan per individu.
- `Waktu Bekerja` dihitung dari `Sign In` sampai `Sign Out`, dikurangi 1 jam istirahat jika durasi minimal 5 jam.

Input tambahan khusus `kehadiran-kru-stick`:

- `start_date` + `end_date`: periode data attendance, contoh `2026-05-05` sampai `2026-06-04`.
- Alias yang diterima: `TglAwal` + `TglAkhir`.
- Jika periode tidak dikirim, sistem memakai tanggal paling awal sampai paling akhir untuk data Kru Stick Borongan di XML.
- Data difilter dari XML Attendance Full dengan `Job_x0020_Title = Kru Stick Borongan` dan `Workgroup = Borongan Stick`.
- Kolom tanggal dibuat dinamis sesuai periode, masing-masing berisi subkolom `In` dan `Out`.

Input tambahan khusus `kehadiran-kru-racip`:

- `start_date` + `end_date`: periode data attendance, contoh `2026-05-05` sampai `2026-06-04`.
- Alias yang diterima: `TglAwal` + `TglAkhir`.
- Jika periode tidak dikirim, sistem memakai tanggal paling awal sampai paling akhir untuk data kru racip di XML.
- Data difilter dari XML Attendance Full dengan `Job_x0020_Title = Operator Borongan Sawmill` dan `Workgroup = Borongan Sawmill`.
- Kolom tanggal dibuat dinamis sesuai periode, masing-masing berisi subkolom `In` dan `Out`.
- Jika XML/periode tidak memiliki data kru racip, PDF tetap tampil dengan tabel kosong.

Input tambahan khusus `kehadiran-kru-bahan-baku`:

- `start_date` + `end_date`: periode data attendance, contoh `2026-05-05` sampai `2026-06-04`.
- Alias yang diterima: `TglAwal` + `TglAkhir`.
- Jika periode tidak dikirim, sistem memakai tanggal paling awal sampai paling akhir untuk data kru bahan baku di XML.
- Data difilter dari XML Attendance Full dengan `Daily_x0020_Worker_x0020_Type_x0020_Code = BR` dan `Job_x0020_Title = Kru Borongan Penerimaan Bahan Baku`, mengikuti record selection `{Attendance.Daily Worker Type Code} = "BR" and {@Group} startswith "A"`.
- Kolom tanggal dibuat dinamis sesuai periode, masing-masing berisi subkolom `In` dan `Out`.
- Jika XML/periode tidak memiliki data kru bahan baku, PDF tetap tampil dengan tabel kosong.

Input tambahan khusus `persentase-kehadiran-mingguan-per-departemen`:

- `start_date` + `end_date`: periode data attendance, contoh `2026-05-01` sampai `2026-05-31`.
- Alias yang diterima: `TglAwal` + `TglAkhir`.
- Jika periode tidak dikirim, sistem memakai tanggal paling awal sampai paling akhir yang tersedia di XML.

Input tambahan khusus `persentase-kehadiran-bulanan`:

- `Pilih Type`: parameter Crystal Report Ascend, contoh `KK/KT` atau `Staff`.
- Alias yang diterima: `Pilih_x0020_Type`, `pilih_type`, `type`, atau `Type`.
- `KK/KT`: menampilkan status pekerja yang diawali `KK`, `KT`, atau `BR`.
- `Staff`: menampilkan status pekerja yang diawali `ST`.
- Departemen yang diawali `ODP` atau `Management` tidak ditampilkan.
- `start_date` + `end_date`: periode data attendance, contoh `2026-05-01` sampai `2026-05-31`.
- Alias tanggal yang diterima: `TglAwal` + `TglAkhir`.

Input tambahan khusus `rekapitulasi-kehadiran-kurang-93-tahunan`:

- `Pilih Status`: parameter Crystal Report Ascend, contoh `KK/KT` atau `Staff`.
- Alias yang diterima: `Pilih_x0020_Status`, `pilih_status`, `status`, `Status`, `Kategori`, `category`, atau `kategori`.
- `Staff`: membaca `Daily_x0020_Worker_x0020_Type_x0020_Code` yang diawali `ST`.
- `KK/KT`: membaca kode yang diawali `KT`, `KK`, atau `BR`.
- `start_date` + `end_date`: periode data attendance tahunan, contoh `2026-01-01` sampai `2026-06-09`.
- Alias tanggal yang diterima: `TglAwal` + `TglAkhir`.
- Jika periode tidak dikirim, sistem memakai tanggal paling awal sampai paling akhir yang tersedia di XML.
- Persentase bulanan mengikuti rumus Crystal `({totalHariKerja} - Sum(TipeI, Employee Code)) / {totalHariKerja} * 100`.
- `totalHariKerja` mengikuti mapping Crystal per bulan: Jan 24, Feb 21, Mar 26, Apr 24, Mei 18, Jun 25, Jul 24, Agt 26, Sep 25, Okt 25, Nov 26, Des 23.
- Baris hanya tampil jika ada minimal satu bulan dengan persentase `> 0` dan `< 93`.

Input tambahan khusus `rekapitulasi-pengabaian-keterlambatan-tahunan`:

- `Pilih Status`: parameter Crystal Report Ascend, contoh `KK/KT` atau `Staff`.
- Alias yang diterima: `Pilih_x0020_Status`, `pilih_status`, `status`, `Status`, `Kategori`, `category`, atau `kategori`.
- `Staff`: membaca `Daily_x0020_Worker_x0020_Type_x0020_Code = ST`.
- `KK/KT`: membaca kode yang diawali `KT` atau `KK`.
- Data hanya dihitung jika `Last_x0020_Modified_x0020_By` terisi, sesuai formula Crystal `{Attendance.Last Modified By} <> " "`.
- Employee code yang diawali `120543`, `110104`, `110131`, `110159`, `120422`, `120523`, `130673`, `130891`, `131060`, atau `131107` tidak ditampilkan.
- `start_date` + `end_date`: periode data attendance tahunan, contoh `2026-01-01` sampai `2026-06-01`.
- Alias tanggal yang diterima: `TglAwal` + `TglAkhir`.
- Jika periode tidak dikirim, sistem memakai tanggal paling awal sampai paling akhir yang tersedia di XML.
- Setiap baris attendance yang lolos filter dihitung sebagai 1 pengabaian pada bulan terkait. Baris total bawah berisi total pengabaian per bulan dan grand total.

Input tambahan khusus `pengabaian-keterlambatan-kehadiran-manual`:

- `start_date` + `end_date`: periode data attendance, contoh `2026-05-05` sampai `2026-06-04`.
- Alias tanggal yang diterima: `TglAwal` + `TglAkhir`.
- `Pilih Status`: parameter Crystal Report Ascend, contoh `KK/KT` atau `Staff`.
- Alias status yang diterima: `Pilih_x0020_Status`, `pilih_status`, `status`, `Status`, `Kategori`, `category`, atau `kategori`.
- Mapping XML: `Staff` membaca `Daily_x0020_Worker_x0020_Type_x0020_Code = ST`; `KK/KT` membaca kode yang diawali `KK` atau `KT`.
- Data hanya tampil jika `Last_x0020_Modified_x0020_By` terisi, sesuai formula Crystal `{Attendance.Last Modified By} <> ""`.
- Jika status tidak dikirim, default `KK/KT`.
- Jika periode tidak dikirim, sistem memakai tanggal paling awal sampai paling akhir yang tersedia di XML.

Input tambahan khusus `durasi-denda-keterlambatan`:

- Gunakan XML `AnlReports.HRM.LateEarly.xml` dari modul Late/Early Ascend.
- `Pilih Type`: parameter Crystal Report Ascend, contoh `KK/KT` atau `Staff`.
- Alias yang diterima: `Pilih_x0020_Type`, `pilih_type`, `type`, atau `Type`.
- `Staff`: membaca `Daily_x0020_Worker_x0020_Type_x0020_Code = ST`.
- `KK/KT`: membaca kode `KK`, `KT`, atau `BR`.
- Data hanya tampil jika `Ignore_x0020_Late` bukan `Ignore` dan `Late_x0020_Sign_x0020_In > 0`.
- Departemen dengan `Department_x0020_Code` diawali `0101` atau `Department_x0020_Name` diawali `Management` tidak ditampilkan.
- `DateInput` atau `date_input`: tanggal batas perubahan nominal denda sesuai formula Crystal. Jika tidak dikirim, sistem memakai tanggal awal periode dikurangi 1 hari.
- Formula nominal: `ST` memakai `1500 x Late Sign In` setelah `DateInput`, selain itu `1000 x Late Sign In`; non-`ST` memakai `400 x Late Sign In` setelah `DateInput`, selain itu `250 x Late Sign In`.
- `start_date` + `end_date`: periode data late/early, contoh `2026-05-01` sampai `2026-05-31`.
- Alias tanggal yang diterima: `TglAwal` + `TglAkhir`.
- Jika periode tidak dikirim, sistem memakai tanggal paling awal sampai paling akhir yang tersedia di XML.

Input tambahan khusus `lembur-bulanan`:

- Gunakan XML `AnlReports.HRM.Overtime.xml` dari modul Overtime Ascend.
- `Pilih Tipe`: parameter Crystal Report Ascend, contoh `KK/KT` atau `Staff`.
- Alias yang diterima: `Pilih_x0020_Tipe`, `pilih_tipe`, `pilihTipe`, `tipe`, atau `Tipe`.
- `Staff`: membaca `Daily_x0020_Worker_x0020_Type_x0020_Code = ST`.
- `KK/KT`: membaca kode `KK`, `KT`, atau `BR`.
- Kolom `Jam` dihitung dari `Original_x0020_Hours`, `Total Hari` dari jumlah tanggal lembur karyawan, dan `Total Lemburan` dari `Overtime_x002F_Hours`.
- `start_date` + `end_date`: periode data overtime, contoh `2026-05-01` sampai `2026-05-31`.
- Alias tanggal yang diterima: `TglAwal` + `TglAkhir`.
- Jika periode tidak dikirim, sistem memakai `FirstPeriod` dan `LastPeriod` dari `Table1` XML sebagai bulan laporan.

Input XML khusus `daftar-libur-cuti-bersama`:

- Gunakan XML `AnlReports.HRM.Holiday.xml` dari modul Holiday Ascend.
- Tabel XML yang dibaca: `Holiday`.
- Field XML yang dipakai: `Date` dan `Name`.

Input XML khusus `pendapatan-lain-lain`:

- Gunakan XML `AnlReports.HRM.OtherIncomeDeduction.xml` dari modul Other Income Deduction Ascend.
- Tabel XML yang dibaca: `income`.
- Field utama yang ditampilkan: `Nama Lengkap`, `Tanggal`, `Keterangan`, `Disetujui Oleh`, dan `Jumlah`.
- Data dengan `Approved By = employeeother` tidak ditampilkan, mengikuti output Crystal Report referensi.

Input tambahan khusus `ketidakhadiran-bulanan`:

- `Pilih_Kategori` (required): tipe karyawan, harus `KK/KT` atau `ST`.
- `AttendanceDate.StartDate` + `AttendanceDate.EndDate`: periode data absence, contoh `2026-05-05` sampai `2026-06-04`.
- Mapping XML: `KK/KT` membaca `Daily_x0020_Worker_x0020_Type_x0020_Code` = `KK` atau `KT`; `ST` membaca kode `ST`.
- Jika periode tidak dikirim, sistem memakai tanggal paling awal sampai paling akhir yang tersedia di XML.

Input tambahan khusus `employee-termination`:

- Gunakan XML `AnlReports.HRM.Retirement.xml` dari modul Retirement Ascend.
- Tabel XML yang dibaca: `Employees`.
- Field utama yang ditampilkan: `Full_x0020_Name` (Nama), `Sex` (L/P), `Job_x0020_Title` (Jabatan), `Status_x0020_Type` (Status), `Level` (Level), `Date_x0020_of_x0020_Join` (Tanggal Masuk), `Date` (Tanggal Keluar), `Retirement_x0020_Reason` (Alasan Keluar), `Department_x0020_Name` (Departemen).
- `start_date` + `end_date`: periode tanggal keluar, contoh `2026-05-01` sampai `2026-05-31`.
- Alias tanggal yang diterima: `TglAwal` + `TglAkhir`.
- Jika periode tidak dikirim, sistem memakai tanggal paling awal sampai paling akhir yang tersedia di XML.
- Laporan dikelompokkan per departemen (`Department_x0020_Name`), masing-masing menampilkan akumulasi L/P.

Input tambahan khusus `thr`:

- `Pilih THR`: parameter Crystal Report Ascend, contoh `Idul Fitri`, `Natal`, atau `Imlek`.
- Alias yang diterima: `Pilih_x0020_THR`, `pilih_thr`, `PilihTHR`, `thr`, atau `THR`.
- Jika tidak dikirim, default `Idul Fitri`.
- Laporan menampilkan 3 section per tipe karyawan (Staff, Karyawan Kontrak, Karyawan Tetap) dalam 1 PDF, masing-masing dengan page break.

Input tambahan khusus `loss-time`:

- Gunakan XML `AnlReports.HRM.LossTime.xml` dari modul Loss Time Ascend.
- Tabel XML yang dibaca: `LossTime`.
- `Pilih Tipe`: parameter Crystal Report Ascend, contoh `KK/KT` atau `Staff`.
- Alias yang diterima: `Pilih_x0020_Tipe`, `pilih_tipe`, `Pilih Type`, `Pilih_x0020_Type`, `type`, atau `Type`.
- `Staff`: membaca `Daily_x0020_Worker_x0020_Type_x0020_Code = ST`.
- `KK/KT`: membaca kode `KK`, `KT`, atau `BR`.
- `start_date` + `end_date`: periode laporan, contoh `2026-05-01` sampai `2026-05-31`.
- Alias tanggal yang diterima: `TglAwal` + `TglAkhir`.
- Jika periode tidak dikirim, sistem memakai bulan dari tanggal paling awal sampai paling akhir yang tersedia di XML.
- Kolom yang ditampilkan: NIK, Nama Karyawan, Jabatan, Tanggal Izin, Total Jam, Total Menit, Keterangan.
- Laporan dikelompokkan per departemen, masing-masing menampilkan akumulasi Status (KK/KT/ST/BR) dan total jam/menit.

Contoh `multipart/form-data`:

```text
DB_CompanyName=UC
xml_file=AnlReports.HRM.EmployeeList.xml
```

Contoh `multipart/form-data` untuk cek karyawan habis kontrak bulan Juni 2026:

```text
DB_CompanyName=UC
month=6
year=2026
xml_file=AnlReports.HRM.EmployeeList.xml
```

Contoh `multipart/form-data` untuk Absensi Briefing Harian RU group `RU vacuum & KD` periode 01-Jun-2026 sampai 05-Jun-2026:

```text
DB_CompanyName=RU
Sys_Username=Windi
Pilih Group=RU vacuum & KD
start_date=2026-06-01
end_date=2026-06-05
penanggung_jawab=SRO,
xml_file=AnlReports.HRM.AttendanceFull.xml
```

Contoh `multipart/form-data` untuk Persentase Kehadiran Mingguan Per Departemen periode Mei 2026:

```text
DB_CompanyName=RU
start_date=2026-05-01
end_date=2026-05-31
xml_file=AnlReports.HRM.AttendanceFull.xml
```

Contoh `multipart/form-data` untuk Kehadiran Kru Stick:

```text
DB_CompanyName=RU
start_date=2026-05-05
end_date=2026-06-04
xml_file=AnlReports.HRM.AttendanceFull.xml
```

Contoh `multipart/form-data` untuk Pengabaian Keterlambatan & Kehadiran Manual kategori ST:

```text
DB_CompanyName=RU
kategori=ST
start_date=2026-05-05
end_date=2026-06-04
xml_file=AnlReports.HRM.AttendanceFull.xml
```

Contoh `multipart/form-data` untuk Ketidakhadiran Bulanan:

```text
DB_CompanyName=RU
Pilih_Kategori=KK/KT
AttendanceDate.StartDate=2026-05-05
AttendanceDate.EndDate=2026-06-04
xml_file=AnlReports.HRM.Absence.xml
```

Contoh `multipart/form-data` untuk THR:

```text
DB_CompanyName=GSU
Pilih THR=Idul Fitri
xml_file=AnlReports.HRM.EmployeeList.xml
```

Contoh `multipart/form-data` untuk Loss Time:

```text
DB_CompanyName=RU
Pilih Tipe=KK/KT
start_date=2026-05-01
end_date=2026-05-31
xml_file=AnlReports.HRM.LossTime.xml
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

Contoh:

- `Employee List - List Karyawan (RU).pdf`
- `Employee List - Laporan Daftar Karyawan (GSU).pdf`
- `Employee List - Laporan Karyawan Masuk Per Departemen Per Tanggal Masuk (RU).pdf`
- `Employee List - Laporan Karyawan Masuk Per Departemen Per Tanggal Masuk (GSU).pdf`
- `Employee List - Laporan Karyawan Masuk Per Departemen Per Tanggal Masuk (UC).pdf`
- `Employee List - Laporan Kehadiran KK KT ST (RU).pdf`
- `Employee List - Laporan List Karyawan Habis Kontrak (RU).pdf`
- `Employee List - Laporan Perbandingan Jumlah Karyawan Tahunan Per Bulan (UC).pdf`
- `Employee List - Laporan Usia Generasi Berdasakan Tahun Kelahiran dan Masa Kerja (UC).pdf`
- `Employee Termination - Laporan Karyawan Keluar Per Departemen Per Tanggal Keluar (RU).pdf`
- `Attendance Full - Laporan Absensi Briefing Harian (RU) - VKD.pdf`
- `Attendance Full - Laporan Kehadiran Kru Stick (RU).pdf`
- `Attendance Full - Laporan Kehadiran Kru Racip Dorong Dan Kru Racip Sambut (RU).pdf`
- `Attendance Full - Laporan Persentase Kehadiran Bulanan KK KT (RU).pdf`
- `Attendance Full - Laporan Persentase Kehadiran Mingguan Per Departemen (RU).pdf`
- `Attendance Full - Laporan Pengabaian Keterlambatan & Kehadiran Manual Staff Per Departemen (RU).pdf`
- `Absence - Laporan Ketidakhadiran Bulanan (RU) - KK/KT.pdf`
- `Loss Time - Laporan Loss Time (RU).pdf`
- `Employee List - Laporan THR (Idul Fitri) (GSU).pdf`
- `Employee List - Laporan Diagram Karyawan Per Departemen (RU).pdf`
- `Employee List - Laporan Diagram Karyawan Per Divisi (RU).pdf`
- `Employee List - Laporan Diagram Karyawan Per Masa Kerja (RU).pdf`
- `Employee List - Laporan Diagram Karyawan Per Level (RU).pdf`
- `Employee List - Laporan Diagram Karyawan Per Strata Pendidikan (RU).pdf`
- `Employee List - Laporan Diagram Karyawan Per Jenis Kelamin (RU).pdf`
- `Employee List - Laporan Diagram Karyawan Per Usia Generasi (RU).pdf`
- `Custom Reports - Laporan Karyawan Masuk Per Departemen Per Tanggal Masuk (RU).pdf`
- `Custom Reports - Laporan Karyawan Keluar Per Departemen Per Tanggal Keluar (RU).pdf`
- `Custom Reports - Laporan Karyawan Keluar Tahunan (KK/KT) (RU).pdf`
- `Custom Reports - Laporan Karyawan Keluar Tahunan (ST) (RU).pdf`

## Response Gagal

- `422 application/json` jika XML kosong, tidak valid, atau tidak bisa diproses.

## Template Shared HRM Tersedia

Template Blade shared Custom Reports berada di `resources/views/ascends/shared/hrm/custom_reports`.

- `custom_reports/karyawan_masuk`
- `custom_reports/karyawan_keluar`
- `custom_reports/karyawan_keluar_tahunan`

Template Blade shared Employee List berada di `resources/views/ascends/shared/hrm/employee_list`.

- `employee_list/list_karyawan`
- `employee_list/daftar_karyawan`
- `employee_list/daftar_karyawan_berdasarkan_abjad`
- `employee_list/data_karyawan_status_kerja`
- `employee_list/karyawan_aktif_per_departemen`
- `employee_list/karyawan_masuk_per_departemen_per_tanggal_masuk`
- `employee_list/karyawan_per_agama`
- `employee_list/karyawan_per_departemen_per_jabatan`
- `employee_list/karyawan_per_etnis`
- `employee_list/karyawan_per_level`
- `employee_list/karyawan_per_masa_kerja`
- `employee_list/karyawan_per_umur`
- `employee_list/kehadiran_kk_kt_st`
- `employee_list/list_karyawan_habis_kontrak`
- `employee_list/perbandingan_jumlah_karyawan_tahunan_per_bulan`
- `employee_list/usia_generasi_tahun_kelahiran_masa_kerja`
- `employee_list/thr`
- `employee_list/diagram_karyawan_per_departemen`
- `employee_list/diagram_karyawan_per_divisi`
- `employee_list/diagram_karyawan_per_masa_kerja`
- `employee_list/diagram_karyawan_per_level`
- `employee_list/diagram_karyawan_per_strata_pendidikan`
- `employee_list/diagram_karyawan_per_jenis_kelamin`
- `employee_list/diagram_karyawan_per_usia_generasi`

Template Blade shared Employee Termination berada di `resources/views/ascends/shared/hrm/employee_termination`.

- `employee_termination`

Template Blade shared Attendance Full berada di `resources/views/ascends/shared/hrm/attendance_full`.

- `attendance_full/absensi_briefing_harian_ru`
- `attendance_full/rekapitulasi_absensi_briefing_harian_ru`
- `attendance_full/rekapitulasi_absensi_briefing_harian_gsu`
- `attendance_full/data_peserta_makan_siang_ibadah_aula_per_departemen`
- `attendance_full/data_peserta_makan_siang_shalat_jumat_per_departemen`
- `attendance_full/kehadiran_kru_stick`
- `attendance_full/kehadiran_kru_racip`
- `attendance_full/kehadiran_kru_bahan_baku`
- `attendance_full/persentase_kehadiran_bulanan`
- `attendance_full/persentase_kehadiran_mingguan_per_departemen`
- `attendance_full/rekapitulasi_kehadiran_kurang_93_tahunan`
- `attendance_full/rekapitulasi_pengabaian_keterlambatan_tahunan`
- `attendance_full/pengabaian_keterlambatan_kehadiran_manual`

Template Blade shared Attendance berada di `resources/views/ascends/shared/hrm/attendance`.

- `attendance/perbandingan_kehadiran_per_bulan`
- `attendance/keterlambatan_kehadiran_briefing_harian`

Template Blade shared Absence berada di `resources/views/ascends/shared/hrm/absence`.

- `absence/ketidakhadiran_bulanan`

Template Blade shared Loss Time berada di `resources/views/ascends/shared/hrm/loss_time`.

- `loss_time`

Catatan: semua endpoint di atas memakai pola shared yang sama. XML menjadi sumber data laporan, sedangkan parameter `DB_CompanyName` menjadi sumber label perusahaan pada title dan filename. Field form `company` hanya fallback jika `DB_CompanyName` belum dikirim.
