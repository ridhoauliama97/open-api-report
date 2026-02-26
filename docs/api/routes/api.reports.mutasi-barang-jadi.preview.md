# api.reports.mutasi-barang-jadi.preview

- Method: `POST`
- URI: `/api/reports/mutasi-barang-jadi`
- Name: `api.reports.mutasi-barang-jadi.preview`
- Action: `App\Http\Controllers\MutasiBarangJadiController@preview`
- Middleware: `api, App\Http\Middleware\AuthenticateReportJwtClaims`

## Contoh cURL

```bash
curl -X POST "http://localhost:8000/api/reports/mutasi-barang-jadi"
```
