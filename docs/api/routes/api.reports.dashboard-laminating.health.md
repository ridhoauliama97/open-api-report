# api.reports.dashboard-laminating.health

- Method: `POST`
- URI: `/api/reports/dashboard-laminating/health`
- Name: `api.reports.dashboard-laminating.health`
- Action: `App\Http\Controllers\DashboardLaminatingController@health`
- Middleware: `api, App\Http\Middleware\AuthenticateReportJwtClaims`

## Contoh cURL

```bash
curl -X POST "http://localhost:8000/api/reports/dashboard-laminating/health"
```
