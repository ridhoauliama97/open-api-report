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
| Queue | `database` (default) — migrasi ke `redis` saat async diaktifkan |

---

## 2. Struktur Direktori Penting

```
open-api-report/
├── app/
│   ├── Auth/                          # Custom user providers (legacy password)
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/
│   │   │   │   ├── AuthController.php     # Login, register, logout, refresh, me
│   │   │   │   └── OpenApiController.php  # Auto-generate OpenAPI spec JSON
│   │   │   ├── PPS/                       # Controller laporan khusus PPS
│   │   │   └── {NamaLaporan}Controller.php  # Controller per jenis laporan (WPS)
│   │   ├── Middleware/
│   │   │   ├── AuthenticateReportJwtClaims.php  # Middleware auth utama (WAJIB di semua report route)
│   │   │   ├── ForceInlinePdfPreview.php
│   │   │   ├── LogUserActivity.php
│   │   │   └── NormalizePdfDownloadFilename.php
│   │   └── Requests/
│   │       ├── BaseReportRequest.php          # Base class — semua FormRequest laporan extend ini
│   │       ├── PPS/                           # Request khusus PPS
│   │       └── Generate{NamaLaporan}ReportRequest.php  # Satu per jenis laporan
│   ├── Jobs/                          # (Kosong saat ini) — target implementasi async
│   ├── Models/
│   │   ├── ActivityLog.php
│   │   ├── PpsUser.php                # User PPS (tabel terpisah dari WPS)
│   │   └── User.php                  # User WPS
│   ├── Providers/
│   │   └── AppServiceProvider.php    # Boot auth providers + extend execution time laporan
│   ├── Services/
│   │   ├── PdfGenerator.php          # Satu-satunya class mPDF wrapper — SELALU gunakan ini
│   │   ├── PPS/                      # Service khusus PPS
│   │   └── {NamaLaporan}ReportService.php  # Service per jenis laporan
│   └── Support/
├── database/migrations/              # SQLite untuk auth/session; SQL Server via DB facade
├── routes/
│   ├── api.php                       # Semua route API (auth + 150+ report routes)
│   └── web.php                       # Route web (form preview)
├── resources/views/reports/          # Blade template untuk tiap laporan
├── async-pdf-generate-implementation.md  # Dokumen rencana implementasi async (SUDAH ADA)
└── AGENT_INSTRUCTIONS.md             # File ini
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

---

## 6. Anatomi Form Request (Pola Standar)

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

    // Tambahkan withValidator() jika perlu validasi end_date >= start_date
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

## 7. Routing — Pola Pendaftaran Route

Semua report route didaftarkan dengan helper closure `$registerReportRoutes` di `routes/api.php`:

```php
// Setiap laporan menghasilkan 3 route:
$registerReportRoutes($path, $namePrefix, $controller);
// → POST     /api{$path}          → $controller@preview
// → GET|POST /api{$path}/pdf      → $controller@download
// → POST     /api{$path}/health   → $controller@health
```

Semua route laporan berada di dalam `Route::middleware('report.jwt.claims')->group(...)`.

**Menambah laporan baru:** tambahkan satu entry ke array yang sesuai di `routes/api.php`:

```php
// Pilih array yang paling relevan secara kategori:
$mutasiReportRouteDefinitions       // laporan mutasi stok
$kayuBulatReportRouteDefinitions    // laporan kayu bulat
$sawnTimberReportRouteDefinitions   // laporan sawn timber / kayu gergajian
$standaloneReportRouteDefinitions   // laporan lain-lain / management / PPS

// Format entry:
['/reports/{path-kebab-case}', 'api.reports.{name.dotted}', {Controller}::class],
```

---

## 8. Autentikasi

- Middleware utama: `report.jwt.claims` → `App\Http\Middleware\AuthenticateReportJwtClaims`
- Mendukung dua mode auth:
  1. **JWT eksternal** — token dari aplikasi WPS/PPS desktop
  2. **Sanctum personal access token** — untuk alur first-party / testing
- Dua user model: `App\Models\User` (WPS) dan `App\Models\PpsUser` (PPS)
- Di controller: **selalu** gunakan `$request->user() ?? auth('api')->user()`

---

## 9. `PdfGenerator` Service — Cara Penggunaan

`app/Services/PdfGenerator.php` adalah **satu-satunya wrapper mPDF**.
**Jangan instantiate `Mpdf` langsung di controller atau service apapun.**

```php
use App\Services\PdfGenerator;

