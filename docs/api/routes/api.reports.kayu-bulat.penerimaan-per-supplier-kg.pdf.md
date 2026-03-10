# api.reports.kayu-bulat.penerimaan-per-supplier-kg.pdf

- Method: `GET|POST|HEAD`
- URI: `/api/reports/kayu-bulat/penerimaan-per-supplier-kg/pdf`
- Name: `api.reports.kayu-bulat.penerimaan-per-supplier-kg.pdf`
- Action: `App\Http\Controllers\PenerimaanKayuBulatPerSupplierKgController@download`
- Middleware: `api, App\Http\Middleware\AuthenticateReportJwtClaims`

## Contoh cURL

```bash
curl -X GET "http://localhost:8000/api/reports/kayu-bulat/penerimaan-per-supplier-kg/pdf"
```

