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

- List Karyawan: `POST http://192.168.10.100:5006/api/internal/ascends/shared/hrm/list-karyawan/pdf`
- Laporan Daftar Karyawan: `POST http://192.168.10.100:5006/api/internal/ascends/shared/hrm/daftar-karyawan/pdf`
- Laporan Daftar Karyawan Berdasarkan Abjad: `POST http://192.168.10.100:5006/api/internal/ascends/shared/hrm/daftar-karyawan-berdasarkan-abjad/pdf`
- Laporan Data Karyawan Berdasarkan Status Kerja: `POST http://192.168.10.100:5006/api/internal/ascends/shared/hrm/data-karyawan-status-kerja/pdf`
- Laporan Karyawan Aktif Per Departemen: `POST http://192.168.10.100:5006/api/internal/ascends/shared/hrm/karyawan-aktif-per-departemen/pdf`
- Laporan Karyawan Masuk Per Departemen Per Tanggal Masuk: `POST http://192.168.10.100:5006/api/internal/ascends/shared/hrm/karyawan-masuk-per-departemen-per-tanggal-masuk/pdf`
- Laporan Karyawan Per Agama: `POST http://192.168.10.100:5006/api/internal/ascends/shared/hrm/karyawan-per-agama/pdf`
- Laporan Karyawan Per Departemen Per Jabatan: `POST http://192.168.10.100:5006/api/internal/ascends/shared/hrm/karyawan-per-departemen-per-jabatan/pdf`
- Laporan Karyawan Per Etnis: `POST http://192.168.10.100:5006/api/internal/ascends/shared/hrm/karyawan-per-etnis/pdf`
- Laporan Karyawan Per Level: `POST http://192.168.10.100:5006/api/internal/ascends/shared/hrm/karyawan-per-level/pdf`
- Laporan Karyawan Per Masa Kerja: `POST http://192.168.10.100:5006/api/internal/ascends/shared/hrm/karyawan-per-masa-kerja/pdf`
- Laporan Karyawan Per Umur: `POST http://192.168.10.100:5006/api/internal/ascends/shared/hrm/karyawan-per-umur/pdf`
- Laporan Perbandingan Jumlah Karyawan Tahunan Per Bulan: `POST http://192.168.10.100:5006/api/internal/ascends/shared/hrm/perbandingan-jumlah-karyawan-tahunan-per-bulan/pdf`
- Laporan Usia Generasi Berdasakan Tahun Kelahiran dan Masa Kerja: `POST http://192.168.10.100:5006/api/internal/ascends/shared/hrm/usia-generasi-tahun-kelahiran-masa-kerja/pdf`

## Input

Input XML sama seperti report Ascends XML lain:

- `multipart/form-data` field `xml_file`
- field `xml` berisi string XML
- raw XML body dengan `Content-Type: application/xml`

Input tambahan wajib untuk shared:

- `company`: `RU`, `GSU`, atau `UC`

Contoh `multipart/form-data`:

```text
company=UC
xml_file=AnlReports.HRM.EmployeeList.xml
```

## Response Sukses

- `200 application/pdf`
- `Content-Disposition: inline`

Title dan filename mengikuti company yang dikirim, contoh:

- `List Karyawan (RU).pdf`
- `Laporan Daftar Karyawan (GSU).pdf`
- `Laporan Karyawan Masuk Per Departemen Per Tanggal Masuk (RU).pdf`
- `Laporan Karyawan Masuk Per Departemen Per Tanggal Masuk (GSU).pdf`
- `Laporan Karyawan Masuk Per Departemen Per Tanggal Masuk (UC).pdf`
- `Laporan Perbandingan Jumlah Karyawan Tahunan Per Bulan (UC).pdf`
- `Laporan Usia Generasi Berdasakan Tahun Kelahiran dan Masa Kerja (UC).pdf`

## Response Gagal

- `422 application/json` jika XML kosong, tidak valid, atau tidak bisa diproses.

## Template Shared HRM Tersedia

Template Blade shared berada di `resources/views/ascends/shared/hrm`.

- `list_karyawan`
- `daftar_karyawan`
- `daftar_karyawan_berdasarkan_abjad`
- `data_karyawan_status_kerja`
- `karyawan_aktif_per_departemen`
- `karyawan_masuk_per_departemen_per_tanggal_masuk`
- `karyawan_per_agama`
- `karyawan_per_departemen_per_jabatan`
- `karyawan_per_etnis`
- `karyawan_per_level`
- `karyawan_per_masa_kerja`
- `karyawan_per_umur`
- `perbandingan_jumlah_karyawan_tahunan_per_bulan`
- `usia_generasi_tahun_kelahiran_masa_kerja`

Catatan: semua endpoint di atas memakai pola shared yang sama. XML menjadi sumber data laporan, sedangkan `company` menjadi sumber label perusahaan pada title dan filename.
