# api.reports.mutasi-barang-jadi.health

- Method: `POST`
- URI: `/api/reports/mutasi-barang-jadi/health`
- Name: `api.reports.mutasi-barang-jadi.health`
- Action: `App\Http\Controllers\MutasiBarangJadiController@health`
- Middleware: `api, App\Http\Middleware\AuthenticateReportJwtClaims`

## Contoh cURL

```bash
curl -X POST "http://localhost:8000/api/reports/mutasi-barang-jadi/health"
```
