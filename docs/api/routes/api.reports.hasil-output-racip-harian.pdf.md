# api.reports.hasil-output-racip-harian.pdf

- Method: `GET|POST|HEAD`
- URI: `/api/reports/hasil-output-racip-harian/pdf`
- Name: `api.reports.hasil-output-racip-harian.pdf`
- Action: `App\Http\Controllers\HasilOutputRacipHarianController@download`
- Middleware: `api, App\Http\Middleware\AuthenticateReportJwtClaims`

## Contoh cURL

```bash
curl -X GET "http://localhost:8000/api/reports/hasil-output-racip-harian/pdf"
```
