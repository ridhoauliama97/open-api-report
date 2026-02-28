# api.reports.dashboard-finger-joint.health

- Method: `POST`
- URI: `/api/reports/dashboard-finger-joint/health`
- Name: `api.reports.dashboard-finger-joint.health`
- Action: `App\Http\Controllers\DashboardFingerJointController@health`
- Middleware: `api, App\Http\Middleware\AuthenticateReportJwtClaims`

## Contoh cURL

```bash
curl -X POST "http://localhost:8000/api/reports/dashboard-finger-joint/health"
```
