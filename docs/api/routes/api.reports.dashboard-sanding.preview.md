# api.reports.dashboard-sanding.preview

- Method: `POST`
- URI: `/api/reports/dashboard-sanding`
- Name: `api.reports.dashboard-sanding.preview`
- Action: `App\Http\Controllers\DashboardSandingController@preview`
- Middleware: `api, App\Http\Middleware\AuthenticateReportJwtClaims`

## Contoh cURL

```bash
curl -X POST "http://localhost:8000/api/reports/dashboard-sanding"
```
