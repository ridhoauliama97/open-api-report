# api.reports.dashboard-cross-cut-akhir.pdf

- Method: `GET|POST|HEAD`
- URI: `/api/reports/dashboard-cross-cut-akhir/pdf`
- Name: `api.reports.dashboard-cross-cut-akhir.pdf`
- Action: `App\Http\Controllers\DashboardCrossCutAkhirController@download`
- Middleware: `api, App\Http\Middleware\AuthenticateReportJwtClaims`

## Contoh cURL

```bash
curl -X GET "http://localhost:8000/api/reports/dashboard-cross-cut-akhir/pdf"
```
