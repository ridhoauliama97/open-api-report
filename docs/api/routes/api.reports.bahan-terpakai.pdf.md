# api.reports.bahan-terpakai.pdf

- Method: `GET|POST|HEAD`
- URI: `/api/reports/bahan-terpakai/pdf`
- Name: `api.reports.bahan-terpakai.pdf`
- Action: `App\Http\Controllers\BahanTerpakaiController@download`
- Middleware: `api, App\Http\Middleware\AuthenticateReportJwtClaims`

## Contoh cURL

```bash
curl -X GET "http://localhost:8000/api/reports/bahan-terpakai/pdf"
```
