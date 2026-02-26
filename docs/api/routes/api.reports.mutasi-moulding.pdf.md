# api.reports.mutasi-moulding.pdf

- Method: `GET|POST|HEAD`
- URI: `/api/reports/mutasi-moulding/pdf`
- Name: `api.reports.mutasi-moulding.pdf`
- Action: `App\Http\Controllers\MutasiMouldingController@download`
- Middleware: `api, App\Http\Middleware\AuthenticateReportJwtClaims`

## Contoh cURL

```bash
curl -X GET "http://localhost:8000/api/reports/mutasi-moulding/pdf"
```
