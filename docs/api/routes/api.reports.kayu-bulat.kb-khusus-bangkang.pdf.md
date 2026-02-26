# api.reports.kayu-bulat.kb-khusus-bangkang.pdf

- Method: `GET|POST|HEAD`
- URI: `/api/reports/kayu-bulat/kb-khusus-bangkang/pdf`
- Name: `api.reports.kayu-bulat.kb-khusus-bangkang.pdf`
- Action: `App\Http\Controllers\KbKhususBangkangController@download`
- Middleware: `api, App\Http\Middleware\AuthenticateReportJwtClaims`

## Contoh cURL

```bash
curl -X GET "http://localhost:8000/api/reports/kayu-bulat/kb-khusus-bangkang/pdf"
```
