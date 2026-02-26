# api.reports.mutasi-laminating.pdf

- Method: `GET|POST|HEAD`
- URI: `/api/reports/mutasi-laminating/pdf`
- Name: `api.reports.mutasi-laminating.pdf`
- Action: `App\Http\Controllers\MutasiLaminatingController@download`
- Middleware: `api, App\Http\Middleware\AuthenticateReportJwtClaims`

## Contoh cURL

```bash
curl -X GET "http://localhost:8000/api/reports/mutasi-laminating/pdf"
```
