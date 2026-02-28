# api.reports.dashboard-sanding.health

- Method: `POST`
- URI: `/api/reports/dashboard-sanding/health`
- Name: `api.reports.dashboard-sanding.health`
- Action: `App\Http\Controllers\DashboardSandingController@health`
- Middleware: `api, App\Http\Middleware\AuthenticateReportJwtClaims`

## Contoh cURL

```bash
curl -X POST "http://localhost:8000/api/reports/dashboard-sanding/health"
```
