# api.reports.sawn-timber.lembar-tally-hasil-sawmill.health

- Method: `POST`
- URI: `/api/reports/sawn-timber/lembar-tally-hasil-sawmill/health`
- Name: `api.reports.sawn-timber.lembar-tally-hasil-sawmill.health`
- Action: `App\Http\Controllers\LembarTallyHasilSawmillController@health`
- Middleware: `api, App\Http\Middleware\AuthenticateReportJwtClaims`

## Contoh cURL

```bash
curl -X POST "http://localhost:8000/api/reports/sawn-timber/lembar-tally-hasil-sawmill/health"
```
