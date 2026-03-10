# api.reports.kayu-bulat.saldo-hidup-kg.pdf

- Method: `GET|POST|HEAD`
- URI: `/api/reports/kayu-bulat/saldo-hidup-kg/pdf`
- Name: `api.reports.kayu-bulat.saldo-hidup-kg.pdf`
- Action: `App\Http\Controllers\SaldoHidupKayuBulatKgController@download`
- Middleware: `api, App\Http\Middleware\AuthenticateReportJwtClaims`

## Contoh cURL

```bash
curl -X GET "http://localhost:8000/api/reports/kayu-bulat/saldo-hidup-kg/pdf"
```

