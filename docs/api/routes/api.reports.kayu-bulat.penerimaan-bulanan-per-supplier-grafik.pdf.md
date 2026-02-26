# api.reports.kayu-bulat.penerimaan-bulanan-per-supplier-grafik.pdf

- Method: `GET|POST|HEAD`
- URI: `/api/reports/kayu-bulat/penerimaan-bulanan-per-supplier-grafik/pdf`
- Name: `api.reports.kayu-bulat.penerimaan-bulanan-per-supplier-grafik.pdf`
- Action: `App\Http\Controllers\PenerimaanKayuBulatPerSupplierBulananGrafikController@download`
- Middleware: `api, App\Http\Middleware\AuthenticateReportJwtClaims`

## Contoh cURL

```bash
curl -X GET "http://localhost:8000/api/reports/kayu-bulat/penerimaan-bulanan-per-supplier-grafik/pdf"
```
