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
- **~200+ reports** grouped into: Mutasi (~19), Kayu Bulat (~32), Sawn Timber (~42), Standalone (~100+ including PPS)
- Standard report = Controller + Service + FormRequest + Blade view, with methods `preview()`, `download()`, `health()`; some also have `index()` for web form views
- ALL FormRequests extend `BaseReportRequest` — `failedValidation()` auto-returns JSON 422 on `api/*`, don't override
- ALL PDF via `App\Services\PdfGenerator` — **never `new Mpdf()`**; methods: `render()` (string) / `renderToFile()` (file, memory-efficient). Supports `pdf_orientation` and `pdf_format` (default A4) in view data
- Subtitle (period label) format: `'Dari '.$date->locale('id')->isoFormat('DD-MMM-YY').' s/d '.$date->locale('id')->isoFormat('DD-MMM-YY')` — always lowercase `s/d`, Indonesian month names via `locale('id')`
- Date convention: ALL date displays in PDF views must use `->locale('id')->isoFormat('DD-MMM-YY')` → e.g. `01-Mei-26`. No `/`, no `YYYY`, no `d/m/Y`.
- PDF render cache via `pdf_render_cache_store` + `pdf_render_cache_ttl_seconds`; auto-bypassed in `local`/`debug` mode
- ALL DB queries use parameterized `DB::select('EXEC SP_... ?, ?', [...])` — **no string interpolation**; `call_syntax=query` supports raw SQL for non-SQL Server testing
- Route registration: `$registerReportRoutes()` closure in `routes/api.php` generates 3 routes (preview/download/health) per entry; add one entry to 1 of 4 groups: `$mutasiReportRouteDefinitions`, `$kayuBulatReportRouteDefinitions`, `$sawnTimberReportRouteDefinitions`, `$standaloneReportRouteDefinitions`. Two special-case groups registered outside the loop: `RekapHasilSawmillPerMejaUpahBoronganV2` and PPS Inject alias (search `api.php` for `->group` after the main loop)
- **3 async PDF implementations**: (1) generic `PdfJobController` + `GenerateReportPdfJob` route group, (2) `LabelStHidupDetailController` custom endpoints, (3) `StockSTKeringController` custom endpoints
- Non-standard **`AscendXmlTestController`** (~60+ `internal/ascends/*` routes, single 8117-line controller with many individual methods). `EmployeeListController` (web-only under `reports/ascends/ru/hrm/employee-list/`)
- **End of report**: setiap selesai membuat laporan Ascends shared, buat/tambah dokumentasi endpoint di `docs/ascends-endpoint/` sesuai folder kategorinya. Contoh format: `docs/ascends-endpoint/Shared/Finance/endpoint-api-shared-finance-receivable-details.md`
- Middleware stack (`bootstrap/app.php`): `LogUserActivity` + `NormalizePdfDownloadFilename` on all API/web routes; `ForceattachmentPdfPreview` on web routes only
  - Note: `ForceattachmentPdfPreview` class lives in file `ForceInlinePdfPreview.php` — class name and filename differ intentionally

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
- Helpers in `TestCase`: `assertPdfDisposition()`, `issueJwtForUser()`. Per-test-file helpers: `authJsonHeaders()`, `createBearerToken()`
- Isolate env-dependent config in `setUp()`:
  ```php
  config()->set('reports.report_auth.issuers', []);
  config()->set('reports.report_auth.audiences', []);
  config()->set('reports.report_auth.required_scope', null);
  ```
- phpunit.xml: `QUEUE_CONNECTION=sync`, `CACHE_STORE=array`, `JWT_SECRET=testing-jwt-secret-key-...`

