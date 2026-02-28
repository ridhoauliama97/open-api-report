# api.reports.dashboard-s4s.pdf

- Method: `GET|POST|HEAD`
- URI: `/api/reports/dashboard-s4s/pdf`
- Name: `api.reports.dashboard-s4s.pdf`
- Action: `App\Http\Controllers\DashboardS4SController@download`
- Middleware: `api, App\Http\Middleware\AuthenticateReportJwtClaims`

## Contoh cURL

```bash
curl -X GET "http://localhost:8000/api/reports/dashboard-s4s/pdf"
```
