# AGENTS.md — open-api-report

## Stack
- **Laravel 12** / PHP 8.2 / mPDF 8.2 / SQL Server (stored procedures) / PhpSpreadsheet
- Vite + Bootstrap 5; Node 24.16.0 (`.nvmrc`)

## Quick start
```bash
composer setup                  # full install: deps + key + migrate + npm build
composer dev                    # server + queue + logs + vite (concurrently)
composer test                   # config:clear && artisan test (sqlite :memory:)
./vendor/bin/pint               # Laravel Pint formatting
php artisan reports:audit-conventions && php artisan reports:audit-api   # verify new reports
```

## Architecture
- **~150 reports** — each is Controller + Service + FormRequest + Blade view
- Controller methods: `preview()`, `download()`, `health()`; some also have `index()` for web form views
- ALL FormRequests extend `BaseReportRequest` — `failedValidation()` auto-returns JSON 422 on `api/*`, don't override
- ALL PDF via `App\Services\PdfGenerator` — **never `new Mpdf()`**; methods: `render()` (string) / `renderToFile()` (file, memory-efficient). Supports `pdf_orientation` and `pdf_format` (default A4) in view data
- Subtitle (period label) format: `'Dari '.$date->locale('id')->isoFormat('DD-MMM-YY').' s/d '.$date->locale('id')->isoFormat('DD-MMM-YY')` — always lowercase `s/d`, Indonesian month names via `locale('id')`
- Date convention: ALL date displays in PDF views must use `->locale('id')->isoFormat('DD-MMM-YY')` → e.g. `01-Mei-26`. No `/`, no `YYYY`, no `d/m/Y`.
- PDF render cache via `pdf_render_cache_store` + `pdf_render_cache_ttl_seconds`; auto-bypassed in `local`/`debug` mode
- ALL DB queries use parameterized `DB::select('EXEC SP_... ?, ?', [...])` — **no string interpolation**; `call_syntax=query` supports raw SQL for non-SQL Server testing
- Route registration: `$registerReportRoutes()` closure in `routes/api.php` generates 3 routes (preview/download/health) per entry; add one entry to 1 of 4 groups: `$mutasiReportRouteDefinitions`, `$kayuBulatReportRouteDefinitions`, `$sawnTimberReportRouteDefinitions`, `$standaloneReportRouteDefinitions`
- **3 async PDF implementations**: (1) generic `PdfJobController` + `GenerateReportPdfJob` route group, (2) `LabelStHidupDetailController` custom endpoints, (3) `StockSTKeringController` custom endpoints
- Exceptions: `AscendXmlTestController` (~60+ non-standard `internal/ascends/*` routes), `EmployeeListController` (web-only under `reports/ascends/ru/hrm/employee-list/`)

## Auth
- Custom `AuthenticateReportJwtClaims` middleware (HS256/HS384/HS512) with Sanctum personal-access-token fallback
- Two user models: `App\Models\User` (connection `sqlsrv`) and `App\Models\PpsUser extends User` (connection `sqlsrv_pps`)
- In controllers: `$request->user() ?? auth('api')->user()`
- JWT secrets: `REPORT_API_JWT_SECRET`, `REPORT_API_JWT_SECRETS`, `SECRET_KEY` (supports `base64:` prefix)
- Custom auth providers registered: `legacy-eloquent` and `dual-legacy-eloquent`

## Database connections
- **`sqlsrv`** (WPS) — env `DB_HOST`, `DB_DATABASE` (default `WPS_TEST3` in config)
- **`sqlsrv_pps`** (PPS) — env `DB_HOST_PPS`, `DB_DATABASE_PPS` (default `WPS_TEST3` in config)
- Every PPS report uses the non-default connection; config at `config/database.php:102-130`

## Testing
```bash
php artisan test tests/Feature/MutasiBarangJadiReportFeatureTest.php
```
- SQLite `:memory:`, real HS256 JWTs via `$this->issueJwtForUser($user)` in `TestCase`
- Services are **Mockery-mocked** — no real SP calls; call `Mockery::close()` in `tearDown()`
- Helpers: `assertPdfDisposition()`, `$this->createBearerToken($user)`, `$this->authJsonHeaders($user)`
- Isolate env-dependent config in `setUp()`:
  ```php
  config()->set('reports.report_auth.issuers', []);
  config()->set('reports.report_auth.audiences', []);
  ```
- phpunit.xml: `QUEUE_CONNECTION=sync`, `CACHE_STORE=array`, `JWT_SECRET=testing-jwt-secret-key-...`

## Artisan commands
| Command | What |
|---|---|
| `reports:audit-conventions` | Checks code conventions (BaseReportRequest, no `new Mpdf`, etc.) |
| `reports:audit-api` | Audits routes vs OpenAPI spec |
| `pdf:clean-expired` | Deletes expired PDFs (scheduled hourly) |
| `reports:refresh-shared-pdfs-if-changed` | Refreshes cached shared PDFs (every 5min, no overlap) |
| `db:export-structure {connection}` | Exports SQL Server schema to JSON (`sqlsrv`/`sqlsrv_pps`) |

## Gotchas
- PDF retention: env `PDF_RETENTION_HOURS` (NOT `REPORT_PDF_JOB_RETENTION_HOURS`); config in `config/app.php:126-134`
- Memory limit per report via env e.g. `LABEL_ST_HIDUP_DETAIL_PDF_MEMORY_LIMIT=2048M`, `STOCK_ST_KERING_PDF_MEMORY_LIMIT=1024M`, `SEMUA_LABEL_PDF_MEMORY_LIMIT=2048M`
- PDF orientation auto-detects landscape when >10 columns; override via `pdf_orientation` in view data; format via `pdf_format` (default A4)
- `REPORT_MAX_EXECUTION_TIME` (default 300s) extends PHP timeout for `/reports/*`, `/api/reports/*`, `/dashboard/*` routes
- `QUEUE_CONNECTION=redis` in `.env.example` (uses `database` in local `.env`, `sync` in testing)
- `CACHE_STORE=database` in `.env.example` (uses `file` in local `.env`, `array` in testing)
- `APP_TIMEZONE=Asia/Jakarta` default
- `EmployeeListController` lives at `App\Http\Controllers\Ascends\Ru\Hrm\` (not in `AscendXmlTestController`)
- `config/reports.php` (2746 lines) is the single source of truth for SP names, DB connections, expected columns, and `report_auth` config (JWT secrets/issuers/audiences)
- `boost.json` registers `codex` agent + `laravel-best-practices` skill; no `opencode.json` exists
- DB default in `config/database.php` points to `WPS_TEST3` — set env vars for target DB
- Detailed full reference: `AGENT_INSTRUCTIONS.md` (463 lines with code patterns)

## Key files
| Need | Path |
|---|---|
| Report configs (SPs, DB, columns) | `config/reports.php` |
| PDF/retention config | `config/app.php` |
| API routes (report registration) | `routes/api.php` |
| Web routes (form views) | `routes/web.php` |
| Schedule definitions | `routes/console.php` |
| PDF wrapper (mPDF) | `app/Services/PdfGenerator.php` |
| Auth middleware | `app/Http/Middleware/AuthenticateReportJwtClaims.php` |
| Async job | `app/Jobs/GenerateReportPdfJob.php` |
| App bootstrap (exec time, auth providers) | `app/Providers/AppServiceProvider.php` |
| Base request (all report requests extend) | `app/Http/Requests/BaseReportRequest.php` |
| Full code patterns reference | `AGENT_INSTRUCTIONS.md` |
