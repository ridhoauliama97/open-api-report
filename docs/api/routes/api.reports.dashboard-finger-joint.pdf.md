# api.reports.dashboard-finger-joint.pdf

- Method: `GET|POST|HEAD`
- URI: `/api/reports/dashboard-finger-joint/pdf`
- Name: `api.reports.dashboard-finger-joint.pdf`
- Action: `App\Http\Controllers\DashboardFingerJointController@download`
- Middleware: `api, App\Http\Middleware\AuthenticateReportJwtClaims`

## Contoh cURL

```bash
curl -X GET "http://localhost:8000/api/reports/dashboard-finger-joint/pdf"
```
