# api.reports.mutasi-st.pdf

- Method: `GET|POST|HEAD`
- URI: `/api/reports/mutasi-st/pdf`
- Name: `api.reports.mutasi-st.pdf`
- Action: `App\Http\Controllers\MutasiSTController@download`
- Middleware: `api, App\Http\Middleware\AuthenticateReportJwtClaims`

## Contoh cURL

```bash
curl -X GET "http://localhost:8000/api/reports/mutasi-st/pdf"
```
