# api.reports.mutasi-s4s.health

- Method: `POST`
- URI: `/api/reports/mutasi-s4s/health`
- Name: `api.reports.mutasi-s4s.health`
- Action: `App\Http\Controllers\MutasiS4SController@health`
- Middleware: `api, App\Http\Middleware\AuthenticateReportJwtClaims`

## Contoh cURL

```bash
curl -X POST "http://localhost:8000/api/reports/mutasi-s4s/health"
```
