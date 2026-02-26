# api.reports.kayu-bulat.timeline-kayu-bulat-bulanan.pdf

- Method: `GET|POST|HEAD`
- URI: `/api/reports/kayu-bulat/timeline-kayu-bulat-bulanan/pdf`
- Name: `api.reports.kayu-bulat.timeline-kayu-bulat-bulanan.pdf`
- Action: `App\Http\Controllers\TimelineKayuBulatBulananController@download`
- Middleware: `api, App\Http\Middleware\AuthenticateReportJwtClaims`

## Contoh cURL

```bash
curl -X GET "http://localhost:8000/api/reports/kayu-bulat/timeline-kayu-bulat-bulanan/pdf"
```
