# api.reports.sawn-timber.penerimaan-st-dari-sawmill-kg.pdf

- Method: `GET|POST|HEAD`
- URI: `/api/reports/sawn-timber/penerimaan-st-dari-sawmill-kg/pdf`
- Name: `api.reports.sawn-timber.penerimaan-st-dari-sawmill-kg.pdf`
- Action: `App\Http\Controllers\PenerimaanStSawmillKgController@download`
- Middleware: `api, App\Http\Middleware\AuthenticateReportJwtClaims`

## Contoh cURL

```bash
curl -X GET "http://localhost:8000/api/reports/sawn-timber/penerimaan-st-dari-sawmill-kg/pdf"
```
