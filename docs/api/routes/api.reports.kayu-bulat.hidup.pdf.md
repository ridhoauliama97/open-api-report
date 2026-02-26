# api.reports.kayu-bulat.hidup.pdf

- Method: `GET|POST|HEAD`
- URI: `/api/reports/kayu-bulat/hidup/pdf`
- Name: `api.reports.kayu-bulat.hidup.pdf`
- Action: `App\Http\Controllers\KayuBulatHidupController@download`
- Middleware: `api, App\Http\Middleware\AuthenticateReportJwtClaims`

## Contoh cURL

```bash
curl -X GET "http://localhost:8000/api/reports/kayu-bulat/hidup/pdf"
```
