# api.auth.me

- Method: `GET|HEAD`
- URI: `/api/auth/me`
- Name: `api.auth.me`
- Action: `App\Http\Controllers\Api\AuthController@me`
- Middleware: `api, App\Http\Middleware\AuthenticateReportJwtClaims`

## Contoh cURL

```bash
curl -X GET "http://localhost:8000/api/auth/me"
```
