# Implementasi Async PDF Generation — `open-api-report`

**Target Repo:** `https://github.com/ridhoauliama97/open-api-report`  
**Stack:** PHP 8.2, Laravel, mPDF, SQL Server (stored procedures), JWT Auth  
**Tujuan:** Mengubah PDF generation dari synchronous (user nunggu) menjadi async (background queue + polling)

---

## Prasyarat (Wajib Dipenuhi Sebelum Mulai)

| Syarat | Perintah Cek |
|---|---|
| Redis berjalan | `redis-cli ping` → harus balas `PONG` |
| PHP extension `redis` aktif | `php -m \| grep redis` |
| Laravel Queue bisa jalan | `php artisan queue:work --once` (tanpa error) |
| Composer sudah install | `composer install` |

Jika Redis belum ada, install via Docker:
```bash
docker run -d --name redis -p 6379:6379 redis:alpine
```

---

## Gambaran Arsitektur Akhir

```
[CLIENT]
   │
   ├─ POST /api/reports/{nama}/pdf/async
   │       └─ Response: { job_id: "uuid-xxx", status: "queued" }  ← CEPAT (< 100ms)
   │
   ├─ GET /api/reports/jobs/{job_id}/status
   │       └─ Response: { status: "processing" | "done" | "failed", download_url: "..." }
   │
   └─ GET /api/reports/jobs/{job_id}/download
           └─ Response: file PDF (stream)

[SERVER - BACKGROUND]
   Queue Worker → ambil job → panggil stored procedure
               → chunk data → generate PDF mPDF
               → simpan file → update status Redis
```

---

## FASE 1 — Setup Infrastruktur (Redis + Queue)

### Langkah 1.1 — Install Predis (Redis Client untuk PHP)

```bash
composer require predis/predis
```

### Langkah 1.2 — Konfigurasi `.env`

Tambahkan baris berikut ke file `.env`:

```dotenv
# Queue menggunakan Redis
QUEUE_CONNECTION=redis

# Konfigurasi Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Konfigurasi penyimpanan PDF hasil generate
PDF_STORAGE_DISK=local
PDF_STORAGE_PATH=pdf_reports
PDF_RETENTION_HOURS=24
```

### Langkah 1.3 — Verifikasi `config/queue.php`

Pastikan bagian `redis` sudah ada dan terlihat seperti ini:

```php
// config/queue.php
'redis' => [
    'driver'      => 'redis',
    'connection'  => 'default',
    'queue'       => env('REDIS_QUEUE', 'default'),
    'retry_after' => 300,     // detik sebelum job dianggap gagal
    'block_for'   => null,
],
```

### Langkah 1.4 — Buat Migration untuk Tabel Job Status

```bash
php artisan make:migration create_pdf_job_statuses_table
```

Isi file migration yang baru dibuat (`database/migrations/xxxx_create_pdf_job_statuses_table.php`):

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pdf_job_statuses', function (Blueprint $table) {
            $table->uuid('job_id')->primary();
            $table->string('report_type');              // contoh: 'mutasi-barang-jadi'
            $table->string('status');                   // queued | processing | done | failed
            $table->string('file_path')->nullable();    // path file PDF jika sudah selesai
            $table->text('error_message')->nullable();  // pesan error jika gagal
            $table->json('request_payload');            // parameter request asli (TglAwal, TglAkhir, dst)
            $table->string('requested_by')->nullable(); // username dari JWT
            $table->timestamp('expires_at')->nullable();// kapan file PDF dihapus otomatis
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pdf_job_statuses');
    }
};
```

Jalankan migration:
```bash
php artisan migrate
```

---

## FASE 2 — Model & Service

### Langkah 2.1 — Buat Model `PdfJobStatus`

Buat file `app/Models/PdfJobStatus.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PdfJobStatus extends Model
{
    protected $primaryKey = 'job_id';
    public    $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'job_id',
        'report_type',
        'status',
        'file_path',
        'error_message',
        'request_payload',
        'requested_by',
        'expires_at',
    ];

    protected $casts = [
        'request_payload' => 'array',
        'expires_at'      => 'datetime',
    ];

    // Status constants — gunakan ini, jangan hardcode string
    const STATUS_QUEUED     = 'queued';
    const STATUS_PROCESSING = 'processing';
    const STATUS_DONE       = 'done';
    const STATUS_FAILED     = 'failed';

    // Helper: buat job baru dengan UUID otomatis
    public static function createJob(string $reportType, array $payload, ?string $requestedBy = null): self
    {
        return self::create([
            'job_id'          => Str::uuid()->toString(),
            'report_type'     => $reportType,
            'status'          => self::STATUS_QUEUED,
            'request_payload' => $payload,
            'requested_by'    => $requestedBy,
            'expires_at'      => now()->addHours(
                (int) config('app.pdf_retention_hours', 24)
            ),
        ]);
    }
}
```

### Langkah 2.2 — Buat Interface `ReportDataInterface`

> **Catatan untuk AI Agent:** Interface ini memastikan setiap jenis laporan punya kontrak yang sama. Semua report service yang sudah ada harus di-implement sesuai interface ini.

Buat file `app/Contracts/ReportDataInterface.php`:

```php
<?php

