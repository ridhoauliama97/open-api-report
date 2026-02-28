# api.reports.dashboard-barang-jadi.health

- Method: `POST`
- URI: `/api/reports/dashboard-barang-jadi/health`
- Name: `api.reports.dashboard-barang-jadi.health`
- Action: `App\Http\Controllers\DashboardBarangJadiController@health`
- Middleware: `api, App\Http\Middleware\AuthenticateReportJwtClaims`

## Contoh cURL

```bash
curl -X POST "http://localhost:8000/api/reports/dashboard-barang-jadi/health"
```
