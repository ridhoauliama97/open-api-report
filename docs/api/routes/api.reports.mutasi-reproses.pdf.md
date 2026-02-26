# api.reports.mutasi-reproses.pdf

- Method: `GET|POST|HEAD`
- URI: `/api/reports/mutasi-reproses/pdf`
- Name: `api.reports.mutasi-reproses.pdf`
- Action: `App\Http\Controllers\MutasiReprosesController@download`
- Middleware: `api, App\Http\Middleware\AuthenticateReportJwtClaims`

## Contoh cURL

```bash
curl -X GET "http://localhost:8000/api/reports/mutasi-reproses/pdf"
```
