# api.reports.dashboard-barang-jadi.pdf

- Method: `GET|POST|HEAD`
- URI: `/api/reports/dashboard-barang-jadi/pdf`
- Name: `api.reports.dashboard-barang-jadi.pdf`
- Action: `App\Http\Controllers\DashboardBarangJadiController@download`
- Middleware: `api, App\Http\Middleware\AuthenticateReportJwtClaims`

## Contoh cURL

```bash
curl -X GET "http://localhost:8000/api/reports/dashboard-barang-jadi/pdf"
```