namespace App\Contracts;

interface ReportDataInterface
{
    /**
     * Ambil data utama laporan dari stored procedure.
     * Harus mengembalikan array of arrays (rows).
     *
     * @param  array $params  Parameter dari request (TglAwal, TglAkhir, dll)
     * @return array
     */
    public function fetchData(array $params): array;

    /**
     * Ambil data sub-laporan jika ada.
     * Kembalikan array kosong [] jika tidak ada sub-laporan.
     *
     * @param  array $params
     * @return array
     */
    public function fetchSubData(array $params): array;

    /**
     * Nama tampilan laporan (untuk header PDF).
     *
     * @return string  Contoh: "Laporan Mutasi Barang Jadi"
     */
    public function getReportTitle(): string;
}
```

---

## FASE 3 — Buat Job Class

### Langkah 3.1 — Generate Job Class

```bash
php artisan make:job GenerateReportPdfJob
```

### Langkah 3.2 — Isi `GenerateReportPdfJob`

Buka `app/Jobs/GenerateReportPdfJob.php` dan ganti seluruh isinya dengan:

```php
<?php

namespace App\Jobs;

use App\Contracts\ReportDataInterface;
use App\Models\PdfJobStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Mpdf\Mpdf;
use Throwable;

class GenerateReportPdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Maksimal percobaan ulang jika job gagal
    public int $tries = 3;

    // Timeout dalam detik (5 menit)
    public int $timeout = 300;

    public function __construct(
        private readonly string $jobId,
        private readonly string $reportType,
        private readonly string $reportServiceClass,   // FQCN class service laporan
        private readonly array  $requestPayload,
        private readonly string $bladeView,            // nama view blade untuk template PDF
    ) {}

    public function handle(): void
    {
        // 1. Update status → processing
        $jobStatus = PdfJobStatus::find($this->jobId);

        if (! $jobStatus) {
            Log::error("[PDF Job] Job ID tidak ditemukan: {$this->jobId}");
            return;
        }

        $jobStatus->update(['status' => PdfJobStatus::STATUS_PROCESSING]);

        try {
            // 2. Resolve service laporan via IoC container
            /** @var ReportDataInterface $service */
            $service = app($this->reportServiceClass);

            // 3. Ambil data dari stored procedure
            $data    = $service->fetchData($this->requestPayload);
            $subData = $service->fetchSubData($this->requestPayload);
            $title   = $service->getReportTitle();

            // 4. Render HTML dari Blade view
            $html = view($this->bladeView, [
                'data'    => $data,
                'subData' => $subData,
                'title'   => $title,
                'params'  => $this->requestPayload,
            ])->render();

            // 5. Generate PDF dengan mPDF
            $mpdf = new Mpdf([
                'mode'        => 'utf-8',
                'format'      => 'A4-L',  // Landscape, sesuaikan per report jika perlu
                'orientation' => 'L',
                'margin_top'  => 10,
                'margin_left' => 10,
                'margin_bottom' => 10,
                'margin_right'=> 10,
            ]);

            $mpdf->WriteHTML($html);
            $pdfContent = $mpdf->Output('', 'S'); // 'S' = return sebagai string

            // 6. Simpan file PDF ke storage
            $filename = sprintf(
                '%s_%s_%s.pdf',
                $this->reportType,
                now()->format('Ymd_His'),
                substr($this->jobId, 0, 8)  // 8 karakter pertama UUID untuk identifikasi
            );

            $storagePath = config('app.pdf_storage_path', 'pdf_reports') . '/' . $filename;
            Storage::disk(config('app.pdf_storage_disk', 'local'))->put($storagePath, $pdfContent);

            // 7. Update status → done
            $jobStatus->update([
                'status'    => PdfJobStatus::STATUS_DONE,
                'file_path' => $storagePath,
            ]);

            Log::info("[PDF Job] Selesai: {$this->jobId} → {$storagePath}");

        } catch (Throwable $e) {
            // 8. Jika error, update status → failed
            $jobStatus->update([
                'status'        => PdfJobStatus::STATUS_FAILED,
                'error_message' => $e->getMessage(),
            ]);

            Log::error("[PDF Job] Gagal: {$this->jobId} — {$e->getMessage()}");

            // Re-throw agar Laravel Queue bisa mencatat kegagalan
            throw $e;
        }
    }

    // Dipanggil otomatis oleh Laravel jika semua percobaan habis
    public function failed(Throwable $exception): void
    {
        PdfJobStatus::where('job_id', $this->jobId)->update([
            'status'        => PdfJobStatus::STATUS_FAILED,
            'error_message' => 'Job gagal setelah ' . $this->tries . ' percobaan: ' . $exception->getMessage(),
        ]);
    }
}
```

---

## FASE 4 — Controller Endpoints Baru

### Langkah 4.1 — Buat `PdfJobController`

```bash
php artisan make:controller Api/PdfJobController
```

Isi `app/Http/Controllers/Api/PdfJobController.php`:

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateReportPdfJob;
use App\Models\PdfJobStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class PdfJobController extends Controller
{
    /**
     * ENDPOINT 1: Dispatch job generate PDF secara async.
     *
     * Method : POST
     * URL    : /api/reports/{reportType}/pdf/async
     * Auth   : Bearer JWT (sama seperti endpoint yang sudah ada)
     * Body   : { "TglAwal": "2026-01-01", "TglAkhir": "2026-01-31", ... }
     *
     * Response sukses (HTTP 202 Accepted):
     * {
     *   "job_id": "550e8400-e29b-41d4-a716-446655440000",
     *   "status": "queued",
     *   "status_url": "/api/reports/jobs/550e8400.../status",
     *   "message": "PDF sedang diproses di background. Cek status_url untuk update."
     * }
     */
    public function dispatch(Request $request, string $reportType): JsonResponse
    {
        // Map report type ke service class dan blade view
        $reportConfig = $this->getReportConfig($reportType);

        if (! $reportConfig) {
            return response()->json([
                'message' => "Jenis laporan '{$reportType}' tidak ditemukan.",
            ], 404);
        }

        // Buat record job di database
        $jobStatus = PdfJobStatus::createJob(
            reportType : $reportType,
            payload    : $request->all(),
            requestedBy: $request->user()?->username ?? 'unknown',
        );

        // Masukkan job ke queue (background)
        GenerateReportPdfJob::dispatch(
            jobId              : $jobStatus->job_id,
            reportType         : $reportType,
            reportServiceClass : $reportConfig['service'],
            requestPayload     : $request->all(),
            bladeView          : $reportConfig['view'],
        );

        return response()->json([
            'job_id'     => $jobStatus->job_id,
            'status'     => $jobStatus->status,
            'status_url' => route('api.pdf-jobs.status', $jobStatus->job_id),
            'message'    => 'PDF sedang diproses di background. Cek status_url untuk update.',
        ], 202); // HTTP 202 = Accepted (request diterima, proses belum selesai)
    }

    /**
     * ENDPOINT 2: Cek status job.
     *
     * Method : GET
     * URL    : /api/reports/jobs/{jobId}/status
     * Auth   : Bearer JWT
     *
     * Response jika masih proses (HTTP 200):
     * { "job_id": "...", "status": "processing", "report_type": "mutasi-barang-jadi" }
     *
     * Response jika selesai (HTTP 200):
     * { "job_id": "...", "status": "done", "download_url": "/api/reports/jobs/.../download" }
     *
     * Response jika gagal (HTTP 200):
     * { "job_id": "...", "status": "failed", "error": "Pesan error..." }
     */
    public function status(string $jobId): JsonResponse
    {
        $job = PdfJobStatus::find($jobId);

        if (! $job) {
            return response()->json(['message' => 'Job tidak ditemukan.'], 404);
        }

        $response = [
            'job_id'      => $job->job_id,
            'status'      => $job->status,
            'report_type' => $job->report_type,
            'created_at'  => $job->created_at->toIso8601String(),
        ];

        if ($job->status === PdfJobStatus::STATUS_DONE) {
            $response['download_url'] = route('api.pdf-jobs.download', $job->job_id);
            $response['expires_at']   = $job->expires_at?->toIso8601String();
        }

        if ($job->status === PdfJobStatus::STATUS_FAILED) {
            $response['error'] = $job->error_message;
        }

        return response()->json($response);
    }

    /**
     * ENDPOINT 3: Download file PDF.
     *
     * Method : GET
     * URL    : /api/reports/jobs/{jobId}/download
     * Auth   : Bearer JWT
     *
     * Response: stream file PDF (Content-Type: application/pdf)
     */
    public function download(string $jobId): Response|JsonResponse
    {
        $job = PdfJobStatus::find($jobId);

        if (! $job) {
            return response()->json(['message' => 'Job tidak ditemukan.'], 404);
        }

        if ($job->status !== PdfJobStatus::STATUS_DONE) {
            return response()->json([
                'message' => 'PDF belum siap. Status saat ini: ' . $job->status,
                'status'  => $job->status,
            ], 409); // HTTP 409 = Conflict (state tidak sesuai ekspektasi)
        }

        if (! Storage::disk(config('app.pdf_storage_disk', 'local'))->exists($job->file_path)) {
            return response()->json(['message' => 'File PDF tidak ditemukan. Mungkin sudah kadaluarsa.'], 410); // HTTP 410 = Gone
        }

        $filename = basename($job->file_path);
        $content  = Storage::disk(config('app.pdf_storage_disk', 'local'))->get($job->file_path);

        return response($content, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Map report type slug ke konfigurasi service dan view.
     *
     * CATATAN UNTUK DEVELOPER/AGENT:
     * Tambahkan entry baru di sini setiap kali ada jenis laporan baru.
     * Format: 'slug-laporan' => ['service' => ServiceClass::class, 'view' => 'nama.blade']
     */
    private function getReportConfig(string $reportType): ?array
    {
        $configs = [
            'mutasi-barang-jadi' => [
                'service' => \App\Services\MutasiBarangJadiService::class,
                'view'    => 'reports.mutasi.barang-jadi',
            ],
            'mutasi-finger-joint' => [
                'service' => \App\Services\MutasiFingerJointService::class,
                'view'    => 'reports.mutasi.finger-joint',
            ],
            'mutasi-moulding' => [
                'service' => \App\Services\MutasiMouldingService::class,
                'view'    => 'reports.mutasi.moulding',
            ],
            // Tambahkan mapping untuk semua 16 report yang ada...
        ];

        return $configs[$reportType] ?? null;
    }
}
```

