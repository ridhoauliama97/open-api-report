# api.reports.mutasi-kayu-bulat.pdf

- Method: `GET|POST|HEAD`
- URI: `/api/reports/mutasi-kayu-bulat/pdf`
- Name: `api.reports.mutasi-kayu-bulat.pdf`
- Action: `App\Http\Controllers\MutasiKayuBulatController@download`
- Middleware: `api, App\Http\Middleware\AuthenticateReportJwtClaims`

## Contoh cURL

```bash
curl -X GET "http://localhost:8000/api/reports/mutasi-kayu-bulat/pdf"
```
