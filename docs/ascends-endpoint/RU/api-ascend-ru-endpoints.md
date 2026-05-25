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

Input yang didukung:

- `multipart/form-data` field `xml_file`
- field `xml` berisi string XML
- raw XML body dengan `Content-Type: application/xml`

Response sukses:

- `200 application/pdf`
- `Content-Disposition: inline; filename="List-Karyawan-RU.pdf"`

Filename mengikuti laporan yang dipanggil, contoh:

- `List-Karyawan-RU.pdf`
- `Laporan Karyawan Per Masa Kerja (RU).pdf`
- `Laporan Data Karyawan (RU) - Status Kerja.pdf`
- `Laporan Daftar Karyawan (RU) - Berdasarkan Abjad.pdf`

Response gagal:

- `422 application/json` jika XML kosong, tidak valid, atau tidak bisa diproses.

## Pola Implementasi Laporan Baru

Untuk setiap laporan baru Ascend RU berbasis XML, gunakan pola: screenshot layout lama + XML source + endpoint internal per laporan + Blade custom. Satu XML boleh dipakai banyak laporan; yang berbeda adalah mapping kolom, service shaping, Blade PDF, dan endpoint.

Checklist:

- [x] Fondasi XML upload/parse sudah tersedia.
- [x] Endpoint internal pertama sudah tersedia: `POST /api/internal/ascends/ru/hrm/list-karyawan/pdf`.
- [x] Blade PDF custom sudah memakai struktur tabel eksplisit yang mudah dicustom.
- [x] Contoh endpoint laporan baru tersedia: `POST /api/internal/ascends/ru/hrm/karyawan-per-masa-kerja/pdf`.
- [ ] Terima input request laporan baru: judul, screenshot layout lama, dan file XML contoh.
- [ ] Cek XML source: record tag, field yang tersedia, field kosong, dan field yang tidak ada.
- [ ] Tentukan slug endpoint baru, contoh: `/api/internal/ascends/ru/hrm/karyawan-per-masa-kerja/pdf`.
- [ ] Tambahkan mapping sub-report di `config/xml_reports/RU/hrm.php`.
- [ ] Buat service report baru untuk shaping data, sorting, grouping, format tanggal/angka/teks.
- [ ] Buat Blade PDF baru sesuai screenshot lama, tapi tetap memakai desain tabel open-api-report.
- [ ] Tambahkan controller/handler endpoint internal untuk laporan tersebut.
- [ ] Tambahkan dokumentasi endpoint di file ini.
- [ ] Tambahkan feature test: upload XML, raw XML, request kosong, dan struktur data utama.
- [ ] Smoke test dengan XML asli dari Ascend.
