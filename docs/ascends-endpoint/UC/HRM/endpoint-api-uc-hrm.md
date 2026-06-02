- List Karyawan (UC): `POST http://192.168.10.100:5006/api/internal/ascends/uc/hrm/list-karyawan/pdf`
- Laporan Karyawan Aktif Per Departemen (UC): `POST http://192.168.10.100:5006/api/internal/ascends/uc/hrm/karyawan-aktif-per-departemen/pdf`
- Laporan Daftar Karyawan (UC): `POST http://192.168.10.100:5006/api/internal/ascends/uc/hrm/daftar-karyawan/pdf`
- Laporan Daftar Karyawan (UC) - Berdasarkan Abjad: `POST http://192.168.10.100:5006/api/internal/ascends/uc/hrm/daftar-karyawan-berdasarkan-abjad/pdf`
- Laporan Data Karyawan (UC) - Status Kerja: `POST http://192.168.10.100:5006/api/internal/ascends/uc/hrm/data-karyawan-status-kerja/pdf`
- Laporan Karyawan Masuk Per Departemen Per Tanggal Masuk (UC): `POST http://192.168.10.100:5006/api/internal/ascends/uc/hrm/karyawan-masuk-per-departemen-per-tanggal-masuk/pdf`

Input XML sama seperti report Ascends XML lain:
- `multipart/form-data` field `xml_file`
- field `xml`
- raw XML body

Response sukses:
- `200 application/pdf`
- `Content-Disposition: inline`
