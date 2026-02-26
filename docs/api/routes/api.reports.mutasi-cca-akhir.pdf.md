# api.reports.mutasi-cca-akhir.pdf

- Method: `GET|POST|HEAD`
- URI: `/api/reports/mutasi-cca-akhir/pdf`
- Name: `api.reports.mutasi-cca-akhir.pdf`
- Action: `App\Http\Controllers\MutasiCCAkhirController@download`
- Middleware: `api, App\Http\Middleware\AuthenticateReportJwtClaims`

## Contoh cURL

```bash
curl -X GET "http://localhost:8000/api/reports/mutasi-cca-akhir/pdf"
```
