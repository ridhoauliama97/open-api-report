# api.reports.kayu-bulat.umur-kayu-bulat-rambung.pdf

- Method: `GET|POST|HEAD`
- URI: `/api/reports/kayu-bulat/umur-kayu-bulat-rambung/pdf`
- Name: `api.reports.kayu-bulat.umur-kayu-bulat-rambung.pdf`
- Action: `App\Http\Controllers\UmurKayuBulatRambungController@download`
- Middleware: `api, App\Http\Middleware\AuthenticateReportJwtClaims`

## Contoh cURL

```bash
curl -X GET "http://localhost:8000/api/reports/kayu-bulat/umur-kayu-bulat-rambung/pdf"
```
