# api.reports.mutasi-laminating.health

- Method: `POST`
- URI: `/api/reports/mutasi-laminating/health`
- Name: `api.reports.mutasi-laminating.health`
- Action: `App\Http\Controllers\MutasiLaminatingController@health`
- Middleware: `api, App\Http\Middleware\AuthenticateReportJwtClaims`

## Contoh cURL

```bash
curl -X POST "http://localhost:8000/api/reports/mutasi-laminating/health"
```
