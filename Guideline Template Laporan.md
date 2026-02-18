# Guideline Membuat Laporan Baru (Acuan: Mutasi Barang Jadi)

## Tujuan
Panduan ini dipakai untuk menambah laporan baru dengan pola arsitektur yang sama seperti modul Mutasi Barang Jadi:
- Web form + download PDF
- API preview + download PDF (+ health check opsional)
- Data source dari stored procedure / query
- Proteksi API report via JWT claim policy

## Standar Penamaan
Contoh jika laporan baru bernama `stok-harian`:
- Controller: `StokHarianController`
- Request: `GenerateStokHarianReportRequest`
- Service: `StokHarianReportService`
- View form: `resources/views/reports/stok/harian-form.blade.php`
- View PDF: `resources/views/reports/stok/harian-pdf.blade.php`
- Route name web: `reports.stok.harian.*`
- Route name api: `api.reports.stok-harian.*`
- Config key: `reports.stok_harian.*`

## Langkah Implementasi
1. Buat service laporan baru
- Duplikasi pola dari `app/Services/MutasiBarangJadiReportService.php`.
- Tambahkan:
  - `fetch(...)` untuk data utama
  - `fetchSubReport(...)` jika perlu tabel tambahan
  - `healthCheck(...)` jika perlu validasi struktur output SP
- Validasi:
  - nama stored procedure aman (`preg_match`)
  - output kolom sesuai ekspektasi
  - normalisasi data `object -> array`

2. Buat request validator
- Buat file `app/Http/Requests/Generate<NamaLaporan>ReportRequest.php`.
- Minimum validasi tanggal:
  - `start_date/end_date` atau format legacy (`TglAwal/TglAkhir`)
  - `end_date >= start_date`

3. Buat controller
- Buat file `app/Http/Controllers/<NamaLaporan>Controller.php`.
- Method minimum:
  - `index()` untuk halaman form
  - `preview()` untuk JSON preview
  - `download()` untuk PDF
- Method opsional:
  - `health()` untuk cek struktur hasil query/SP
- Gunakan `PdfGenerator` untuk render HTML Blade menjadi PDF.

4. Tambahkan view
- Form web: input tanggal + tombol preview/download.
- Template PDF:
  - Header judul laporan
  - Info periode
  - Info footer `Dicetak oleh` + timestamp
  - Tabel data utama (+ sub tabel bila ada)
- Referensi pola: `resources/views/reports/mutasi/barang-jadi-form.blade.php` dan `resources/views/reports/mutasi/barang-jadi-pdf.blade.php`.

5. Daftarkan route
- Web di `routes/web.php`:
  - `GET /reports/...` -> `index`
  - `POST /reports/.../preview` -> `preview`
  - `POST /reports/.../download` -> `download`
- API di `routes/api.php`:
  - `POST /api/reports/...` -> `preview`
  - `GET|POST /api/reports/.../pdf` -> `download`
  - `POST /api/reports/.../health` -> `health` (opsional)
- Route API report wajib di group middleware `report.jwt.claims`.

6. Tambahkan konfigurasi report
- Tambahkan key baru di `config/reports.php`.
- Minimum key:
  - `database_connection`
  - `stored_procedure`
  - `call_syntax`
  - `query` (untuk fallback mode query)

7. Update OpenAPI
- Tambahkan endpoint baru di `app/Http/Controllers/Api/OpenApiController.php`:
  - path
  - requestBody
  - response schema
  - security bearer

8. Tambahkan test feature
- Buat/extend test di `tests/Feature/`.
- Minimum skenario:
  - form web bisa diakses
  - preview API sukses
  - download PDF sukses
  - unauthorized token ditolak
- Jika ada health endpoint:
  - assert struktur response `health.*`

## Checklist Merge
- `php artisan route:list` memastikan route terdaftar.
- `php artisan test` harus hijau.
- OpenAPI `GET /api/openapi.json` sudah memuat endpoint baru.
- Footer PDF menampilkan user dari token/session tanpa error.
- README diperbarui jika ada endpoint/config baru.

## Catatan Integrasi JWT
- Report API tidak bergantung login user DB lokal.
- Pastikan token issuer punya claim yang dipakai report:
  - `sub`, `name`, `email`
  - `scope` jika `REPORT_JWT_REQUIRED_SCOPE` diaktifkan
  - `iss` dan `aud` jika whitelist diaktifkan
- Referensi lengkap ada di `docs/jwt-cross-app-integration.md`.
