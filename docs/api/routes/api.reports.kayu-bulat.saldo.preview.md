# api.reports.kayu-bulat.saldo.preview

- Method: `POST`
- URI: `/api/reports/kayu-bulat/saldo`
- Name: `api.reports.kayu-bulat.saldo.preview`
- Action: `App\Http\Controllers\SaldoKayuBulatController@preview`
- Middleware: `api, App\Http\Middleware\AuthenticateReportJwtClaims`

## Contoh cURL

```bash
curl -X POST "http://localhost:8000/api/reports/kayu-bulat/saldo"
```
