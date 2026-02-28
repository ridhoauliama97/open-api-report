# api.reports.dashboard-sanding.pdf

- Method: `GET|POST|HEAD`
- URI: `/api/reports/dashboard-sanding/pdf`
- Name: `api.reports.dashboard-sanding.pdf`
- Action: `App\Http\Controllers\DashboardSandingController@download`
- Middleware: `api, App\Http\Middleware\AuthenticateReportJwtClaims`

## Contoh cURL

```bash
curl -X GET "http://localhost:8000/api/reports/dashboard-sanding/pdf"
```
