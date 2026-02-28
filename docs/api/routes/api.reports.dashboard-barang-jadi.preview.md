# api.reports.dashboard-barang-jadi.preview

- Method: `POST`
- URI: `/api/reports/dashboard-barang-jadi`
- Name: `api.reports.dashboard-barang-jadi.preview`
- Action: `App\Http\Controllers\DashboardBarangJadiController@preview`
- Middleware: `api, App\Http\Middleware\AuthenticateReportJwtClaims`

## Contoh cURL

```bash
curl -X POST "http://localhost:8000/api/reports/dashboard-barang-jadi"
```
