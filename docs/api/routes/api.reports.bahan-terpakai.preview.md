# api.reports.bahan-terpakai.preview

- Method: `POST`
- URI: `/api/reports/bahan-terpakai`
- Name: `api.reports.bahan-terpakai.preview`
- Action: `App\Http\Controllers\BahanTerpakaiController@preview`
- Middleware: `api, App\Http\Middleware\AuthenticateReportJwtClaims`

## Contoh cURL

```bash
curl -X POST "http://localhost:8000/api/reports/bahan-terpakai"
```
