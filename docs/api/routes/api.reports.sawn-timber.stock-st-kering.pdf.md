# api.reports.sawn-timber.stock-st-kering.pdf

- Method: `GET|POST|HEAD`
- URI: `/api/reports/sawn-timber/stock-st-kering/pdf`
- Name: `api.reports.sawn-timber.stock-st-kering.pdf`
- Action: `App\Http\Controllers\StockSTKeringController@download`
- Middleware: `api, App\Http\Middleware\AuthenticateReportJwtClaims`

## Contoh cURL

```bash
curl -X GET "http://localhost:8000/api/reports/sawn-timber/stock-st-kering/pdf"
```

