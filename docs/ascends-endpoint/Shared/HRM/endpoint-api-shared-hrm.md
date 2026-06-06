# Dokumentasi Hit Endpoint API Ascend Shared HRM

Dokumen ini berisi endpoint internal untuk test/render laporan HRM Ascend yang memakai Blade shared.

- Base host: `http://192.168.10.100:5006`
- Prefix API: `/api`

## Konsep Shared HRM

Template shared HRM dipakai supaya struktur Blade laporan bisa digunakan lintas perusahaan:

- `RU`
- `GSU`
- `UC`

Karena XML HRM Ascend tidak memiliki field company yang terisi, request shared wajib mengirim field `company`.

## Endpoint Shared

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

## Endpoint Shared Attendance Full

- Attendance Full - Laporan Absensi Briefing Harian: `POST http://192.168.10.100:5006/api/internal/ascends/shared/hrm/attendance-full/absensi-briefing-harian/pdf`
- Attendance Full - Laporan Rekapitulasi Absensi Briefing Harian: `POST http://192.168.10.100:5006/api/internal/ascends/shared/hrm/attendance-full/rekapitulasi-absensi-briefing-harian/pdf`
- Attendance Full - Laporan Absensi Individu: `POST http://192.168.10.100:5006/api/internal/ascends/shared/hrm/attendance-full/absensi-individu/pdf`
- Attendance Full - Laporan Kehadiran Kru Stick: `POST http://192.168.10.100:5006/api/internal/ascends/shared/hrm/attendance-full/kehadiran-kru-stick/pdf`
- Attendance Full - Laporan Persentase Kehadiran Mingguan Per Departemen: `POST http://192.168.10.100:5006/api/internal/ascends/shared/hrm/attendance-full/persentase-kehadiran-mingguan-per-departemen/pdf`
- Attendance Full - Laporan Pengabaian Keterlambatan & Kehadiran Manual Per Departemen: `POST http://192.168.10.100:5006/api/internal/ascends/shared/hrm/attendance-full/pengabaian-keterlambatan-kehadiran-manual/pdf`

## Endpoint Shared Absence

- Absence - Laporan Ketidakhadiran Bulanan: `POST http://192.168.10.100:5006/api/internal/ascends/shared/hrm/absence/ketidakhadiran-bulanan/pdf`

## Input

Input XML sama seperti report Ascends XML lain:

- `multipart/form-data` field `xml_file`
- field `xml` berisi string XML
- raw XML body dengan `Content-Type: application/xml`

Catatan: file `AnlReports.HRM.AttendanceFull.xml` bisa berukuran lebih dari 100 MB, jadi limit PHP/web server perlu minimal mengikuti konfigurasi upload 200 MB.

Input tambahan wajib untuk shared:

- `company`: `RU`, `GSU`, atau `UC`

Input tambahan khusus `list-karyawan-habis-kontrak`:

- `month` + `year`: filter karyawan yang `Expiry Date`-nya berada di bulan/tahun tersebut.
- Alternatif: `start_date` + `end_date` untuk filter rentang `Expiry Date` tertentu.
- Alias yang diterima: `bulan` + `tahun`, atau `TglAwal` + `TglAkhir`.

Input tambahan khusus `absensi-briefing-harian`:

- `group`: kode/nama group atau divisi yang tampil di judul, contoh `VKD`.
- `start_date` + `end_date`: periode data attendance, contoh `2026-06-01` sampai `2026-06-05`.
- `report_date`: fallback untuk filter satu tanggal saja, contoh `2026-06-04`.
- `penanggung_jawab`: optional, contoh `SRO,`.
- `tema`: optional.
- Alias yang diterima: `division`/`divisi`, `tanggal`/`date`, `responsible_person`, dan `theme`.

Input tambahan khusus `rekapitulasi-absensi-briefing-harian`:

- `start_date` + `end_date`: periode rekap data attendance, contoh `2026-06-01` sampai `2026-06-05`.
- `group`: optional, untuk membatasi rekap pada kode/nama group atau divisi tertentu.
- `report_date`: fallback untuk rekap satu tanggal saja.
- Alias yang diterima: `TglAwal` + `TglAkhir`, `division`/`divisi`, dan `tanggal`/`date`.
- Jika `group` tidak dikirim, report merekap seluruh divisi/group yang ada di XML.

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

Input tambahan khusus `persentase-kehadiran-mingguan-per-departemen`:

- `start_date` + `end_date`: periode data attendance, contoh `2026-05-01` sampai `2026-05-31`.
- Alias yang diterima: `TglAwal` + `TglAkhir`.
- Jika periode tidak dikirim, sistem memakai tanggal paling awal sampai paling akhir yang tersedia di XML.

Input tambahan khusus `pengabaian-keterlambatan-kehadiran-manual`:

