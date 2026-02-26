# api.reports.kayu-bulat.penerimaan-bulanan-per-supplier.pdf

- Method: `GET|POST|HEAD`
- URI: `/api/reports/kayu-bulat/penerimaan-bulanan-per-supplier/pdf`
- Name: `api.reports.kayu-bulat.penerimaan-bulanan-per-supplier.pdf`
- Action: `App\Http\Controllers\PenerimaanKayuBulatBulananPerSupplierController@download`
- Middleware: `api, App\Http\Middleware\AuthenticateReportJwtClaims`

## Contoh cURL

```bash
curl -X GET "http://localhost:8000/api/reports/kayu-bulat/penerimaan-bulanan-per-supplier/pdf"
```
