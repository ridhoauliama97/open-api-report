# api.reports.rangkuman-label-input.pdf

- Method: `GET|POST|HEAD`
- URI: `/api/reports/rangkuman-label-input/pdf`
- Name: `api.reports.rangkuman-label-input.pdf`
- Action: `App\Http\Controllers\RangkumanJlhLabelInputController@download`
- Middleware: `api, App\Http\Middleware\AuthenticateReportJwtClaims`

## Contoh cURL

```bash
curl -X GET "http://localhost:8000/api/reports/rangkuman-label-input/pdf"
```
