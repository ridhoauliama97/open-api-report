# api.reports.kayu-bulat.penerimaan-per-supplier-group.pdf

- Method: `GET|POST|HEAD`
- URI: `/api/reports/kayu-bulat/penerimaan-per-supplier-group/pdf`
- Name: `api.reports.kayu-bulat.penerimaan-per-supplier-group.pdf`
- Action: `App\Http\Controllers\PenerimaanKayuBulatPerSupplierGroupController@download`
- Middleware: `api, App\Http\Middleware\AuthenticateReportJwtClaims`

## Contoh cURL

```bash
curl -X GET "http://localhost:8000/api/reports/kayu-bulat/penerimaan-per-supplier-group/pdf"
```
