# api.reports.mutasi-finger-joint.pdf

- Method: `GET|POST|HEAD`
- URI: `/api/reports/mutasi-finger-joint/pdf`
- Name: `api.reports.mutasi-finger-joint.pdf`
- Action: `App\Http\Controllers\MutasiFingerJointController@download`
- Middleware: `api, App\Http\Middleware\AuthenticateReportJwtClaims`

## Contoh cURL

```bash
curl -X GET "http://localhost:8000/api/reports/mutasi-finger-joint/pdf"
```
