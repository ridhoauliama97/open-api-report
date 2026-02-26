# api.reports.label-nyangkut.pdf

- Method: `GET|POST|HEAD`
- URI: `/api/reports/label-nyangkut/pdf`
- Name: `api.reports.label-nyangkut.pdf`
- Action: `App\Http\Controllers\LabelNyangkutController@download`
- Middleware: `api, App\Http\Middleware\AuthenticateReportJwtClaims`

## Contoh cURL

```bash
curl -X GET "http://localhost:8000/api/reports/label-nyangkut/pdf"
```
