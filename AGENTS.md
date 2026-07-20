# AGENTS.md — open-api-report

## Stack
- **Laravel 12** / PHP ^8.2 / mPDF 8.2 / SQL Server (stored procedures) / PhpSpreadsheet ^5.8
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
- **~200+ reports** in 4 groups: Mutasi (~20), Kayu Bulat (~32), Sawn Timber (~42), Standalone (~100+ including PPS)
- Standard report = Controller + Service + FormRequest + Blade view, with methods `preview()`, `download()`, `health()`; some also have `index()` for web form views
- ALL FormRequests extend `BaseReportRequest` — `failedValidation()` auto-returns JSON 422 on `api/*`; **never override**
- ALL PDF via `App\Services\PdfGenerator` — **never `new Mpdf()`**; methods: `render()` (string) / `renderToFile()` (file, memory-efficient). Supports `pdf_orientation` and `pdf_format` (default A4) in view data
- Subtitle (period label): `'Dari '.$date->locale('id')->isoFormat('DD-MMM-YY').' s/d '.$date->locale('id')->isoFormat('DD-MMM-YY')` — lowercase `s/d`, Indonesian month names
- Date convention: ALL PDF view dates use `->locale('id')->isoFormat('DD-MMM-YY')` → e.g. `01-Mei-26`. No `/`, no `YYYY`, no `d/m/Y`
- PDF render cache via `config('app.pdf_render_cache_store')` + `config('app.pdf_render_cache_ttl_seconds')` (default 300s); auto-bypassed in `local`/`debug`
- ALL DB queries: `DB::select('EXEC SP_... ?, ?', [...])` — **no string interpolation**; `call_syntax=query` for non-SQL Server
- Route registration: `$registerReportRoutes()` closure in `routes/api.php` generates 3 routes (preview/download/health) per entry; add entry to 1 of 4 arrays: `$mutasiReportRouteDefinitions`, `$kayuBulatReportRouteDefinitions`, `$sawnTimberReportRouteDefinitions`, `$standaloneReportRouteDefinitions`. Two special groups registered outside the loop: `RekapHasilSawmillPerMejaUpahBoronganV2` and PPS Inject alias
- **3 async PDF patterns**: (1) generic `PdfJobController` + `GenerateReportPdfJob`, (2) `LabelStHidupDetailController` custom endpoints, (3) `StockSTKeringController` custom endpoints
- Non-standard **`AscendXmlTestController`** (~9210 lines, ~60+ `internal/ascends/*` routes in `routes/api.php`, one method per route). `EmployeeListController` lives at `App\Http\Controllers\Ascends\Ru\Hrm\` (separate, web-only)
- Middleware stack (`bootstrap/app.php`): `LogUserActivity` + `NormalizePdfDownloadFilename` on all API/web routes; `ForceattachmentPdfPreview` on web routes only
  - Note: `ForceattachmentPdfPreview` class is in file `ForceInlinePdfPreview.php` (intentional mismatch)
- **End of report**: setiap selesai membuat Ascends shared report, tambah dokumentasi endpoint di `docs/ascends-endpoint/` sesuai kategorinya

## Auth
- Custom `AuthenticateReportJwtClaims` middleware (HS256/HS384/HS512) with Sanctum personal-access-token fallback
- Two user models: `App\Models\User` (connection `sqlsrv`), `App\Models\PpsUser extends User` (connection `sqlsrv_pps`)
- JWT secrets: `REPORT_API_JWT_SECRET`, `REPORT_API_JWT_SECRETS`, `SECRET_KEY` (supports `base64:` prefix)
- Custom auth providers: `legacy-eloquent` and `dual-legacy-eloquent`

## Database connections
| Connection | Env vars | Default DB |
|---|---|---|
| `sqlsrv` (WPS) | `DB_HOST`, `DB_DATABASE` | `WPS_TEST3` |
| `sqlsrv_pps` (PPS) | `DB_HOST_PPS`, `DB_DATABASE_PPS` | `WPS_TEST3` |
- Default connection is `sqlite` (testing/local); set `DB_CONNECTION=sqlsrv` for real data
- Every PPS report explicitly uses the non-default connection

## Testing
```bash
php artisan test tests/Feature/MutasiBarangJadiReportFeatureTest.php
```
- SQLite `:memory:`, real HS256 JWTs via `$this->issueJwtForUser($user)` in `TestCase`
- Services are **Mockery-mocked** — no real SP calls; `Mockery::close()` in `tearDown()`
- Helpers: `assertPdfDisposition()`, `issueJwtForUser()`. Per-file: `authJsonHeaders()`, `createBearerToken()`
- Isolate env config in `setUp()`:
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
| `reports:audit-api` {--fail-on-missing-openapi} | Audits routes vs OpenAPI spec |
| `pdf:clean-expired` | Deletes expired PDFs (hourly via `routes/console.php`) |
| `reports:refresh-shared-pdfs-if-changed` | Refreshes cached shared PDFs (every 5min, no overlap) |
| `reports:refresh-label-st-hidup-detail-pdf-if-changed` | Refresh Label ST Hidup Detail PDF when data changes |
| `reports:refresh-stock-st-kering-pdf-if-changed` | Refresh Stock ST Kering PDF when data changes |
| `reports:generate-label-st-hidup-detail-pdf` | DB-free background Label ST Hidup Detail PDF generation |
| `reports:generate-stock-st-kering-pdf` | DB-free background Stock ST Kering PDF generation |
| `reports:warm-label-st-hidup-detail-pdf` | Pre-generate shared Label ST Hidup Detail PDF for all users |
| `reports:warm-stock-st-kering-pdf` | Pre-generate shared Stock ST Kering PDF for all users |
| `db:export-structure {connection}` | Exports SQL Server schema to JSON; `--with-definitions` for SP body |
| `swagger:generate` | Generates OpenAPI spec JSON (NOT `generate:swagger-spec`) |

## Gotchas
- PDF retention: env `PDF_RETENTION_HOURS` (NOT `REPORT_PDF_JOB_RETENTION_HOURS`); config in `config/app.php:130`
- Memory limit per report via env e.g. `LABEL_ST_HIDUP_DETAIL_PDF_MEMORY_LIMIT=2048M`, `STOCK_ST_KERING_PDF_MEMORY_LIMIT=1024M`, `SEMUA_LABEL_PDF_MEMORY_LIMIT=2048M`
- PDF orientation auto-detects landscape when >10 columns; override via `pdf_orientation` in view data; format via `pdf_format` (default A4)
- `REPORT_MAX_EXECUTION_TIME` (default 300s) extends PHP timeout for `/reports/*`, `/api/reports/*`, `/dashboard/*` routes (see `AppServiceProvider.php:47`)
- `QUEUE_CONNECTION=redis` in `.env.example` (uses `database` in local `.env`, `sync` in testing)
- `CACHE_STORE=database` in `.env.example` (uses `file` in local `.env`, `array` in testing)
- `APP_TIMEZONE=Asia/Jakarta` default
- `config/reports.php` (2746 lines) is the single source of truth for SP names, DB connections, columns, and `report_auth` config
- `boost.json` registers `codex` agent + `laravel-best-practices` skill; no `opencode.json`
- Detailed reference: `AGENT_INSTRUCTIONS.md` (463 lines with code patterns)
- `fmtAmount()` helper is defined locally in each blade file — usage: `number_format($v, 2, ',', '.')`; zero → `-`; negative → `'- '` prefix

## UC Shared Report Design Conventions

Views at `resources/views/ascends/shared/`:

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
| **Sub-section** | `.sub-section-header td` | **bold**; `color: #9c111d`; border top/bottom |
| **Item row** | `.item-row td` | `padding-left: 4px` |
| **Striping** | `.row-odd td` / `.row-even td` | `#c9d1df` / `#eef2f8` |
| **Subtotal** | `.subtotal-row td` | **bold**; border top/bottom |
| **Grand total** | `.grand-total-row td` | **bold**; border top/bottom |
| **Empty state** | `.empty-row td` | `italic bold; color #9c111d; bg #c9d1df` |
| **Number align** | `.number` | `text-align: right` |
| **Number wrap** | `.nowrap` | `white-space: nowrap` |
| **Number negative** | `.number-negative` | `color: #9c111d` |
| **Footer** | — | `@include('ascends.shared.partials.report-footer')` |
