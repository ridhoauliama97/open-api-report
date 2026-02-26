# api.reports.sawn-timber.st-sawmill-masuk-per-group.pdf

- Method: `GET|POST|HEAD`
- URI: `/api/reports/sawn-timber/st-sawmill-masuk-per-group/pdf`
- Name: `api.reports.sawn-timber.st-sawmill-masuk-per-group.pdf`
- Action: `App\Http\Controllers\StSawmillMasukPerGroupController@download`
- Middleware: `api, App\Http\Middleware\AuthenticateReportJwtClaims`

## Contoh cURL

```bash
curl -X GET "http://localhost:8000/api/reports/sawn-timber/st-sawmill-masuk-per-group/pdf"
```
