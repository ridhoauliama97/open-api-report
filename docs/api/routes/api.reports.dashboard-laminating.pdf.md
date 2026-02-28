# api.reports.dashboard-laminating.pdf

- Method: `GET|POST|HEAD`
- URI: `/api/reports/dashboard-laminating/pdf`
- Name: `api.reports.dashboard-laminating.pdf`
- Action: `App\Http\Controllers\DashboardLaminatingController@download`
- Middleware: `api, App\Http\Middleware\AuthenticateReportJwtClaims`

## Contoh cURL

```bash
curl -X GET "http://localhost:8000/api/reports/dashboard-laminating/pdf"
```
