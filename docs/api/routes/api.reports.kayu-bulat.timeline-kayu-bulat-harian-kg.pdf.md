# api.reports.kayu-bulat.timeline-kayu-bulat-harian-kg.pdf

- Method: `GET|POST|HEAD`
- URI: `/api/reports/kayu-bulat/timeline-kayu-bulat-harian-kg/pdf`
- Name: `api.reports.kayu-bulat.timeline-kayu-bulat-harian-kg.pdf`
- Action: `App\Http\Controllers\TimelineKayuBulatHarianKgController@download`
- Middleware: `api, App\Http\Middleware\AuthenticateReportJwtClaims`

## Contoh cURL

```bash
curl -X GET "http://localhost:8000/api/reports/kayu-bulat/timeline-kayu-bulat-harian-kg/pdf"
```

