# api.reports.mutasi-kayu-bulat-kg.pdf

- Method: `GET|POST|HEAD`
- URI: `/api/reports/mutasi-kayu-bulat-kg/pdf`
- Name: `api.reports.mutasi-kayu-bulat-kg.pdf`
- Action: `App\Http\Controllers\MutasiKayuBulatKGController@download`
- Middleware: `api, App\Http\Middleware\AuthenticateReportJwtClaims`

## Contoh cURL

```bash
curl -X GET "http://localhost:8000/api/reports/mutasi-kayu-bulat-kg/pdf"
```
