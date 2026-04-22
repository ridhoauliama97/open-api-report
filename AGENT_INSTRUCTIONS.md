# Agent Instructions — `open-api-report`

> File ini adalah panduan teknis untuk AI coding agents (Claude Code, Cursor, Codex, Gemini, dll).
> Baca seluruh file ini sebelum membuat, mengubah, atau menghapus file apapun di repo ini.

---

## 1. Ringkasan Proyek

`open-api-report` adalah **Laravel 12 Report API Service** yang melayani ratusan jenis laporan produksi
untuk dua aplikasi desktop:

- **WPS** (Wood Processing System) — laporan kayu/timber
- **PPS** (Plastic Production System) — laporan produksi plastik

**Stack utama:**

| Komponen | Detail |
|---|---|
| PHP | 8.2 |
| Framework | Laravel 12 |
| PDF Engine | `mpdf/mpdf` ^8.2 via `PdfGenerator` service |
| Database | SQL Server (stored procedures) |
| Auth | Laravel Sanctum (token) + middleware JWT-claims kustom |
| Queue | `database` (dev) / `redis` (production) |

---

## 2. Struktur Direktori Penting

```
open-api-report/
├── app/
│   ├── Auth/                          # Custom user providers (legacy password)
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/
│   │   │   │   ├── AuthController.php       # Login, register, logout, refresh, me
│   │   │   │   ├── OpenApiController.php    # Auto-generate OpenAPI spec JSON
│   │   │   │   └── PdfJobController.php     # Async PDF: dispatch, status, download
│   │   │   ├── PPS/                         # Controller laporan khusus PPS
│   │   │   └── {NamaLaporan}Controller.php  # Controller per jenis laporan (WPS)
│   │   ├── Middleware/
│   │   │   ├── AuthenticateReportJwtClaims.php  # Middleware auth utama (WAJIB di semua report route)
│   │   │   ├── ForceInlinePdfPreview.php
│   │   │   ├── LogUserActivity.php
│   │   │   └── NormalizePdfDownloadFilename.php
│   │   └── Requests/
│   │       ├── BaseReportRequest.php          # Base class — semua FormRequest laporan extend ini
│   │       ├── PPS/                           # Request khusus PPS
│   │       └── Generate{NamaLaporan}ReportRequest.php
│   ├── Console/Commands/
│   │   ├── AuditReportApiCommand.php          # php artisan reports:audit-api
│   │   ├── AuditReportConventionsCommand.php  # php artisan reports:audit-conventions
│   │   ├── CleanExpiredPdfFiles.php           # php artisan pdf:clean-expired
│   │   └── ExportDatabaseStructureCommand.php # php artisan db:export-structure
│   ├── Contracts/
│   │   └── ReportDataInterface.php            # Interface kontrak untuk report service async
│   ├── Jobs/
│   │   └── GenerateReportPdfJob.php           # Background job generate PDF
│   ├── Models/
│   │   ├── ActivityLog.php
│   │   ├── PdfJobStatus.php           # Model tabel pdf_job_statuses
│   │   ├── PpsUser.php                # User PPS (tabel terpisah dari WPS)
│   │   └── User.php                   # User WPS
│   ├── Providers/
│   │   └── AppServiceProvider.php     # Boot auth providers + extend execution time laporan
│   ├── Services/
│   │   ├── PdfGenerator.php           # Satu-satunya class mPDF wrapper — SELALU gunakan ini
│   │   ├── PPS/                       # Service khusus PPS
│   │   └── {NamaLaporan}ReportService.php
│   └── Support/
├── config/
│   ├── app.php                        # Termasuk pdf_storage_disk, pdf_storage_path, pdf_retention_hours
│   ├── reports.php                    # Konfigurasi per laporan: SP name, DB connection, expected columns
│   └── queue.php
├── database/migrations/
├── routes/
│   ├── api.php                        # Semua route API (auth + 150+ report routes + 3 async routes)
│   ├── console.php                    # Schedule: pdf:clean-expired hourly
│   └── web.php
├── resources/views/reports/           # Blade template per laporan
├── async-pdf-generate-implementation.md
└── AGENT_INSTRUCTIONS.md              # File ini
```