---

## FASE 5 — Routing

### Langkah 5.1 — Tambah Routes di `routes/api.php`

Buka `routes/api.php` dan tambahkan di dalam middleware group yang sudah ada (yang sudah punya JWT middleware):

```php
use App\Http\Controllers\Api\PdfJobController;

// Letakkan di dalam Route::middleware(['jwt.auth']) yang sudah ada
Route::prefix('reports')->group(function () {

    // ── ENDPOINT BARU (ASYNC) ──────────────────────────────────────────────

    // Dispatch job generate PDF
    Route::post('{reportType}/pdf/async', [PdfJobController::class, 'dispatch'])
         ->name('api.pdf-jobs.dispatch');

    // Cek status job
    Route::get('jobs/{jobId}/status', [PdfJobController::class, 'status'])
         ->name('api.pdf-jobs.status');

    // Download hasil PDF
    Route::get('jobs/{jobId}/download', [PdfJobController::class, 'download'])
         ->name('api.pdf-jobs.download');

    // ── ENDPOINT LAMA (TETAP ADA, JANGAN DIHAPUS) ─────────────────────────
    // Endpoint sinkronus yang sudah ada biarkan tetap berjalan.
    // Pengguna bisa memilih antara /pdf (sync) atau /pdf/async (async).
});
```

