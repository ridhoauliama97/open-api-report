# api.reports.dashboard-laminating.preview

- Method: `POST`
- URI: `/api/reports/dashboard-laminating`
- Name: `api.reports.dashboard-laminating.preview`
- Action: `App\Http\Controllers\DashboardLaminatingController@preview`
- Middleware: `api, App\Http\Middleware\AuthenticateReportJwtClaims`

## Contoh cURL

```bash
curl -X POST "http://localhost:8000/api/reports/dashboard-laminating"
```
