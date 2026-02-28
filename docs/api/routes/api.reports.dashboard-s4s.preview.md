# api.reports.dashboard-s4s.preview

- Method: `POST`
- URI: `/api/reports/dashboard-s4s`
- Name: `api.reports.dashboard-s4s.preview`
- Action: `App\Http\Controllers\DashboardS4SController@preview`
- Middleware: `api, App\Http\Middleware\AuthenticateReportJwtClaims`

## Contoh cURL

```bash
curl -X POST "http://localhost:8000/api/reports/dashboard-s4s"
```
