# api.reports.mutasi-barang-jadi.pdf

- Method: `GET|POST|HEAD`
- URI: `/api/reports/mutasi-barang-jadi/pdf`
- Name: `api.reports.mutasi-barang-jadi.pdf`
- Action: `App\Http\Controllers\MutasiBarangJadiController@download`
- Middleware: `api, App\Http\Middleware\AuthenticateReportJwtClaims`

## Contoh cURL

```bash
curl -X GET "http://localhost:8000/api/reports/mutasi-barang-jadi/pdf"
```
