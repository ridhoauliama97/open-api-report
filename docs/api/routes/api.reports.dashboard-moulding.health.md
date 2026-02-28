# api.reports.dashboard-moulding.health

- Method: `POST`
- URI: `/api/reports/dashboard-moulding/health`
- Name: `api.reports.dashboard-moulding.health`
- Action: `App\Http\Controllers\DashboardMouldingController@health`
- Middleware: `api, App\Http\Middleware\AuthenticateReportJwtClaims`

## Contoh cURL

```bash
curl -X POST "http://localhost:8000/api/reports/dashboard-moulding/health"
```
