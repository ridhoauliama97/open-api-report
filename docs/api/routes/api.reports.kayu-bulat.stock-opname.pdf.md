# api.reports.kayu-bulat.stock-opname.pdf

- Method: `GET|POST|HEAD`
- URI: `/api/reports/kayu-bulat/stock-opname/pdf`
- Name: `api.reports.kayu-bulat.stock-opname.pdf`
- Action: `App\Http\Controllers\StockOpnameKayuBulatController@download`
- Middleware: `api, App\Http\Middleware\AuthenticateReportJwtClaims`

## Contoh cURL

```bash
curl -X GET "http://localhost:8000/api/reports/kayu-bulat/stock-opname/pdf"
```