- `start_date` + `end_date`: periode data attendance, contoh `2026-05-05` sampai `2026-06-04`.
- Alias tanggal yang diterima: `TglAwal` + `TglAkhir`.
- `kategori`: tipe/status/kategori karyawan dari form Ascend, contoh `ST`.
- Alias kategori yang diterima: `Kategori`, `category`, `Category`, `status`, `Status`, `tipe`, `Tipe`, `type`, `Type`, `PilihKategori`, `pilih_kategori`, `Pilih Kategori`, atau `Pilih_x0020_Kategori`.
- Mapping XML: nilai kategori membaca `Daily_x0020_Worker_x0020_Type_x0020_Code`. Contoh `KK/KT` membaca kode `KK` atau `KT`, sedangkan `ST` membaca kode `ST`.
- Jika kategori tidak dikirim, default `ST`.
- Jika periode tidak dikirim, sistem memakai tanggal paling awal sampai paling akhir yang tersedia di XML.

Input tambahan khusus `ketidakhadiran-bulanan`:

- `start_date` + `end_date`: periode data absence, contoh `2026-05-05` sampai `2026-06-04`.
- Alias yang diterima: `TglAwal` + `TglAkhir`.
- `Pilih Kategori`: tipe karyawan dari form Ascend, contoh `KK/KT` atau `ST`.
- Alias yang diterima: `Kategori`, `kategori`, `PilihKategori`, `pilih_kategori`, `Tipe`, `tipe`, `type`, atau `Type`.
- Mapping XML: `KK/KT` membaca `Daily_x0020_Worker_x0020_Type_x0020_Code` = `KK` atau `KT`; `ST` membaca kode `ST`.
- Jika periode tidak dikirim, sistem memakai tanggal paling awal sampai paling akhir yang tersedia di XML.

Contoh `multipart/form-data`:

```text
company=UC
xml_file=AnlReports.HRM.EmployeeList.xml
```

Contoh `multipart/form-data` untuk cek karyawan habis kontrak bulan Juni 2026:

```text
company=UC
month=6
year=2026
xml_file=AnlReports.HRM.EmployeeList.xml
```

Contoh `multipart/form-data` untuk Absensi Briefing Harian group VKD periode 01-Jun-2026 sampai 05-Jun-2026:

```text
company=RU
group=VKD
start_date=2026-06-01
end_date=2026-06-05
penanggung_jawab=SRO,
xml_file=AnlReports.HRM.AttendanceFull.xml
```

Contoh `multipart/form-data` untuk Persentase Kehadiran Mingguan Per Departemen periode Mei 2026:

```text
company=RU
start_date=2026-05-01
end_date=2026-05-31
xml_file=AnlReports.HRM.AttendanceFull.xml
```

Contoh `multipart/form-data` untuk Kehadiran Kru Stick:

```text
company=RU
start_date=2026-05-05
end_date=2026-06-04
xml_file=AnlReports.HRM.AttendanceFull.xml
```

Contoh `multipart/form-data` untuk Pengabaian Keterlambatan & Kehadiran Manual kategori ST:

```text
company=RU
kategori=ST
start_date=2026-05-05
end_date=2026-06-04
xml_file=AnlReports.HRM.AttendanceFull.xml
```

Contoh `multipart/form-data` untuk Ketidakhadiran Bulanan:

```text
company=RU
Pilih Kategori=KK/KT
start_date=2026-05-05
end_date=2026-06-04
xml_file=AnlReports.HRM.Absence.xml
```

## Response Sukses

- `200 application/pdf`
- `Content-Disposition: inline`

Title yang tampil di halaman PDF tetap memakai nama laporan tanpa prefix kategori:

```text
{Nama Laporan} ({company})
```

Filename PDF memakai prefix kategori folder:

```text
Employee List - {Nama Laporan} ({company}).pdf
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
- `Attendance Full - Laporan Absensi Briefing Harian (RU) - VKD.pdf`
- `Attendance Full - Laporan Kehadiran Kru Stick (RU).pdf`
- `Attendance Full - Laporan Persentase Kehadiran Mingguan Per Departemen (RU).pdf`
- `Attendance Full - Laporan Pengabaian Keterlambatan & Kehadiran Manual ST Per Departemen (RU).pdf`
- `Absence - Laporan Ketidakhadiran Bulanan (RU) - KK KT.pdf`

## Response Gagal

- `422 application/json` jika XML kosong, tidak valid, atau tidak bisa diproses.

## Template Shared HRM Tersedia

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

Template Blade shared Attendance Full berada di `resources/views/ascends/shared/hrm/attendance_full`.

- `attendance_full/absensi_briefing_harian`
- `attendance_full/rekapitulasi_absensi_briefing_harian`
- `attendance_full/kehadiran_kru_stick`
- `attendance_full/persentase_kehadiran_mingguan_per_departemen`
- `attendance_full/pengabaian_keterlambatan_kehadiran_manual`

Template Blade shared Absence berada di `resources/views/ascends/shared/hrm/absence`.

- `absence/ketidakhadiran_bulanan`

Catatan: semua endpoint di atas memakai pola shared yang sama. XML menjadi sumber data laporan, sedangkan `company` menjadi sumber label perusahaan pada title dan filename.