---

## FASE 6 — Jalankan Queue Worker

### Langkah 6.1 — Jalankan Worker (Development)

```bash
# Terminal terpisah, biarkan terus berjalan
php artisan queue:work redis --queue=default --tries=3 --timeout=300
```

### Langkah 6.2 — Jalankan Worker (Production dengan Supervisor)

Buat file konfigurasi Supervisor di `/etc/supervisor/conf.d/open-api-report-worker.conf`:

```ini
[program:open-api-report-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/open-api-report/artisan queue:work redis --sleep=3 --tries=3 --timeout=300
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/open-api-report-worker.log
stopwaitsecs=3600
```

Aktifkan:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start open-api-report-worker:*
```

---

## FASE 7 — Cleanup Otomatis File PDF (Opsional tapi Direkomendasikan)

### Langkah 7.1 — Buat Artisan Command

```bash
php artisan make:command CleanExpiredPdfFiles
```

Isi `app/Console/Commands/CleanExpiredPdfFiles.php`:

```php
<?php

namespace App\Console\Commands;

use App\Models\PdfJobStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanExpiredPdfFiles extends Command
{
    protected $signature   = 'pdf:clean-expired';
    protected $description = 'Hapus file PDF yang sudah melewati waktu kadaluarsa';

    public function handle(): void
    {
        $expired = PdfJobStatus::where('status', PdfJobStatus::STATUS_DONE)
            ->where('expires_at', '<', now())
            ->get();

        $disk = Storage::disk(config('app.pdf_storage_disk', 'local'));

        foreach ($expired as $job) {
            if ($job->file_path && $disk->exists($job->file_path)) {
                $disk->delete($job->file_path);
                $this->line("Dihapus: {$job->file_path}");
            }
            $job->delete();
        }

        $this->info("Selesai. {$expired->count()} file kadaluarsa dihapus.");
    }
}
```

### Langkah 7.2 — Jadwalkan di `app/Console/Kernel.php`

```php
// Di dalam method schedule()
$schedule->command('pdf:clean-expired')->hourly();
```

Jalankan scheduler (di crontab server):
```bash
# Tambahkan ke crontab: crontab -e
* * * * * cd /path/to/open-api-report && php artisan schedule:run >> /dev/null 2>&1
```

---

## Cara Penggunaan API (Contoh Request Lengkap)

### Step 1 — Minta Generate PDF (Async)

```bash
# Request
curl -X POST http://localhost:8000/api/reports/mutasi-barang-jadi/pdf/async \
  -H "Authorization: Bearer <jwt_token>" \
  -H "Content-Type: application/json" \
  -d '{"TglAwal": "2026-01-01", "TglAkhir": "2026-01-31"}'