// Render ke string (untuk HTTP response langsung)
$pdfContent = $pdfGenerator->render('reports.mutasi.barang-jadi', [
    'data'    => $rows,
    'subData' => $subRows,
    'title'   => 'Laporan Mutasi Barang Jadi',

    // Opsi PDF (semua opsional — ada auto-detect):
    'pdf_orientation'          => 'landscape',  // 'portrait' | 'landscape' | auto
    'pdf_format'               => 'A4',         // A4, A3, A2, A1, A0, LETTER, LEGAL
    'pdf_simple_tables'        => true,
    'pdf_default_font'         => 'Noto Serif',
    'pdf_shrink_tables_to_fit' => 1,
    'pdf_column_count'         => 15,           // override auto-detect kolom
    'pdf_disable_chunking'     => false,        // true hanya untuk laporan sangat kecil
    'pdf_disable_auto_page_break' => false,
]);

// Render langsung ke file (DIREKOMENDASIKAN untuk async job — hemat memory)
$pdfGenerator->renderToFile('reports.mutasi.barang-jadi', $data, '/absolute/path/output.pdf');
```

`PdfGenerator` secara otomatis:
- Mendeteksi orientasi berdasarkan jumlah kolom (> 10 → landscape)
- Menulis HTML dalam chunks 500 KB untuk mencegah error `pcre.backtrack_limit`
- Menghapus Google Fonts `<link>` (memperlambat render mPDF)
- Sanitasi UTF-8 dan strip BOM

---

## 10. Masalah Performa Saat Ini & Rencana Solusi

### Masalah Utama

Request `POST /api/reports/{nama}/pdf` berjalan **sinkronus**:

```
Desktop App → POST /api/reports/.../pdf → Laravel → EXEC SP_... → 10k-50k rows
→ mPDF render → memory/timeout → GAGAL
```

### Solusi: Async Queue

File `async-pdf-generate-implementation.md` berisi **blueprint lengkap** implementasi async.
**Baca file tersebut sebelum mengerjakan fitur async.**

### Ringkasan File yang Perlu Dibuat

```
# File BARU:
app/Contracts/ReportDataInterface.php
app/Models/PdfJobStatus.php
app/Jobs/GenerateReportPdfJob.php
app/Http/Controllers/Api/PdfJobController.php
app/Console/Commands/CleanExpiredPdfFiles.php        ← opsional
database/migrations/xxxx_create_pdf_job_statuses_table.php

# File DIMODIFIKASI:
routes/api.php          ← tambah 3 route baru di dalam middleware group yang sudah ada
.env                    ← tambah QUEUE_CONNECTION=redis dan PDF_STORAGE_* vars
```

### Endpoint Async yang Akan Dibuat

| Method | URL | Response |
|---|---|---|
| `POST` | `/api/reports/{reportType}/pdf/async` | `{ job_id, status: "queued" }` — HTTP 202 |
| `GET` | `/api/reports/jobs/{jobId}/status` | `{ status: "queued/processing/done/failed" }` |
| `GET` | `/api/reports/jobs/{jobId}/download` | Stream file PDF |

### Skema Tabel `pdf_job_statuses`

```
job_id           uuid PRIMARY KEY
report_type      string              — contoh: 'mutasi-barang-jadi'
status           string              — queued | processing | done | failed
file_path        string nullable     — path file PDF di storage
error_message    text nullable
request_payload  json                — semua parameter request asli (TglAwal, TglAkhir, dll)
requested_by     string nullable     — username dari JWT
expires_at       timestamp nullable  — waktu file dihapus otomatis (default: +24 jam)
timestamps
```

### Pola Polling dari Desktop App

```
1. POST /api/reports/{type}/pdf/async  → dapat job_id
2. Loop: GET /api/reports/jobs/{id}/status tiap 3-5 detik
   - status = "done"   → tampilkan tombol Download
   - status = "failed" → tampilkan pesan error
