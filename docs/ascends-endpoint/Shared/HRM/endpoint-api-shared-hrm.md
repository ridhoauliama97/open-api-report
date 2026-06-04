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
- `report_date`: tanggal data attendance, contoh `2026-06-04`.
- `penanggung_jawab`: optional, contoh `SRO,`.
- `tema`: optional.
- Alias yang diterima: `division`/`divisi`, `tanggal`/`date`, `responsible_person`, dan `theme`.

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

Contoh `multipart/form-data` untuk Absensi Briefing Harian group VKD tanggal 04-Jun-2026:

```text
company=RU
group=VKD
report_date=2026-06-04
penanggung_jawab=SRO,
xml_file=AnlReports.HRM.AttendanceFull.xml
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

Catatan: semua endpoint di atas memakai pola shared yang sama. XML menjadi sumber data laporan, sedangkan `company` menjadi sumber label perusahaan pada title dan filename.
