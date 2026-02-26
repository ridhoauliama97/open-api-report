# api.reports.mutasi-sanding.health

- Method: `POST`
- URI: `/api/reports/mutasi-sanding/health`
- Name: `api.reports.mutasi-sanding.health`
- Action: `App\Http\Controllers\MutasiSandingController@health`
- Middleware: `api, App\Http\Middleware\AuthenticateReportJwtClaims`

## Contoh cURL

```bash
curl -X POST "http://localhost:8000/api/reports/mutasi-sanding/health"
```