3. GET /api/reports/jobs/{id}/download → buka/simpan PDF
```

---

## 11. Cara Menambah Laporan Baru (Checklist)

Kerjakan berurutan:

- [ ] **1. Form Request** — `app/Http/Requests/Generate{Nama}ReportRequest.php`
  - Extend `BaseReportRequest`
  - Definisikan `rules()` dengan dual-format (snake_case + PascalCase)
  - Tambahkan `withValidator()` untuk validasi urutan tanggal

- [ ] **2. Service** — `app/Services/{Nama}ReportService.php`
  - Definisikan `EXPECTED_COLUMNS` constant
  - Implement `fetch()`, `healthCheck()`, `fetchSubReport()` (jika ada sub-laporan)
  - Gunakan `DB::select('EXEC SP_... ?, ?', [...])` — jangan string interpolation

- [ ] **3. Blade View** — `resources/views/reports/{kategori}/{nama}.blade.php`
  - Lihat view yang sudah ada sebagai referensi struktur HTML dan CSS

- [ ] **4. Controller** — `app/Http/Controllers/{Nama}Controller.php`
  - Implement `preview()`, `download()`, `health()`
  - Inject `PdfGenerator` dan `{Nama}ReportService` via method injection
  - Gunakan `$pdfGenerator->render(...)` — **jangan `new Mpdf()`**

- [ ] **5. Route** — tambah 1 entry di `routes/api.php`
  - Pilih array kategori yang tepat
  - Format: `['/reports/{path}', 'api.reports.{name}', {Controller}::class]`

- [ ] **6. (Jika async sudah live)** tambah mapping di `PdfJobController::getReportConfig()`:
  ```php
  '{slug}' => ['service' => {Nama}ReportService::class, 'view' => 'reports.{kat}.{nama}'],
  ```

---

## 12. Hal yang DILARANG

- **DILARANG** instantiate `new Mpdf(...)` langsung — selalu lewat `PdfGenerator`.
- **DILARANG** hapus atau ubah endpoint `/pdf` sinkronus yang sudah ada — endpoint async adalah **tambahan**, bukan pengganti.
- **DILARANG** string interpolation di query SQL — selalu parameterized binding.
- **DILARANG** extend `FormRequest` langsung — selalu extend `BaseReportRequest`.
- **DILARANG** override `failedValidation()` di Form Request child class kecuali ada alasan spesifik.
- **DILARANG** buat method controller selain `preview`, `download`, `health` tanpa alasan yang jelas.
- **DILARANG** simpan file PDF hasil generate di dalam `public/` — gunakan `Storage::disk(...)`.
- **DILARANG** jalankan `DB::select()` tanpa parameterized binding untuk input dari user.

---

## 13. Perintah Berguna

```bash
# Setup awal
composer install && php artisan key:generate && php artisan migrate

# Jalankan dev server + queue worker + logger + vite bersamaan
composer dev

# Jalankan hanya queue worker (untuk testing async)
php artisan queue:listen --tries=1 --timeout=0

# Cek laporan yang gagal
php artisan queue:failed

# Retry semua job yang gagal
php artisan queue:retry all

# Cleanup PDF kadaluarsa (setelah command dibuat)
php artisan pdf:clean-expired

# Format kode dengan Laravel Pint
./vendor/bin/pint

# Jalankan test suite
composer test
```

---

## 14. Referensi File Kunci

| Kebutuhan | File |
|---|---|
| Lihat semua jenis laporan yang ada | `routes/api.php` |
| Contoh controller lengkap | `app/Http/Controllers/MutasiBarangJadiController.php` |
| Contoh service lengkap | `app/Services/MutasiBarangJadiReportService.php` |
| Contoh form request | `app/Http/Requests/GenerateMutasiBarangJadiReportRequest.php` |
| PDF generator (mPDF wrapper) | `app/Services/PdfGenerator.php` |
| Auth middleware | `app/Http/Middleware/AuthenticateReportJwtClaims.php` |
| Bootstrap & extend execution time | `app/Providers/AppServiceProvider.php` |
| Blueprint implementasi async | `async-pdf-generate-implementation.md` |
| Konfigurasi environment | `.env.example` |
