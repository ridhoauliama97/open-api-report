# api.reports.kayu-bulat.supplier-intel.pdf

- Method: `GET|POST|HEAD`
- URI: `/api/reports/kayu-bulat/supplier-intel/pdf`
- Name: `api.reports.kayu-bulat.supplier-intel.pdf`
- Action: `App\Http\Controllers\SupplierIntelController@download`
- Middleware: `api, App\Http\Middleware\AuthenticateReportJwtClaims`

## Contoh cURL

```bash
curl -X GET "http://localhost:8000/api/reports/kayu-bulat/supplier-intel/pdf"
```


