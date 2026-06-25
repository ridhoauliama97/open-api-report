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
- **~150 reports** — each is Controller + Service + FormRequest + Blade view
- Controller API methods: `preview()`, `download()`, `health()`; some also have `index()` for web views
- Exceptions: `AscendXmlTestController` (many non-standard internal/ascends/* endpoints), `LabelStHidupDetailController`/`StockSTKeringController` (custom async endpoints)
- ALL FormRequests extend `BaseReportRequest` (not `FormRequest` directly) — `failedValidation()` auto-returns JSON 422 on `api/*` routes, don't override
- ALL PDF via `App\Services\PdfGenerator` — **never `new Mpdf()`**; `render()` (string) and `renderToFile()` (file, memory-efficient)
- PDF render caching via `pdf_render_cache_store` + `pdf_render_cache_ttl_seconds` env vars; auto-bypassed in `local`/`debug`
- ALL DB queries use parameterized `DB::select('EXEC SP_... ?, ?', [...])` — **no string interpolation**; some reports support `call_syntax=query` for raw SQL
- Route registration: API routes via `$registerReportRoutes()` closure in `routes/api.php`; web routes in `routes/web.php`; add one array entry per new report to one of 4 route groups: `$mutasiReportRouteDefinitions`, `$kayuBulatReportRouteDefinitions`, `$sawnTimberReportRouteDefinitions`, `$standaloneReportRouteDefinitions`
- Async PDF: `GenerateReportPdfJob` reuses `download()` controller via synthetic HTTP request; also custom async endpoints on `LabelStHidupDetailController` and `StockSTKeringController`

## Auth
- Custom JWT middleware `AuthenticateReportJwtClaims` (HS256/HS384/HS512) with Sanctum fallback
- Two user models: `App\Models\User` (WPS) and `App\Models\PpsUser` (PPS)
- In controllers: `$request->user() ?? auth('api')->user()`
- JWT secrets checked: `REPORT_API_JWT_SECRET`, `REPORT_API_JWT_SECRETS`, `SECRET_KEY`
- Two custom auth providers registered: `legacy-eloquent` and `dual-legacy-eloquent`

## Testing
```bash
php artisan test tests/Feature/MutasiBarangJadiReportFeatureTest.php  # single test class
```
- Tests use **SQLite `:memory:`** — real HS256 JWTs generated via `$this->issueJwtForUser()`
- Services are **Mockery-mocked** — no real SP calls; call `Mockery::close()` in `tearDown()`
- `assertPdfDisposition()` helper on `TestCase`
- Common test helpers: `$this->createBearerToken($user)` / `$this->authJsonHeaders($user)` in test classes
- Isolate env-dependent config in `setUp()`:
  ```php
  config()->set('reports.report_auth.issuers', []);
  config()->set('reports.report_auth.audiences', []);
  ```
- PHPUnit config uses `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`, `JWT_SECRET=testing-jwt-secret-key-...`

## Artisan commands
| Command | What |
|---|---|
| `reports:audit-conventions` | Validates code conventions (BaseReportRequest, no `new Mpdf`, etc.) |
| `reports:audit-api` | Audits routes vs OpenAPI spec |
| `pdf:clean-expired` | Deletes expired PDFs (scheduled hourly via `routes/console.php`) |
| `reports:refresh-shared-pdfs-if-changed` | Refreshes cached shared PDFs (scheduled every 5min) |
| `db:export-structure {connection}` | Exports SQL Server schema to JSON |

## Gotchas
- **Two DB connections**: `sqlsrv` (WPS, default) and `sqlsrv_pps` (PPS) — every PPS report uses the non-default connection
- Env key `PDF_RETENTION_HOURS` (NOT `REPORT_PDF_JOB_RETENTION_HOURS`) controls PDF file retention
- `PDF_STORAGE_DISK` / `PDF_STORAGE_PATH` / `PDF_RETENTION_HOURS` in `config/app.php`
- `boost.json` registers `laravel-best-practices` skill for agents
- Memory limit per specific report via env e.g. `LABEL_ST_HIDUP_DETAIL_PDF_MEMORY_LIMIT=2048M`
- PDF orientation auto-detects landscape when >10 columns; override via `pdf_orientation` in view data
- `REPORT_MAX_EXECUTION_TIME` (default 300s) extends PHP timeout for `/reports/*`, `/api/reports/*`, `/dashboard/*` routes
- `QUEUE_CONNECTION=redis` in `.env.example` (uses `database` in testing)
- `CACHE_STORE=database` in `.env.example`
- `APP_TIMEZONE=Asia/Jakarta` default
- `EmployeeListController` lives under `App\Http\Controllers\Ascends\Ru\Hrm\` (not in `AscendXmlTestController`)

## Key files
| Need | Path |
|---|---|
| All report configs (SP names, DB connections) | `config/reports.php` |
| PDF/retention config | `config/app.php` |
| API routes (report registration) | `routes/api.php` |
| Web routes (form views) | `routes/web.php` |
| Schedule definitions | `routes/console.php` |
| PDF wrapper (mPDF) | `app/Services/PdfGenerator.php` |
| Auth middleware | `app/Http/Middleware/AuthenticateReportJwtClaims.php` |
| Async job | `app/Jobs/GenerateReportPdfJob.php` |
| App bootstrap (execution time, auth providers) | `app/Providers/AppServiceProvider.php` |
| Base request (all report requests extend) | `app/Http/Requests/BaseReportRequest.php` |
| Detailed reference | `AGENT_INSTRUCTIONS.md` |