---

## 3. Konvensi Penamaan (WAJIB DIIKUTI)

Setiap jenis laporan **selalu** terdiri dari 4 file dengan pola nama yang konsisten:

| File | Path | Pola Nama |
|---|---|---|
| Controller | `app/Http/Controllers/{NamaLaporan}Controller.php` | `{NamaLaporan}Controller` |
| Service | `app/Services/{NamaLaporan}ReportService.php` | `{NamaLaporan}ReportService` |
| Form Request | `app/Http/Requests/Generate{NamaLaporan}ReportRequest.php` | `Generate{NamaLaporan}ReportRequest` |
| Blade View | `resources/views/reports/{kategori}/{nama-laporan}.blade.php` | kebab-case |

Laporan dalam namespace **PPS** menggunakan subdirektori:
- `app/Http/Controllers/PPS/{NamaLaporan}Controller.php`
- `app/Services/PPS/{NamaLaporan}ReportService.php`
- `app/Http/Requests/PPS/Generate{NamaLaporan}ReportRequest.php`

---

## 4. Anatomi Controller Laporan (Pola Standar)

Setiap controller laporan memiliki **tepat 3 method publik**:

```php
class {NamaLaporan}Controller extends Controller
{
    // METHOD 1: preview() — return JSON data (untuk tabel di desktop app)
    public function preview(
        Generate{NamaLaporan}ReportRequest $request,
        {NamaLaporan}ReportService $service,
    ): JsonResponse { ... }

    // METHOD 2: download() — return PDF binary stream
    public function download(
        Generate{NamaLaporan}ReportRequest $request,
        {NamaLaporan}ReportService $service,
        PdfGenerator $pdfGenerator,
    ) { ... }

    // METHOD 3: health() — validasi struktur output stored procedure
    public function health(
        Generate{NamaLaporan}ReportRequest $request,
        {NamaLaporan}ReportService $service,
    ): JsonResponse { ... }
}
```

**Aturan penting untuk `download()`:**
- Selalu cek `$request->user() ?? auth('api')->user()` — kembalikan 401 jika null.
- PDF di-generate via `$pdfGenerator->render($view, $data)` — **jangan instantiate `Mpdf` langsung**.
- Response PDF: `response($pdfContent)->header('Content-Type', 'application/pdf')`.

**Aturan penting untuk `preview()`:**
- Return `JsonResponse` dengan key `data` berisi array rows.
- Sertakan `meta` key dengan `start_date`, `end_date`, `TglAwal`, `TglAkhir` untuk kompatibilitas.

---

## 5. Anatomi Service Laporan (Pola Standar)

```php
class {NamaLaporan}ReportService
{
    // Kolom yang diharapkan dari output SP — untuk health check
    private const EXPECTED_COLUMNS = ['Kolom1', 'Kolom2', ...];

    // Ambil data utama — memanggil stored procedure SQL Server
    public function fetch(string $startDate, string $endDate): array
    {
        return DB::select('EXEC SP_{NamaStoredProcedure} ?, ?', [$startDate, $endDate]);
    }

    // Opsional: sub-laporan jika ada
    public function fetchSubReport(string $startDate, string $endDate): array { ... }

    // Validasi struktur kolom output SP
    public function healthCheck(string $startDate, string $endDate): array
    {
        // Jalankan SP, compare kolom aktual dengan EXPECTED_COLUMNS
        // Return ['is_healthy' => bool, 'actual_columns' => [...], 'expected_columns' => [...]]
    }
}
```

**Aturan:**
- Selalu gunakan `DB::select()` dengan parameterized query — **jangan string interpolation**.
- Wrap dalam `try/catch`, lempar `RuntimeException` jika SP gagal.
- Konversi null ke nilai default (0 untuk numerik, `''` untuk string) sebelum return.
- Kolom numerik harus dicast ke `float` atau `int` — jangan biarkan sebagai string dari DB driver.
- Nama SP dan konfigurasi lainnya **sebaiknya** dibaca dari `config/reports.php`, bukan hardcode.

---

## 6. `config/reports.php` — Konfigurasi Per Laporan

