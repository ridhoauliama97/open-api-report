# api.reports.mutasi-hasil-racip.pdf

- Method: `GET|POST|HEAD`
- URI: `/api/reports/mutasi-hasil-racip/pdf`
- Name: `api.reports.mutasi-hasil-racip.pdf`
- Action: `App\Http\Controllers\MutasiHasilRacipController@download`
- Middleware: `api, App\Http\Middleware\AuthenticateReportJwtClaims`

## Contoh cURL

```bash
curl -X GET "http://localhost:8000/api/reports/mutasi-hasil-racip/pdf"
```
