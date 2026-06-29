# AGENTS.md — open-api-report

## Stack
- **Laravel 12** / PHP 8.2 / mPDF 8.2 / SQL Server (stored procedures) / PhpSpreadsheet
- Vite + Bootstrap 5; Node 24.16.0 (`.nvmrc`)

## Quick start
```bash
composer install && npm install && php artisan key:generate && php artisan migrate
composer dev        # runs server + queue + logs + vite concurrently via npx concurrently
composer test       # config:clear && phpunit (sqlite :memory:)
./vendor/bin/pint   # Laravel Pint formatting
php artisan reports:audit-conventions && php artisan reports:audit-api   # verify new reports
```

## Architecture
- **~150 reports** — each is Controller + Service + FormRequest + Blade view
- Controller standard methods: `preview()`, `download()`, `health()`; some also have `index()` for web form views
- ALL FormRequests extend `BaseReportRequest` (not `FormRequest`) — `failedValidation()` auto-returns JSON 422 on `api/*`, don't override
- ALL PDF via `App\Services\PdfGenerator` — **never `new Mpdf()`**; use `render()` (string) or `renderToFile()` (file, memory-efficient)
- PDF render caching via `pdf_render_cache_store` + `pdf_render_cache_ttl_seconds` env vars; auto-bypassed in `local`/`debug` mode
- ALL DB queries use parameterized `DB::select('EXEC SP_... ?, ?', [...])` — **no string interpolation**; `call_syntax=query` supports raw SQL for non-SQL Server testing
- Route registration: `$registerReportRoutes()` closure in `routes/api.php` generates 3 routes (preview/download/health) per entry; add one array entry to one of 4 groups: `$mutasiReportRouteDefinitions`, `$kayuBulatReportRouteDefinitions`, `$sawnTimberReportRouteDefinitions`, `$standaloneReportRouteDefinitions`
- Async PDF: generic `GenerateReportPdfJob` + `PdfJobController` (dispatch/status/download); also custom per-report async on `LabelStHidupDetailController` and `StockSTKeringController`
- **3 separate async implementations**: (1) generic `PdfJobController` route group, (2) `LabelStHidupDetailController` custom endpoints, (3) `StockSTKeringController` custom endpoints
- Exception: `AscendXmlTestController` (~60+ non-standard `internal/ascends/*` routes), `EmployeeListController` (web-only index/preview/download under `reports/ascends/ru/hrm/employee-list/`)

## Auth
- Custom `AuthenticateReportJwtClaims` middleware (HS256/HS384/HS512) with Sanctum personal-access-token fallback
- Two user models: `App\Models\User` (WPS) and `App\Models\PpsUser` (PPS)
- In controllers: `$request->user() ?? auth('api')->user()`
- JWT secrets checked: `REPORT_API_JWT_SECRET`, `REPORT_API_JWT_SECRETS`, `SECRET_KEY` (supports `base64:` prefix)
- Two custom auth providers registered: `legacy-eloquent` and `dual-legacy-eloquent`

## Database connections
- **`sqlsrv`** (WPS default) — `DB_HOST`, `DB_DATABASE=WPS`
- **`sqlsrv_pps`** (PPS) — `DB_HOST_PPS`, `DB_DATABASE_PPS=PPS`
- Every PPS report uses the non-default connection; config in `config/database.php:102-130`

## Testing
```bash
php artisan test tests/Feature/MutasiBarangJadiReportFeatureTest.php  # single test class
```
- SQLite `:memory:` — real HS256 JWTs via `$this->issueJwtForUser($user)` in `TestCase`
- Services are **Mockery-mocked** — no real SP calls; call `Mockery::close()` in `tearDown()`
- `assertPdfDisposition()` helper on `TestCase`
- Common helpers: `$this->createBearerToken($user)` / `$this->authJsonHeaders($user)`
- Isolate env-dependent config in `setUp()`:
  ```php
  config()->set('reports.report_auth.issuers', []);
  config()->set('reports.report_auth.audiences', []);
  ```
- phpunit.xml: `QUEUE_CONNECTION=sync`, `CACHE_STORE=array`, `JWT_SECRET=testing-jwt-secret-key-...`

## Artisan commands
| Command | What |
|---|---|
| `reports:audit-conventions` | Validates code conventions (BaseReportRequest, no `new Mpdf`, etc.) |
| `reports:audit-api` | Audits routes vs OpenAPI spec |
| `pdf:clean-expired` | Deletes expired PDFs (scheduled hourly via `routes/console.php`) |
| `reports:refresh-shared-pdfs-if-changed` | Refreshes cached shared PDFs (scheduled every 5min, no overlap) |
| `db:export-structure {connection}` | Exports SQL Server schema to JSON (`sqlsrv` or `sqlsrv_pps`) |

## Gotchas
- Env key `PDF_RETENTION_HOURS` (NOT `REPORT_PDF_JOB_RETENTION_HOURS`) controls PDF file retention
- `PDF_STORAGE_DISK` / `PDF_STORAGE_PATH` / `PDF_RETENTION_HOURS` in `config/app.php`
- Memory limit per specific report via env e.g. `LABEL_ST_HIDUP_DETAIL_PDF_MEMORY_LIMIT=2048M`
- PDF orientation auto-detects landscape when >10 columns; override via `pdf_orientation` in view data
- `REPORT_MAX_EXECUTION_TIME` (default 300s) extends PHP timeout for `/reports/*`, `/api/reports/*`, `/dashboard/*` routes
- `QUEUE_CONNECTION=redis` in `.env.example` (uses `database` in local `.env`, `sync` in testing)
- `CACHE_STORE=database` in `.env.example` (uses `file` in local `.env`, `array` in testing)
- `APP_TIMEZONE=Asia/Jakarta` default
- `EmployeeListController` lives under `App\Http\Controllers\Ascends\Ru\Hrm\` (not in `AscendXmlTestController`)
- `config/reports.php` is 2746 lines — the single source of truth for SP names, DB connections, and expected columns per report
- `boost.json` registers `codex` agent + `laravel-best-practices` skill

## Key files
| Need | Path |
|---|---|
| All report configs (SP names, DB connections, expected columns) | `config/reports.php` |
| PDF/retention config | `config/app.php` |
| API routes (report registration) | `routes/api.php` |
| Web routes (form views) | `routes/web.php` |
| Schedule definitions | `routes/console.php` |
| PDF wrapper (mPDF) | `app/Services/PdfGenerator.php` |
| Auth middleware | `app/Http/Middleware/AuthenticateReportJwtClaims.php` |
| Async job | `app/Jobs/GenerateReportPdfJob.php` |
| App bootstrap (execution time, auth providers) | `app/Providers/AppServiceProvider.php` |
| Base request (all report requests extend) | `app/Http/Requests/BaseReportRequest.php` |
| Detailed reference with full code patterns | `AGENT_INSTRUCTIONS.md` |
