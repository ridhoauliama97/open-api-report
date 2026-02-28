# api.reports.dashboard-s4s-v2.pdf

- Method: `GET|POST|HEAD`
- URI: `/api/reports/dashboard-s4s-v2/pdf`
- Name: `api.reports.dashboard-s4s-v2.pdf`
- Action: `App\Http\Controllers\DashboardS4SV2Controller@download`
- Middleware: `api, App\Http\Middleware\AuthenticateReportJwtClaims`

## Contoh cURL

```bash
curl -X GET "http://localhost:8000/api/reports/dashboard-s4s-v2/pdf"
```