File `config/reports.php` berisi konfigurasi tiap laporan yang dapat di-override via `.env`:

```php
// Contoh struktur satu entry di config/reports.php:
'mutasi_barang_jadi' => [
    'database_connection' => env('MUTASI_BARANG_JADI_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
    'stored_procedure'    => env('MUTASI_BARANG_JADI_REPORT_PROCEDURE', 'SP_Mutasi_BarangJadi'),
    'call_syntax'         => env('MUTASI_BARANG_JADI_REPORT_CALL_SYNTAX', 'exec'),
    'expected_columns'    => [...],
],
```

**Yang bisa dikonfigurasi per laporan via `.env`:**
- `{NAMA}_REPORT_DB_CONNECTION` — koneksi DB khusus (default: `DB_CONNECTION`)
- `{NAMA}_REPORT_PROCEDURE` — nama stored procedure (override jika nama SP berbeda di env lain)
- `{NAMA}_REPORT_CALL_SYNTAX` — `exec` atau `call`
- `{NAMA}_REPORT_EXPECTED_COLUMNS` — override kolom yang divalidasi di health check

Saat membuat laporan baru, **tambahkan entry di `config/reports.php`** agar SP-nya bisa dikonfigurasi tanpa mengubah kode.

---

## 7. Anatomi Form Request (Pola Standar)

Semua Form Request **extend `BaseReportRequest`** (bukan `FormRequest` langsung):

```php
class Generate{NamaLaporan}ReportRequest extends BaseReportRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            // Support dual format: snake_case (API baru) + PascalCase (legacy desktop app)
            'start_date' => ['nullable', 'date', 'required_without:TglAwal'],
            'end_date'   => ['nullable', 'date', 'required_without:TglAkhir'],
            'TglAwal'    => ['nullable', 'date', 'required_without:start_date'],
            'TglAkhir'   => ['nullable', 'date', 'required_without:end_date'],
        ];
    }

    // Tambahkan withValidator() untuk validasi end_date >= start_date
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            $start = $this->input('start_date', $this->input('TglAwal'));
            $end   = $this->input('end_date',   $this->input('TglAkhir'));
            if ($start && $end && strtotime((string)$end) < strtotime((string)$start)) {
                $v->errors()->add('end_date', 'Tanggal akhir harus sama atau setelah tanggal awal.');
            }
        });
    }
}
```

**Penting:** `BaseReportRequest::failedValidation()` otomatis return JSON 422 untuk request `api/*`.
Jangan override `failedValidation()` di child class kecuali ada kebutuhan khusus.

---

## 8. Routing — Pola Pendaftaran Route

Semua report route didaftarkan dengan helper closure `$registerReportRoutes` di `routes/api.php`:

```php
// Setiap laporan menghasilkan 3 route:
$registerReportRoutes($path, $namePrefix, $controller);
// → POST     /api{$path}          → $controller@preview
// → GET|POST /api{$path}/pdf      → $controller@download
// → POST     /api{$path}/health   → $controller@health
```

**3 route async** juga ada di dalam middleware group yang sama:
```php
Route::get('/reports/jobs/{jobId}/status', ...)   // cek status job
Route::get('/reports/jobs/{jobId}/download', ...) // download PDF
Route::post('/reports/{reportPath}/pdf/async', ...) // dispatch job — wildcard path
```

Semua route laporan berada di dalam `Route::middleware('report.jwt.claims')->group(...)`.

**Menambah laporan baru:** tambahkan satu entry ke array yang sesuai di `routes/api.php`:
```php
['/reports/{path-kebab-case}', 'api.reports.{name.dotted}', {Controller}::class],
```

---

## 9. Autentikasi

- Middleware utama: `report.jwt.claims` → `App\Http\Middleware\AuthenticateReportJwtClaims`
- Mendukung dua mode: JWT eksternal (dari WPS/PPS desktop) dan Sanctum personal access token
- Dua user model: `App\Models\User` (WPS) dan `App\Models\PpsUser` (PPS)
- Di controller: **selalu** gunakan `$request->user() ?? auth('api')->user()`

---

## 10. `PdfGenerator` Service — Cara Penggunaan

