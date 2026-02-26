# api.reports.bahan-terpakai.health

- Method: `POST`
- URI: `/api/reports/bahan-terpakai/health`
- Name: `api.reports.bahan-terpakai.health`
- Action: `App\Http\Controllers\BahanTerpakaiController@health`
- Middleware: `api, App\Http\Middleware\AuthenticateReportJwtClaims`

## Contoh cURL

```bash
curl -X POST "http://localhost:8000/api/reports/bahan-terpakai/health"
```
