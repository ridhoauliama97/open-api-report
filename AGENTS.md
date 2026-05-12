# AGENTS.md — open-api-report

## Stack
- **Laravel 12** / PHP 8.2 / mPDF 8.2 / SQL Server (stored procedures)
- Vite + Bootstrap 5 for frontend; Node 24 (`.nvmrc`)

## Quick start
```bash
composer install && npm install && php artisan key:generate && php artisan migrate
composer dev        # runs server + queue + logs + vite concurrently
composer test       # config:clear && phpunit (sqlite :memory:)
./vendor/bin/pint   # Laravel Pint formatting
```

## Architecture
- **170+ reports** — each is Controller + Service + FormRequest + Blade view
- Controller signature: `preview()`, `download()`, `health()` — exactly these 3 public methods
- ALL FormRequests extend `BaseReportRequest` (not `FormRequest` directly)
- ALL PDF goes through `App\Services\PdfGenerator` — **never `new Mpdf()`**
- ALL DB queries use parameterized `DB::select('EXEC SP_... ?, ?', [...])` — **no string interpolation**
- Route registration via `$registerReportRoutes()` closure in `routes/api.php`; add one array entry per new report
- Async PDF: `GenerateReportPdfJob` reuses existing `download()` controller via synthetic HTTP request

## Auth
- Custom JWT middleware `AuthenticateReportJwtClaims` (HS256) with Sanctum fallback
- Two user models: `App\Models\User` (WPS) and `App\Models\PpsUser` (PPS)
- In controllers: `$request->user() ?? auth('api')->user()`

## Testing
```bash
php artisan test tests/Feature/MutasiBarangJadiReportFeatureTest.php  # single test class
```
- Tests use **SQLite `:memory:`** — real HS256 JWTs generated via `$this->issueJwtForUser()`
- Services are **Mockery-mocked** — no real SP calls
- `assertPdfDisposition()` helper on `TestCase`
- Isolate env-dependent config in `setUp()`:
  ```php
  config()->set('reports.report_auth.issuers', []);
  config()->set('reports.report_auth.audiences', []);
  ```

## Artisan commands
| Command | What |
|---|---|
| `reports:audit-conventions` | Validates code against conventions (BaseReportRequest, no `new Mpdf`, etc.) |
| `reports:audit-api` | Audits routes vs OpenAPI spec |
| `pdf:clean-expired` | Deletes expired PDFs (scheduled hourly via `routes/console.php`) |
| `db:export-structure {connection}` | Exports SQL Server schema |

## Gotchas
- **Two DB connections** for reports: `sqlsrv` (WPS, default) and `sqlsrv_pps` (PPS) — every PPS report uses the non-default connection
- Env key `PDF_RETENTION_HOURS` (NOT `REPORT_PDF_JOB_RETENTION_HOURS`) controls PDF file retention
- `REPORT_API_JWT_SECRET` / `REPORT_API_JWT_SECRETS` / `SECRET_KEY` — all three are checked for JWT signature verification
- `boost.json` registers `laravel-best-practices` skill for agents
- Memory limit per specific report via env e.g. `LABEL_ST_HIDUP_DETAIL_PDF_MEMORY_LIMIT=2048M`
- `BaseReportRequest::failedValidation()` auto-returns JSON 422 for `api/*` routes — don't override in children
- PDF orientation auto-detects landscape when >10 columns; override via `pdf_orientation` in view data

## Key files
| Need | Path |
|---|---|
| All report configs | `config/reports.php` |
| All routes | `routes/api.php` |
| PDF wrapper | `app/Services/PdfGenerator.php` |
| Auth middleware | `app/Http/Middleware/AuthenticateReportJwtClaims.php` |
| Async job | `app/Jobs/GenerateReportPdfJob.php` |
