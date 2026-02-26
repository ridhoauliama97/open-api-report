# api.reports.kayu-bulat.balok-sudah-semprot.pdf

- Method: `GET|POST|HEAD`
- URI: `/api/reports/kayu-bulat/balok-sudah-semprot/pdf`
- Name: `api.reports.kayu-bulat.balok-sudah-semprot.pdf`
- Action: `App\Http\Controllers\BalokSudahSemprotController@download`
- Middleware: `api, App\Http\Middleware\AuthenticateReportJwtClaims`

## Contoh cURL

```bash
curl -X GET "http://localhost:8000/api/reports/kayu-bulat/balok-sudah-semprot/pdf"
```
