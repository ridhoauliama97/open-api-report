# api.reports.kayu-bulat.timeline-kayu-bulat-harian.pdf

- Method: `GET|POST|HEAD`
- URI: `/api/reports/kayu-bulat/timeline-kayu-bulat-harian/pdf`
- Name: `api.reports.kayu-bulat.timeline-kayu-bulat-harian.pdf`
- Action: `App\Http\Controllers\TimelineKayuBulatHarianController@download`
- Middleware: `api, App\Http\Middleware\AuthenticateReportJwtClaims`

## Contoh cURL

```bash
curl -X GET "http://localhost:8000/api/reports/kayu-bulat/timeline-kayu-bulat-harian/pdf"
```
