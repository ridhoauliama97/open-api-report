# api.reports.kayu-bulat.saldo.pdf

- Method: `GET|POST|HEAD`
- URI: `/api/reports/kayu-bulat/saldo/pdf`
- Name: `api.reports.kayu-bulat.saldo.pdf`
- Action: `App\Http\Controllers\SaldoKayuBulatController@download`
- Middleware: `api, App\Http\Middleware\AuthenticateReportJwtClaims`

## Contoh cURL

```bash
curl -X GET "http://localhost:8000/api/reports/kayu-bulat/saldo/pdf"
```
