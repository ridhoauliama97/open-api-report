# api.reports.kayu-bulat.hidup-per-group.pdf

- Method: `GET|POST|HEAD`
- URI: `/api/reports/kayu-bulat/hidup-per-group/pdf`
- Name: `api.reports.kayu-bulat.hidup-per-group.pdf`
- Action: `App\Http\Controllers\HidupKBPerGroupController@download`
- Middleware: `api, App\Http\Middleware\AuthenticateReportJwtClaims`

## Contoh cURL

```bash
curl -X GET "http://localhost:8000/api/reports/kayu-bulat/hidup-per-group/pdf"
```
