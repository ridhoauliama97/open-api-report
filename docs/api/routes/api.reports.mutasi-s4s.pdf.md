# api.reports.mutasi-s4s.pdf

- Method: `GET|POST|HEAD`
- URI: `/api/reports/mutasi-s4s/pdf`
- Name: `api.reports.mutasi-s4s.pdf`
- Action: `App\Http\Controllers\MutasiS4SController@download`
- Middleware: `api, App\Http\Middleware\AuthenticateReportJwtClaims`

## Contoh cURL

```bash
curl -X GET "http://localhost:8000/api/reports/mutasi-s4s/pdf"
```
