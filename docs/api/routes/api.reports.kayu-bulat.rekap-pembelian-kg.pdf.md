# api.reports.kayu-bulat.rekap-pembelian-kg.pdf

- Method: `GET|POST|HEAD`
- URI: `/api/reports/kayu-bulat/rekap-pembelian-kg/pdf`
- Name: `api.reports.kayu-bulat.rekap-pembelian-kg.pdf`
- Action: `App\Http\Controllers\RekapPembelianKayuBulatKgController@download`
- Middleware: `api, App\Http\Middleware\AuthenticateReportJwtClaims`

## Contoh cURL

```bash
curl -X GET "http://localhost:8000/api/reports/kayu-bulat/rekap-pembelian-kg/pdf"
```

