# api.reports.dashboard-moulding.pdf

- Method: `GET|POST|HEAD`
- URI: `/api/reports/dashboard-moulding/pdf`
- Name: `api.reports.dashboard-moulding.pdf`
- Action: `App\Http\Controllers\DashboardMouldingController@download`
- Middleware: `api, App\Http\Middleware\AuthenticateReportJwtClaims`

## Contoh cURL

```bash
curl -X GET "http://localhost:8000/api/reports/dashboard-moulding/pdf"
```
