# api.reports.kayu-bulat.rekap-produktivitas-sawmill-rp.pdf

- Method: `GET|POST|HEAD`
- URI: `/api/reports/kayu-bulat/rekap-produktivitas-sawmill-rp/pdf`
- Name: `api.reports.kayu-bulat.rekap-produktivitas-sawmill-rp.pdf`
- Action: `App\Http\Controllers\RekapProduktivitasSawmillRpController@download`
- Middleware: `api, App\Http\Middleware\AuthenticateReportJwtClaims`

## Contoh cURL

```bash
curl -X GET "http://localhost:8000/api/reports/kayu-bulat/rekap-produktivitas-sawmill-rp/pdf"
```