`app/Services/PdfGenerator.php` adalah **satu-satunya wrapper mPDF**.
**Jangan instantiate `Mpdf` langsung di controller atau service apapun.**

```php
use App\Services\PdfGenerator;

// Render ke string (untuk HTTP response langsung)
$pdfContent = $pdfGenerator->render('reports.mutasi.barang-jadi', [
    'data'    => $rows,
    'subData' => $subRows,
    'title'   => 'Laporan Mutasi Barang Jadi',

    // Opsi PDF (semua opsional):
    'pdf_orientation'             => 'landscape',  // 'portrait' | 'landscape' | auto-detect
    'pdf_format'                  => 'A4',         // A4, A3, A2, A1, A0, LETTER, LEGAL
    'pdf_simple_tables'           => true,
    'pdf_default_font'            => 'Noto Serif',
    'pdf_shrink_tables_to_fit'    => 1,
    'pdf_column_count'            => 15,           // override auto-detect
    'pdf_disable_chunking'        => false,        // true hanya untuk laporan sangat kecil
    'pdf_disable_auto_page_break' => false,
]);

// Render langsung ke file (DIREKOMENDASIKAN untuk async job — hemat memory)
$pdfGenerator->renderToFile('reports.mutasi.barang-jadi', $data, '/absolute/path/output.pdf');
```

---

## 11. Async PDF — Arsitektur yang Sudah Diimplementasikan

### Cara Kerja Job (`GenerateReportPdfJob`)

Job **tidak** menginstantiate service secara manual. Ia menggunakan pendekatan yang lebih cerdas:
1. Dari `reportType` (slug path), cari route `{type}/pdf` yang sudah ada di router Laravel
2. Bangun *synthetic HTTP request* dengan payload yang sama
3. Panggil `$controller->download($request)` via `app()->call()`
4. Ambil binary PDF dari response, simpan ke storage
5. Update status di `pdf_job_statuses`

Keuntungan: tidak ada duplikasi logika — semua reuse controller `download()` yang sudah ada.

### Konfigurasi `.env` untuk Async

```dotenv
# Storage PDF hasil generate
PDF_STORAGE_DISK=local          # disk di config/filesystems.php
PDF_STORAGE_PATH=pdf_reports    # subfolder di dalam disk
PDF_RETENTION_HOURS=24          # jam sebelum file dihapus oleh pdf:clean-expired
PDF_FAILED_JOB_RETENTION_HOURS=72

# Queue — ganti ke "redis" saat production
QUEUE_CONNECTION=database
```

> **PERHATIAN:** Jangan pakai key `REPORT_PDF_JOB_RETENTION_HOURS` — key ini tidak dibaca oleh kode.
> Key yang benar adalah `PDF_RETENTION_HOURS` (dibaca oleh `config/app.php` → `pdf_retention_hours`).

### Endpoint Async

| Method | URL | Response |
|---|---|---|
| `POST` | `/api/reports/{path}/pdf/async` | `{ job_id, status: "queued" }` — HTTP 202 |
| `GET` | `/api/reports/jobs/{jobId}/status` | `{ status, download_url? }` |
| `GET` | `/api/reports/jobs/{jobId}/download` | Stream file PDF |

### Skema Tabel `pdf_job_statuses`

```
job_id           uuid PRIMARY KEY
report_type      string              — contoh: 'mutasi-barang-jadi'
status           string              — queued | processing | done | failed
file_path        string nullable     — path file PDF di storage
error_message    text nullable
request_payload  json                — parameter request asli
requested_by     string nullable     — username dari JWT
expires_at       timestamp nullable  — waktu file dihapus otomatis
timestamps
```

---

## 12. Artisan Commands Tersedia

| Command | Fungsi |
|---|---|
| `reports:audit-conventions` | Validasi kepatuhan kode terhadap instruksi ini (BaseReportRequest, no `new Mpdf`, middleware, method controller) |
| `reports:audit-api` | Bandingkan route terdaftar vs OpenAPI spec, audit triplet preview/pdf/health |
| `pdf:clean-expired` | Hapus file PDF async yang sudah kadaluarsa (dijadwalkan `hourly` di `routes/console.php`) |
| `db:export-structure {connection}` | Export skema SQL Server (tabel, SP + parameter, FK) ke JSON + README |

