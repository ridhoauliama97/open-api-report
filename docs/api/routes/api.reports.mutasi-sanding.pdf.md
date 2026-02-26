# api.reports.mutasi-sanding.pdf

- Method: `GET|POST|HEAD`
- URI: `/api/reports/mutasi-sanding/pdf`
- Name: `api.reports.mutasi-sanding.pdf`
- Action: `App\Http\Controllers\MutasiSandingController@download`
- Middleware: `api, App\Http\Middleware\AuthenticateReportJwtClaims`

## Contoh cURL

```bash
curl -X GET "http://localhost:8000/api/reports/mutasi-sanding/pdf"
```
