# api.reports.mutasi-st.health

- Method: `POST`
- URI: `/api/reports/mutasi-st/health`
- Name: `api.reports.mutasi-st.health`
- Action: `App\Http\Controllers\MutasiSTController@health`
- Middleware: `api, App\Http\Middleware\AuthenticateReportJwtClaims`

## Contoh cURL

```bash
curl -X POST "http://localhost:8000/api/reports/mutasi-st/health"
```