## Artisan commands
| Command | What |
|---|---|
| `reports:audit-conventions` | Checks code conventions (BaseReportRequest, no `new Mpdf`, middleware, controller methods) |
| `reports:audit-api` | Audits routes vs OpenAPI spec |
| `pdf:clean-expired` | Deletes expired PDFs (scheduled hourly via `routes/console.php`) |
| `reports:refresh-shared-pdfs-if-changed` | Refreshes cached shared PDFs (every 5min, no overlap) |
| `reports:refresh-label-st-hidup-detail-pdf-if-changed` | Refresh Label ST Hidup Detail PDF only when data changes |
| `reports:refresh-stock-st-kering-pdf-if-changed` | Refresh Stock ST Kering PDF only when data changes |
| `reports:generate-label-st-hidup-detail-pdf` | DB-free background Label ST Hidup Detail PDF generation |
| `reports:generate-stock-st-kering-pdf` | DB-free background Stock ST Kering PDF generation |
| `reports:warm-label-st-hidup-detail-pdf` | Pre-generate shared Label ST Hidup Detail PDF for all users |
| `reports:warm-stock-st-kering-pdf` | Pre-generate shared Stock ST Kering PDF for all users |
| `db:export-structure {connection}` | Exports SQL Server schema to JSON (`sqlsrv`/`sqlsrv_pps`); add `--with-definitions` for SP body |
| `generate:swagger-spec` | Generates OpenAPI spec JSON |

## Gotchas
- PDF retention: env `PDF_RETENTION_HOURS` (NOT `REPORT_PDF_JOB_RETENTION_HOURS`); config in `config/app.php:130`
- Memory limit per report via env e.g. `LABEL_ST_HIDUP_DETAIL_PDF_MEMORY_LIMIT=2048M`, `STOCK_ST_KERING_PDF_MEMORY_LIMIT=1024M`, `SEMUA_LABEL_PDF_MEMORY_LIMIT=2048M`
- PDF orientation auto-detects landscape when >10 columns; override via `pdf_orientation` in view data; format via `pdf_format` (default A4)
- `REPORT_MAX_EXECUTION_TIME` (default 300s) extends PHP timeout for `/reports/*`, `/api/reports/*`, `/dashboard/*` routes
- `QUEUE_CONNECTION=redis` in `.env.example` (uses `database` in local `.env`, `sync` in testing)
- `CACHE_STORE=database` in `.env.example` (uses `file` in local `.env`, `array` in testing)
- `APP_TIMEZONE=Asia/Jakarta` default
- `EmployeeListController` lives at `App\Http\Controllers\Ascends\Ru\Hrm\` (not in `AscendXmlTestController`)
- `config/reports.php` (2746 lines) is the single source of truth for SP names, DB connections, expected columns, and `report_auth` config
- `boost.json` registers `codex` agent + `laravel-best-practices` skill; no `opencode.json` exists, but `.opencode/plans/` stores planning docs
- `skills-lock.json` registers additional engineering skills (ask-matt, codebase-design, decision-mapping, diagnosing-bugs, etc.)
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

## UC Shared Report Design Conventions

Semua Blade view di `resources/views/ascends/shared/` harus konsisten:

| Elemen | CSS Class | Style |
|---|---|---|
| **Font** | `body` | `"Noto Serif", serif; 10px` |
| **Company** | `.report-companyTitle` | `18px bold; text-align: center; margin: 0 0 4px` |
| **Title** | `.report-title` | `16px bold; text-align: center; margin: 0` |
| **Subtitle** | `.report-subtitle` | `12px; color: #636466; text-align: center; margin: 2px 0 20px` |
| **Page** | `@page` | `margin: 14mm 10mm 14mm 10mm; footer: html_reportFooter` |
| **Table** | `.data-table` | `1px solid #000` outer; `collapse; fixed` |
| **Cell** | `th, td` | `border-left/right: 1px solid #000; padding: 1px 2px` |
| **Section header** | `.section-header td` | **bold italic**; `color: #9c111d`; border top/bottom |
| **Sub-section header** | `.sub-section-header td` | **bold**; `color: #9c111d`; border top/bottom |
| **Item row** | `.item-row td` | `padding-left: 4px` |
| **Striping** | `.row-odd td` / `.row-even td` | `#c9d1df` / `#eef2f8` |
| **Subtotal** | `.subtotal-row td` | **bold**; border top/bottom |
| **Grand total** | `.grand-total-row td` | **bold**; border top/bottom |
| **Empty state** | `.empty-row td` | `italic bold; color #9c111d; bg #c9d1df` |
| **Number align** | `.number` | `text-align: right` |
| **Number wrap** | `.nowrap` | `white-space: nowrap` |
| **Number negative** | `.number-negative` | `color: #9c111d` |
| **Number format** | `fmtAmount()` | `number_format($v, 2, ',', '.')`; zero → `-`; negatif → `'- '` prefix |
| **Footer** | — | `@include('ascends.shared.partials.report-footer')` |
