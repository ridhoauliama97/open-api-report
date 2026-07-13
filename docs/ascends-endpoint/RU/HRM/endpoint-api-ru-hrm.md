# Dokumentasi Hit Endpoint API Ascend RU

Dokumen ini berisi endpoint internal untuk integrasi Ascend RU ke PDF report.

- Base host: `http://192.168.10.100:5006`
- Prefix API: `/api`

## Ascend XML Internal Print

Endpoint ini dipakai oleh Ascend custom print untuk mengirim XML hasil query/report dan menerima PDF langsung.

- List Karyawan RU: `POST http://192.168.10.100:5006/api/internal/ascends/ru/hrm/list-karyawan/pdf`
- Laporan Karyawan Per Masa Kerja: `POST http://192.168.10.100:5006/api/internal/ascends/ru/hrm/karyawan-per-masa-kerja/pdf`
- Laporan Data Karyawan (RU) - Status Kerja: `POST http://192.168.10.100:5006/api/internal/ascends/ru/hrm/data-karyawan-status-kerja/pdf`
- Laporan Daftar Karyawan (RU) - Berdasarkan Abjad: `POST http://192.168.10.100:5006/api/internal/ascends/ru/hrm/daftar-karyawan-berdasarkan-abjad/pdf`
- Laporan Daftar Karyawan (RU): `POST http://192.168.10.100:5006/api/internal/ascends/ru/hrm/daftar-karyawan/pdf`
- Laporan Karyawan Aktif Per Departemen (RU): `POST http://192.168.10.100:5006/api/internal/ascends/ru/hrm/karyawan-aktif-per-departemen/pdf`
- Laporan Karyawan Per Agama (RU): `POST http://192.168.10.100:5006/api/internal/ascends/ru/hrm/karyawan-per-agama/pdf`
- Laporan Karyawan Per Etnis (RU): `POST http://192.168.10.100:5006/api/internal/ascends/ru/hrm/karyawan-per-etnis/pdf`
- Laporan Karyawan Per Level (RU): `POST http://192.168.10.100:5006/api/internal/ascends/ru/hrm/karyawan-per-level/pdf`
- Laporan Karyawan Per Umur (RU): `POST http://192.168.10.100:5006/api/internal/ascends/ru/hrm/karyawan-per-umur/pdf`
- Laporan Karyawan Per Departemen Per Jabatan (RU): `POST http://192.168.10.100:5006/api/internal/ascends/ru/hrm/karyawan-per-departemen-per-jabatan/pdf`

Input yang didukung:

- `multipart/form-data` field `xml_file`
- field `xml` berisi string XML
- raw XML body dengan `Content-Type: application/xml`

Response sukses:

- `200 application/pdf`
- `Content-Disposition: attachment; filename="List-Karyawan-RU.pdf"`

Filename mengikuti laporan yang dipanggil, contoh:

- `List-Karyawan-RU.pdf`
- `Laporan Karyawan Per Masa Kerja (RU).pdf`
- `Laporan Data Karyawan (RU) - Status Kerja.pdf`
- `Laporan Daftar Karyawan (RU) - Berdasarkan Abjad.pdf`
- `Laporan Daftar Karyawan (RU).pdf`
- `Laporan Karyawan Aktif Per Departemen (RU).pdf`
- `Laporan Karyawan Per Agama (RU).pdf`
- `Laporan Karyawan Per Etnis (RU).pdf`
- `Laporan Karyawan Per Level (RU).pdf`
- `Laporan Karyawan Per Umur (RU).pdf`
- `Laporan Karyawan Per Departemen Per Jabatan (RU).pdf`
- `Sales Invoice (RU).pdf`

Response gagal:

- `422 application/json` jika XML kosong, tidak valid, atau tidak bisa diproses.
