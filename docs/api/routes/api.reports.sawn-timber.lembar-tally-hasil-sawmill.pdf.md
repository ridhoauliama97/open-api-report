# api.reports.sawn-timber.lembar-tally-hasil-sawmill.pdf

- Method: `GET|POST|HEAD`
- URI: `/api/reports/sawn-timber/lembar-tally-hasil-sawmill/pdf`
- Name: `api.reports.sawn-timber.lembar-tally-hasil-sawmill.pdf`
- Action: `App\Http\Controllers\LembarTallyHasilSawmillController@download`
- Middleware: `api, App\Http\Middleware\AuthenticateReportJwtClaims`

## Contoh cURL

```bash
curl -X GET "http://localhost:8000/api/reports/sawn-timber/lembar-tally-hasil-sawmill/pdf"
```