---

## 13. Cara Menambah Laporan Baru (Checklist)

Kerjakan berurutan:

- [ ] **1. `config/reports.php`** — tambah entry konfigurasi SP dan expected columns
- [ ] **2. Form Request** — `app/Http/Requests/Generate{Nama}ReportRequest.php`
  - Extend `BaseReportRequest`, bukan `FormRequest`
  - Dual-format rules: snake_case + PascalCase
  - `withValidator()` untuk validasi urutan tanggal
- [ ] **3. Service** — `app/Services/{Nama}ReportService.php`
  - `EXPECTED_COLUMNS` constant
  - `fetch()`, `healthCheck()`, `fetchSubReport()` jika perlu
  - Gunakan `DB::select('EXEC SP_... ?, ?', [...])` — jangan string interpolation
- [ ] **4. Blade View** — `resources/views/reports/{kategori}/{nama}.blade.php`
- [ ] **5. Controller** — `app/Http/Controllers/{Nama}Controller.php`
  - `preview()`, `download()`, `health()`
  - Inject `PdfGenerator` di `download()` — jangan `new Mpdf()`
- [ ] **6. Route** — tambah entry di `routes/api.php` di array kategori yang tepat
- [ ] **7. Verifikasi** — jalankan `php artisan reports:audit-conventions` dan `reports:audit-api`

---

## 14. Hal yang DILARANG

- **DILARANG** instantiate `new Mpdf(...)` langsung — selalu lewat `PdfGenerator`.
- **DILARANG** hapus atau ubah endpoint `/pdf` sinkronus yang sudah ada.
- **DILARANG** string interpolation di query SQL — selalu parameterized binding.
- **DILARANG** extend `FormRequest` langsung — selalu extend `BaseReportRequest`.
- **DILARANG** override `failedValidation()` di Form Request child class.
- **DILARANG** buat method controller selain `preview`, `download`, `health` tanpa alasan yang jelas.
- **DILARANG** simpan file PDF hasil generate di `public/` — gunakan `Storage::disk(...)`.
- **DILARANG** pakai key `REPORT_PDF_JOB_RETENTION_HOURS` di `.env` — key yang benar adalah `PDF_RETENTION_HOURS`.

---

## 15. Perintah Berguna

```bash
# Setup awal
composer install && php artisan key:generate && php artisan migrate

# Jalankan dev server + queue worker + logger + vite bersamaan
composer dev

# Jalankan hanya queue worker (untuk testing async)
php artisan queue:listen --tries=1 --timeout=0

# Audit kode terhadap konvensi instruksi ini
php artisan reports:audit-conventions
php artisan reports:audit-api

# Cek laporan yang gagal
php artisan queue:failed

# Retry semua job yang gagal
php artisan queue:retry all

# Cleanup PDF kadaluarsa
php artisan pdf:clean-expired

# Export skema database SQL Server ke JSON
php artisan db:export-structure sqlsrv
php artisan db:export-structure sqlsrv_pps

# Format kode dengan Laravel Pint
./vendor/bin/pint

# Jalankan test suite
composer test
```

---

## 16. Referensi File Kunci

| Kebutuhan | File |
|---|---|
| Lihat semua jenis laporan yang ada | `routes/api.php` |
| Konfigurasi SP per laporan | `config/reports.php` |
| Konfigurasi storage & retention PDF | `config/app.php` + `.env` |
| Contoh controller lengkap | `app/Http/Controllers/MutasiBarangJadiController.php` |
| Contoh service lengkap | `app/Services/MutasiBarangJadiReportService.php` |
| Contoh form request | `app/Http/Requests/GenerateMutasiBarangJadiReportRequest.php` |
| PDF generator (mPDF wrapper) | `app/Services/PdfGenerator.php` |
| Async job implementation | `app/Jobs/GenerateReportPdfJob.php` |
| Auth middleware | `app/Http/Middleware/AuthenticateReportJwtClaims.php` |
| Bootstrap & extend execution time | `app/Providers/AppServiceProvider.php` |
| Konfigurasi environment | `.env.example` |