# Response (langsung, < 100ms)
{
  "job_id": "550e8400-e29b-41d4-a716-446655440000",
  "status": "queued",
  "status_url": "http://localhost:8000/api/reports/jobs/550e8400.../status",
  "message": "PDF sedang diproses di background. Cek status_url untuk update."
}
```

### Step 2 — Polling Status (Cek Setiap 3-5 Detik)

```bash
# Request
curl http://localhost:8000/api/reports/jobs/550e8400-e29b-41d4-a716-446655440000/status \
  -H "Authorization: Bearer <jwt_token>"

# Response saat masih proses
{ "job_id": "550e8400...", "status": "processing", "report_type": "mutasi-barang-jadi" }

# Response saat selesai
{
  "job_id": "550e8400...",
  "status": "done",
  "report_type": "mutasi-barang-jadi",
  "download_url": "http://localhost:8000/api/reports/jobs/550e8400.../download",
  "expires_at": "2026-01-15T10:00:00+00:00"
}
```

### Step 3 — Download PDF

```bash
curl http://localhost:8000/api/reports/jobs/550e8400-e29b-41d4-a716-446655440000/download \
  -H "Authorization: Bearer <jwt_token>" \
  --output laporan-mutasi-barang-jadi.pdf
```

---

## Checklist Verifikasi Implementasi

Gunakan checklist ini setelah selesai untuk memastikan semuanya berjalan:

- [ ] `php artisan migrate` berhasil → tabel `pdf_job_statuses` ada
- [ ] `php artisan queue:work redis --once` tidak error
- [ ] `POST /api/reports/mutasi-barang-jadi/pdf/async` → response 202 dengan `job_id`
- [ ] `GET /api/reports/jobs/{jobId}/status` → response 200 dengan status `queued` atau `processing`
- [ ] Setelah beberapa detik, status berubah menjadi `done`
- [ ] `GET /api/reports/jobs/{jobId}/download` → file PDF berhasil diunduh
- [ ] Jika terjadi error di job, status berubah menjadi `failed` dengan pesan error
- [ ] `php artisan pdf:clean-expired` berjalan dan menghapus file lama

---

## Ringkasan File yang Perlu Dibuat/Dimodifikasi

| File | Aksi |
|---|---|
| `.env` | Modifikasi — tambah Redis & queue config |
| `database/migrations/xxxx_create_pdf_job_statuses_table.php` | Buat baru |
| `app/Models/PdfJobStatus.php` | Buat baru |
| `app/Contracts/ReportDataInterface.php` | Buat baru |
| `app/Jobs/GenerateReportPdfJob.php` | Buat baru |
| `app/Http/Controllers/Api/PdfJobController.php` | Buat baru |
| `routes/api.php` | Modifikasi — tambah 3 route baru |
| `app/Console/Commands/CleanExpiredPdfFiles.php` | Buat baru (opsional) |
| `app/Console/Kernel.php` | Modifikasi — tambah schedule (opsional) |
