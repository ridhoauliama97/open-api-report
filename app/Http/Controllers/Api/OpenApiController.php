<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class OpenApiController extends Controller
{
    /**
     * Display the default page for this resource.
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'openapi' => '3.0.3',
            'info' => [
                'title' => 'Sales Report API',
                'version' => '1.0.0',
                'description' => 'API untuk autentikasi JWT, preview data, dan generate laporan PDF (penjualan, mutasi cross cut, mutasi barang jadi) berdasarkan rentang tanggal.',
            ],
            'servers' => [
                ['url' => url('/')],
            ],
            'paths' => [
                '/api/auth/register' => [
                    'post' => [
                        'summary' => 'Registrasi user baru',
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/AuthRegisterRequest',
                                    ],
                                ],
                            ],
                        ],
                        'responses' => [
                            '201' => [
                                'description' => 'Registrasi berhasil',
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            '$ref' => '#/components/schemas/AuthTokenResponse',
                                        ],
                                    ],
                                ],
                            ],
                            '422' => [
                                'description' => 'Validasi gagal',
                            ],
                        ],
                    ],
                ],
                '/api/auth/login' => [
                    'post' => [
                        'summary' => 'Login user',
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/AuthLoginRequest',
                                    ],
                                ],
                            ],
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Login berhasil',
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            '$ref' => '#/components/schemas/AuthTokenResponse',
                                        ],
                                    ],
                                ],
                            ],
                            '401' => [
                                'description' => 'Kredensial tidak valid',
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            '$ref' => '#/components/schemas/MessageResponse',
                                        ],
                                        'example' => [
                                            'message' => 'Email atau password tidak valid.',
                                        ],
                                    ],
                                ],
                            ],
                            '422' => [
                                'description' => 'Validasi gagal',
                            ],
                        ],
                    ],
                ],
                '/api/auth/logout' => [
                    'post' => [
                        'summary' => 'Logout user (invalidate token)',
                        'description' => 'Kirim token melalui Authorization Bearer atau field `token` di body.',
                        'security' => [
                            ['bearerAuth' => []],
                        ],
                        'requestBody' => [
                            'required' => false,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/AuthTokenRequest',
                                    ],
                                ],
                            ],
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Logout berhasil',
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            '$ref' => '#/components/schemas/MessageResponse',
                                        ],
                                    ],
                                ],
                            ],
                            '401' => [
                                'description' => 'Token tidak valid atau tidak ditemukan',
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            '$ref' => '#/components/schemas/MessageResponse',
                                        ],
                                        'examples' => [
                                            'token_missing' => [
                                                'value' => [
                                                    'message' => 'Token tidak ditemukan. Kirim Authorization: Bearer <token> atau field token.',
                                                ],
                                            ],
                                            'token_invalid' => [
                                                'value' => [
                                                    'message' => 'Token tidak valid atau sudah kedaluwarsa.',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                '/api/auth/refresh' => [
                    'post' => [
                        'summary' => 'Refresh access token',
                        'description' => 'Kirim token melalui Authorization Bearer atau field `token` di body.',
                        'security' => [
                            ['bearerAuth' => []],
                        ],
                        'requestBody' => [
                            'required' => false,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/AuthTokenRequest',
                                    ],
                                ],
                            ],
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Token berhasil diperbarui',
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            '$ref' => '#/components/schemas/AuthTokenResponse',
                                        ],
                                    ],
                                ],
                            ],
                            '401' => [
                                'description' => 'Token tidak valid atau tidak ditemukan',
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            '$ref' => '#/components/schemas/MessageResponse',
                                        ],
                                        'examples' => [
                                            'token_missing' => [
                                                'value' => [
                                                    'message' => 'Token tidak ditemukan. Kirim Authorization: Bearer <token> atau field token.',
                                                ],
                                            ],
                                            'token_invalid' => [
                                                'value' => [
                                                    'message' => 'Token tidak valid atau sudah kedaluwarsa.',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                '/api/auth/me' => [
                    'get' => [
                        'summary' => 'Data user yang sedang login',
                        'security' => [
                            ['bearerAuth' => []],
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Data user',
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            '$ref' => '#/components/schemas/AuthUserResponse',
                                        ],
                                    ],
                                ],
                            ],
                            '401' => [
                                'description' => 'Unauthenticated',
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            '$ref' => '#/components/schemas/MessageResponse',
                                        ],
                                        'example' => [
                                            'message' => 'Unauthenticated.',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                '/api/reports/sales' => [
                    'post' => [
                        'summary' => 'Preview data laporan penjualan',
                        'security' => [
                            ['bearerAuth' => []],
                        ],
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/MutasiBarangJadiRequest',
                                    ],
                                ],
                                'application/x-www-form-urlencoded' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/MutasiBarangJadiRequest',
                                    ],
                                ],
                            ],
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Data preview laporan',
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            '$ref' => '#/components/schemas/MutasiBarangJadiPreviewResponse',
                                        ],
                                    ],
                                ],
                            ],
                            '422' => [
                                'description' => 'Validasi gagal',
                            ],
                            '401' => [
                                'description' => 'Unauthenticated',
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            '$ref' => '#/components/schemas/MessageResponse',
                                        ],
                                        'example' => [
                                            'message' => 'Unauthenticated.',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                '/api/reports/sales/pdf' => [
                    'post' => [
                        'summary' => 'Generate laporan penjualan PDF',
                        'security' => [
                            ['bearerAuth' => []],
                        ],
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/MutasiBarangJadiRequest',
                                    ],
                                ],
                                'application/x-www-form-urlencoded' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/MutasiBarangJadiRequest',
                                    ],
                                ],
                            ],
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'PDF berhasil dibuat',
                                'content' => [
                                    'application/pdf' => [
                                        'schema' => [
                                            'type' => 'string',
                                            'format' => 'binary',
                                        ],
                                    ],
                                ],
                            ],
                            '422' => [
                                'description' => 'Validasi gagal',
                            ],
                            '401' => [
                                'description' => 'Unauthenticated',
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            '$ref' => '#/components/schemas/MessageResponse',
                                        ],
                                        'example' => [
                                            'message' => 'Unauthenticated.',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                '/api/reports/mutasi-barang-jadi' => [
                    'post' => [
                        'summary' => 'Preview data laporan mutasi barang jadi',
                        'security' => [
                            ['bearerAuth' => []],
                        ],
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/SalesReportRequest',
                                    ],
                                ],
                                'application/x-www-form-urlencoded' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/SalesReportRequest',
                                    ],
                                ],
                            ],
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Data preview laporan',
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            '$ref' => '#/components/schemas/SalesReportPreviewResponse',
                                        ],
                                    ],
                                ],
                            ],
                            '422' => [
                                'description' => 'Validasi gagal',
                            ],
                            '401' => [
                                'description' => 'Unauthenticated',
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            '$ref' => '#/components/schemas/MessageResponse',
                                        ],
                                        'example' => [
                                            'message' => 'Unauthenticated.',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                '/api/reports/mutasi-barang-jadi/pdf' => [
                    'post' => [
                        'summary' => 'Generate laporan mutasi barang jadi PDF',
                        'security' => [
                            ['bearerAuth' => []],
                        ],
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/SalesReportRequest',
                                    ],
                                ],
                                'application/x-www-form-urlencoded' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/SalesReportRequest',
                                    ],
                                ],
                            ],
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'PDF berhasil dibuat',
                                'content' => [
                                    'application/pdf' => [
                                        'schema' => [
                                            'type' => 'string',
                                            'format' => 'binary',
                                        ],
                                    ],
                                ],
                            ],
                            '422' => [
                                'description' => 'Validasi gagal',
                            ],
                            '401' => [
                                'description' => 'Unauthenticated',
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            '$ref' => '#/components/schemas/MessageResponse',
                                        ],
                                        'example' => [
                                            'message' => 'Unauthenticated.',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                '/api/reports/mutasi-barang-jadi/health' => [
                    'post' => [
                        'summary' => 'Cek kesehatan struktur output SP mutasi barang jadi',
                        'security' => [
                            ['bearerAuth' => []],
                        ],
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/MutasiBarangJadiRequest',
                                    ],
                                ],
                                'application/x-www-form-urlencoded' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/MutasiBarangJadiRequest',
                                    ],
                                ],
                            ],
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Hasil pemeriksaan struktur output',
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            '$ref' => '#/components/schemas/MutasiBarangJadiHealthResponse',
                                        ],
                                    ],
                                ],
                            ],
                            '422' => [
                                'description' => 'Validasi gagal',
                            ],
                            '401' => [
                                'description' => 'Unauthenticated',
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            '$ref' => '#/components/schemas/MessageResponse',
                                        ],
                                        'example' => [
                                            'message' => 'Unauthenticated.',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'components' => [
                'securitySchemes' => [
                    'bearerAuth' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'bearerFormat' => 'JWT',
                    ],
                ],
                'schemas' => [
                    'AuthRegisterRequest' => [
                        'type' => 'object',
                        'required' => ['name', 'email', 'password'],
                        'properties' => [
                            'name' => [
                                'type' => 'string',
                                'example' => 'John Doe',
                            ],
                            'email' => [
                                'type' => 'string',
                                'format' => 'email',
                                'example' => 'john@example.com',
                            ],
                            'password' => [
                                'type' => 'string',
                                'format' => 'password',
                                'example' => 'secret123',
                            ],
                            'password_confirmation' => [
                                'type' => 'string',
                                'format' => 'password',
                                'example' => 'secret123',
                                'nullable' => true,
                            ],
                        ],
                    ],
                    'AuthLoginRequest' => [
                        'type' => 'object',
                        'required' => ['email', 'password'],
                        'properties' => [
                            'email' => [
                                'type' => 'string',
                                'format' => 'email',
                                'example' => 'john@example.com',
                            ],
                            'password' => [
                                'type' => 'string',
                                'format' => 'password',
                                'example' => 'secret123',
                            ],
                        ],
                    ],
                    'AuthTokenRequest' => [
                        'type' => 'object',
                        'properties' => [
                            'token' => [
                                'type' => 'string',
                                'example' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...',
                            ],
                        ],
                    ],
                    'AuthTokenResponse' => [
                        'type' => 'object',
                        'properties' => [
                            'access_token' => [
                                'type' => 'string',
                                'example' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...',
                            ],
                            'token_type' => [
                                'type' => 'string',
                                'example' => 'bearer',
                            ],
                            'expires_in' => [
                                'type' => 'integer',
                                'example' => 3600,
                            ],
                            'user' => [
                                '$ref' => '#/components/schemas/User',
                            ],
                        ],
                    ],
                    'AuthUserResponse' => [
                        'type' => 'object',
                        'properties' => [
                            'user' => [
                                '$ref' => '#/components/schemas/User',
                            ],
                        ],
                    ],
                    'MessageResponse' => [
                        'type' => 'object',
                        'properties' => [
                            'message' => [
                                'type' => 'string',
                                'example' => 'Logout berhasil.',
                            ],
                        ],
                    ],
                    'User' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => ['type' => 'integer', 'example' => 1],
                            'name' => ['type' => 'string', 'example' => 'John Doe'],
                            'email' => ['type' => 'string', 'format' => 'email', 'example' => 'john@example.com'],
                            'email_verified_at' => ['type' => 'string', 'format' => 'date-time', 'nullable' => true],
                            'created_at' => ['type' => 'string', 'format' => 'date-time'],
                            'updated_at' => ['type' => 'string', 'format' => 'date-time'],
                        ],
                    ],
                    'SalesReportRequest' => [
                        'type' => 'object',
                        'required' => ['start_date', 'end_date'],
                        'properties' => [
                            'start_date' => [
                                'type' => 'string',
                                'format' => 'date',
                                'example' => '2026-01-01',
                            ],
                            'end_date' => [
                                'type' => 'string',
                                'format' => 'date',
                                'example' => '2026-01-31',
                            ],
                        ],
                    ],
                    'SalesReportPreviewResponse' => [
                        'type' => 'object',
                        'properties' => [
                            'message' => [
                                'type' => 'string',
                                'example' => 'Preview laporan berhasil diambil.',
                            ],
                            'meta' => [
                                'type' => 'object',
                                'properties' => [
                                    'start_date' => ['type' => 'string', 'format' => 'date'],
                                    'end_date' => ['type' => 'string', 'format' => 'date'],
                                    'total_rows' => ['type' => 'integer', 'example' => 100],
                                    'amount_field' => ['type' => 'string', 'example' => 'total'],
                                    'grand_total' => ['type' => 'number', 'example' => 12345678.9],
                                ],
                            ],
                            'data' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'object',
                                    'additionalProperties' => true,
                                ],
                            ],
                        ],
                    ],
                    'MutasiBarangJadiRequest' => [
                        'type' => 'object',
                        'required' => ['TglAwal', 'TglAkhir'],
                        'properties' => [
                            'TglAwal' => [
                                'type' => 'string',
                                'format' => 'date',
                                'example' => '2026-01-01',
                            ],
                            'TglAkhir' => [
                                'type' => 'string',
                                'format' => 'date',
                                'example' => '2026-01-31',
                            ],
                        ],
                    ],
                    'MutasiBarangJadiRow' => [
                        'type' => 'object',
                        'properties' => [
                            'Jenis' => ['type' => 'string', 'example' => 'BJ JABON FJLB A/A'],
                            'Awal' => ['type' => 'number', 'example' => 4.2935],
                            'Masuk' => ['type' => 'number', 'example' => 438.0548],
                            'AdjOutput' => ['type' => 'number', 'example' => 0],
                            'BSOutput' => ['type' => 'number', 'example' => 159.5689],
                            'AdjInput' => ['type' => 'number', 'example' => 0],
                            'BSInput' => ['type' => 'number', 'example' => 159.57],
                            'Keluar' => ['type' => 'number', 'example' => 9.2471],
                            'Jual' => ['type' => 'number', 'example' => 401.6065],
                            'MLDInput' => ['type' => 'number', 'example' => 0],
                            'LMTInput' => ['type' => 'number', 'example' => 0.0857],
                            'CCAInput' => ['type' => 'number', 'example' => 2.3059],
                            'SANDInput' => ['type' => 'number', 'example' => 0],
                            'Akhir' => ['type' => 'number', 'example' => 29.102],
                        ],
                    ],
                    'MutasiBarangJadiPreviewResponse' => [
                        'type' => 'object',
                        'properties' => [
                            'message' => [
                                'type' => 'string',
                                'example' => 'Preview laporan berhasil diambil.',
                            ],
                            'meta' => [
                                'type' => 'object',
                                'properties' => [
                                    'start_date' => ['type' => 'string', 'format' => 'date'],
                                    'end_date' => ['type' => 'string', 'format' => 'date'],
                                    'TglAwal' => ['type' => 'string', 'format' => 'date'],
                                    'TglAkhir' => ['type' => 'string', 'format' => 'date'],
                                    'total_rows' => ['type' => 'integer', 'example' => 14],
                                    'column_order' => [
                                        'type' => 'array',
                                        'items' => ['type' => 'string'],
                                        'example' => ['Jenis', 'Awal', 'Masuk', 'AdjOutput', 'BSOutput', 'AdjInput', 'BSInput', 'Keluar', 'Jual', 'MLDInput', 'LMTInput', 'CCAInput', 'SANDInput', 'Akhir'],
                                    ],
                                ],
                            ],
                            'data' => [
                                'type' => 'array',
                                'items' => [
                                    '$ref' => '#/components/schemas/MutasiBarangJadiRow',
                                ],
                            ],
                        ],
                    ],
                    'MutasiBarangJadiHealthResponse' => [
                        'type' => 'object',
                        'properties' => [
                            'message' => [
                                'type' => 'string',
                                'example' => 'Struktur output SP_Mutasi_BarangJadi valid.',
                            ],
                            'meta' => [
                                'type' => 'object',
                                'properties' => [
                                    'start_date' => ['type' => 'string', 'format' => 'date'],
                                    'end_date' => ['type' => 'string', 'format' => 'date'],
                                    'TglAwal' => ['type' => 'string', 'format' => 'date'],
                                    'TglAkhir' => ['type' => 'string', 'format' => 'date'],
                                ],
                            ],
                            'health' => [
                                'type' => 'object',
                                'properties' => [
                                    'is_healthy' => ['type' => 'boolean', 'example' => true],
                                    'expected_columns' => ['type' => 'array', 'items' => ['type' => 'string']],
                                    'detected_columns' => ['type' => 'array', 'items' => ['type' => 'string']],
                                    'missing_columns' => ['type' => 'array', 'items' => ['type' => 'string']],
                                    'extra_columns' => ['type' => 'array', 'items' => ['type' => 'string']],
                                    'row_count' => ['type' => 'integer', 'example' => 14],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }
}
