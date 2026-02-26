# api.reports.sawn-timber.stock-st-basah.pdf

- Method: `GET|POST|HEAD`
- URI: `/api/reports/sawn-timber/stock-st-basah/pdf`
- Name: `api.reports.sawn-timber.stock-st-basah.pdf`
- Action: `App\Http\Controllers\StockSTBasahController@download`
- Middleware: `api, App\Http\Middleware\AuthenticateReportJwtClaims`

## Contoh cURL

```bash
curl -X GET "http://localhost:8000/api/reports/sawn-timber/stock-st-basah/pdf"
```
