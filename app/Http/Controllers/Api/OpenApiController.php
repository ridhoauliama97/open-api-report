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
                'title' => 'Open API Report',
                'version' => '1.0.0',
                'description' => 'API autentikasi JWT dan laporan mutasi barang jadi/finger joint berbasis rentang tanggal.',
            ],
            'servers' => [
                ['url' => url('/')],
            ],
            'paths' => [
                '/api/auth/register' => [
                    'post' => [
                        'summary' => 'Registrasi user baru',
                        'requestBody' => [
                            'required' => false,
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
                            'required' => false,
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
                        'security' => [
                            ['bearerAuth' => []],
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
                            ],
                        ],
                    ],
                ],
                '/api/auth/refresh' => [
                    'post' => [
                        'summary' => 'Refresh access token',
                        'security' => [
                            ['bearerAuth' => []],
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
                            'required' => false,
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
                            ],
                        ],
                    ],
                ],
                '/api/reports/mutasi-finger-joint' => [
                    'post' => [
                        'summary' => 'Preview data laporan mutasi finger joint',
                        'security' => [
                            ['bearerAuth' => []],
                        ],
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/MutasiFingerJointRequest',
                                    ],
                                ],
                                'application/x-www-form-urlencoded' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/MutasiFingerJointRequest',
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
                                            '$ref' => '#/components/schemas/MutasiFingerJointPreviewResponse',
                                        ],
                                    ],
                                ],
                            ],
                            '422' => [
                                'description' => 'Validasi gagal',
                            ],
                            '401' => [
                                'description' => 'Unauthenticated',
                            ],
                        ],
                    ],
                ],
                '/api/reports/mutasi-finger-joint/pdf' => [
                    'post' => [
                        'summary' => 'Generate laporan mutasi finger joint PDF',
                        'security' => [
                            ['bearerAuth' => []],
                        ],
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/MutasiFingerJointRequest',
                                    ],
                                ],
                                'application/x-www-form-urlencoded' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/MutasiFingerJointRequest',
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
                            ],
                        ],
                    ],
                ],
                '/api/reports/mutasi-finger-joint/health' => [
                    'post' => [
                        'summary' => 'Cek kesehatan struktur output SP mutasi finger joint',
                        'security' => [
                            ['bearerAuth' => []],
                        ],
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/MutasiFingerJointRequest',
                                    ],
                                ],
                                'application/x-www-form-urlencoded' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/MutasiFingerJointRequest',
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
                                            '$ref' => '#/components/schemas/MutasiFingerJointHealthResponse',
                                        ],
                                    ],
                                ],
                            ],
                            '422' => [
                                'description' => 'Validasi gagal',
                            ],
                            '401' => [
                                'description' => 'Unauthenticated',
                            ],
                        ],
                    ],
                ],
                '/api/reports/mutasi-moulding' => [
                    'post' => [
                        'summary' => 'Preview data laporan mutasi moulding',
                        'security' => [
                            ['bearerAuth' => []],
                        ],
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/MutasiMouldingRequest',
                                    ],
                                ],
                                'application/x-www-form-urlencoded' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/MutasiMouldingRequest',
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
                                            '$ref' => '#/components/schemas/MutasiMouldingPreviewResponse',
                                        ],
                                    ],
                                ],
                            ],
                            '422' => [
                                'description' => 'Validasi gagal',
                            ],
                            '401' => [
                                'description' => 'Unauthenticated',
                            ],
                        ],
                    ],
                ],
                '/api/reports/mutasi-moulding/pdf' => [
                    'post' => [
                        'summary' => 'Generate laporan mutasi moulding PDF',
                        'security' => [
                            ['bearerAuth' => []],
                        ],
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/MutasiMouldingRequest',
                                    ],
                                ],
                                'application/x-www-form-urlencoded' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/MutasiMouldingRequest',
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
                            ],
                        ],
                    ],
                ],
                '/api/reports/mutasi-moulding/health' => [
                    'post' => [
                        'summary' => 'Cek kesehatan struktur output SP mutasi moulding',
                        'security' => [
                            ['bearerAuth' => []],
                        ],
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/MutasiMouldingRequest',
                                    ],
                                ],
                                'application/x-www-form-urlencoded' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/MutasiMouldingRequest',
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
                                            '$ref' => '#/components/schemas/MutasiMouldingHealthResponse',
                                        ],
                                    ],
                                ],
                            ],
                            '422' => [
                                'description' => 'Validasi gagal',
                            ],
                            '401' => [
                                'description' => 'Unauthenticated',
                            ],
                        ],
                    ],
                ],
                '/api/reports/mutasi-s4s' => [
                    'post' => [
                        'summary' => 'Preview data laporan mutasi s4s',
                        'security' => [
                            ['bearerAuth' => []],
                        ],
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/MutasiS4SRequest',
                                    ],
                                ],
                                'application/x-www-form-urlencoded' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/MutasiS4SRequest',
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
                                            '$ref' => '#/components/schemas/MutasiS4SPreviewResponse',
                                        ],
                                    ],
                                ],
                            ],
                            '422' => [
                                'description' => 'Validasi gagal',
                            ],
                            '401' => [
                                'description' => 'Unauthenticated',
                            ],
                        ],
                    ],
                ],
                '/api/reports/mutasi-s4s/pdf' => [
                    'post' => [
                        'summary' => 'Generate laporan mutasi s4s PDF',
                        'security' => [
                            ['bearerAuth' => []],
                        ],
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/MutasiS4SRequest',
                                    ],
                                ],
                                'application/x-www-form-urlencoded' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/MutasiS4SRequest',
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
                            ],
                        ],
                    ],
                ],
                '/api/reports/mutasi-s4s/health' => [
                    'post' => [
                        'summary' => 'Cek kesehatan struktur output SP mutasi s4s',
                        'security' => [
                            ['bearerAuth' => []],
                        ],
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/MutasiS4SRequest',
                                    ],
                                ],
                                'application/x-www-form-urlencoded' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/MutasiS4SRequest',
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
                                            '$ref' => '#/components/schemas/MutasiS4SHealthResponse',
                                        ],
                                    ],
                                ],
                            ],
                            '422' => [
                                'description' => 'Validasi gagal',
                            ],
                            '401' => [
                                'description' => 'Unauthenticated',
                            ],
                        ],
                    ],
                ],
                '/api/reports/mutasi-kayu-bulat' => [
                    'post' => [
                        'summary' => 'Preview data laporan mutasi kayu bulat',
                        'security' => [
                            ['bearerAuth' => []],
                        ],
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/MutasiKayuBulatRequest',
                                    ],
                                ],
                                'application/x-www-form-urlencoded' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/MutasiKayuBulatRequest',
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
                                            '$ref' => '#/components/schemas/MutasiKayuBulatPreviewResponse',
                                        ],
                                    ],
                                ],
                            ],
                            '422' => [
                                'description' => 'Validasi gagal',
                            ],
                            '401' => [
                                'description' => 'Unauthenticated',
                            ],
                        ],
                    ],
                ],
                '/api/reports/mutasi-kayu-bulat/pdf' => [
                    'post' => [
                        'summary' => 'Generate laporan mutasi kayu bulat PDF',
                        'security' => [
                            ['bearerAuth' => []],
                        ],
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/MutasiKayuBulatRequest',
                                    ],
                                ],
                                'application/x-www-form-urlencoded' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/MutasiKayuBulatRequest',
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
                            ],
                        ],
                    ],
                ],
                '/api/reports/mutasi-kayu-bulat/health' => [
                    'post' => [
                        'summary' => 'Cek kesehatan struktur output SP mutasi kayu bulat',
                        'security' => [
                            ['bearerAuth' => []],
                        ],
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/MutasiKayuBulatRequest',
                                    ],
                                ],
                                'application/x-www-form-urlencoded' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/MutasiKayuBulatRequest',
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
                                            '$ref' => '#/components/schemas/MutasiKayuBulatHealthResponse',
                                        ],
                                    ],
                                ],
                            ],
                            '422' => [
                                'description' => 'Validasi gagal',
                            ],
                            '401' => [
                                'description' => 'Unauthenticated',
                            ],
                        ],
                    ],
                ],
                '/api/reports/mutasi-kayu-bulat-kgv2' => [
                    'post' => [
                        'summary' => 'Preview data laporan mutasi kayu bulat kgv2',
                        'security' => [
                            ['bearerAuth' => []],
                        ],
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/MutasiKayuBulatKGV2Request',
                                    ],
                                ],
                                'application/x-www-form-urlencoded' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/MutasiKayuBulatKGV2Request',
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
                                            '$ref' => '#/components/schemas/MutasiKayuBulatKGV2PreviewResponse',
                                        ],
                                    ],
                                ],
                            ],
                            '422' => [
                                'description' => 'Validasi gagal',
                            ],
                            '401' => [
                                'description' => 'Unauthenticated',
                            ],
                        ],
                    ],
                ],
                '/api/reports/mutasi-kayu-bulat-kgv2/pdf' => [
                    'post' => [
                        'summary' => 'Generate laporan mutasi kayu bulat kgv2 PDF',
                        'security' => [
                            ['bearerAuth' => []],
                        ],
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/MutasiKayuBulatKGV2Request',
                                    ],
                                ],
                                'application/x-www-form-urlencoded' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/MutasiKayuBulatKGV2Request',
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
                            ],
                        ],
                    ],
                ],
                '/api/reports/mutasi-kayu-bulat-kgv2/health' => [
                    'post' => [
                        'summary' => 'Cek kesehatan struktur output SP mutasi kayu bulat kgv2',
                        'security' => [
                            ['bearerAuth' => []],
                        ],
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/MutasiKayuBulatKGV2Request',
                                    ],
                                ],
                                'application/x-www-form-urlencoded' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/MutasiKayuBulatKGV2Request',
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
                                            '$ref' => '#/components/schemas/MutasiKayuBulatKGV2HealthResponse',
                                        ],
                                    ],
                                ],
                            ],
                            '422' => [
                                'description' => 'Validasi gagal',
                            ],
                            '401' => [
                                'description' => 'Unauthenticated',
                            ],
                        ],
                    ],
                ],
                '/api/reports/mutasi-kayu-bulat-kg' => [
                    'post' => [
                        'summary' => 'Preview data laporan mutasi kayu bulat kg',
                        'security' => [
                            ['bearerAuth' => []],
                        ],
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/MutasiKayuBulatKGRequest',
                                    ],
                                ],
                                'application/x-www-form-urlencoded' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/MutasiKayuBulatKGRequest',
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
                                            '$ref' => '#/components/schemas/MutasiKayuBulatKGPreviewResponse',
                                        ],
                                    ],
                                ],
                            ],
                            '422' => [
                                'description' => 'Validasi gagal',
                            ],
                            '401' => [
                                'description' => 'Unauthenticated',
                            ],
                        ],
                    ],
                ],
                '/api/reports/mutasi-kayu-bulat-kg/pdf' => [
                    'post' => [
                        'summary' => 'Generate laporan mutasi kayu bulat kg PDF',
                        'security' => [
                            ['bearerAuth' => []],
                        ],
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/MutasiKayuBulatKGRequest',
                                    ],
                                ],
                                'application/x-www-form-urlencoded' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/MutasiKayuBulatKGRequest',
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
                            ],
                        ],
                    ],
                ],
                '/api/reports/mutasi-kayu-bulat-kg/health' => [
                    'post' => [
                        'summary' => 'Cek kesehatan struktur output SP mutasi kayu bulat kg',
                        'security' => [
                            ['bearerAuth' => []],
                        ],
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/MutasiKayuBulatKGRequest',
                                    ],
                                ],
                                'application/x-www-form-urlencoded' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/MutasiKayuBulatKGRequest',
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
                                            '$ref' => '#/components/schemas/MutasiKayuBulatKGHealthResponse',
                                        ],
                                    ],
                                ],
                            ],
                            '422' => [
                                'description' => 'Validasi gagal',
                            ],
                            '401' => [
                                'description' => 'Unauthenticated',
                            ],
                        ],
                    ],
                ],
                '/api/reports/rangkuman-label-input' => [
                    'post' => [
                        'summary' => 'Preview data laporan rangkuman jumlah label input',
                        'security' => [
                            ['bearerAuth' => []],
                        ],
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/RangkumanLabelInputRequest',
                                    ],
                                ],
                                'application/x-www-form-urlencoded' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/RangkumanLabelInputRequest',
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
                                            '$ref' => '#/components/schemas/RangkumanLabelInputPreviewResponse',
                                        ],
                                    ],
                                ],
                            ],
                            '422' => [
                                'description' => 'Validasi gagal',
                            ],
                            '401' => [
                                'description' => 'Unauthenticated',
                            ],
                        ],
                    ],
                ],
                '/api/reports/rangkuman-label-input/pdf' => [
                    'post' => [
                        'summary' => 'Generate laporan rangkuman jumlah label input PDF',
                        'security' => [
                            ['bearerAuth' => []],
                        ],
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/RangkumanLabelInputRequest',
                                    ],
                                ],
                                'application/x-www-form-urlencoded' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/RangkumanLabelInputRequest',
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
                            ],
                        ],
                    ],
                ],
                '/api/reports/rangkuman-label-input/health' => [
                    'post' => [
                        'summary' => 'Cek kesehatan struktur output SPWps_LapRangkumanJlhLabelInput',
                        'security' => [
                            ['bearerAuth' => []],
                        ],
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/RangkumanLabelInputRequest',
                                    ],
                                ],
                                'application/x-www-form-urlencoded' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/RangkumanLabelInputRequest',
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
                                            '$ref' => '#/components/schemas/RangkumanLabelInputHealthResponse',
                                        ],
                                    ],
                                ],
                            ],
                            '422' => [
                                'description' => 'Validasi gagal',
                            ],
                            '401' => [
                                'description' => 'Unauthenticated',
                            ],
                        ],
                    ],
                ],
                '/api/reports/label-nyangkut' => [
                    'post' => [
                        'summary' => 'Preview data laporan label nyangkut',
                        'security' => [
                            ['bearerAuth' => []],
                        ],
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/LabelNyangkutRequest',
                                    ],
                                ],
                                'application/x-www-form-urlencoded' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/LabelNyangkutRequest',
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
                                            '$ref' => '#/components/schemas/LabelNyangkutPreviewResponse',
                                        ],
                                    ],
                                ],
                            ],
                            '422' => [
                                'description' => 'Validasi gagal',
                            ],
                            '401' => [
                                'description' => 'Unauthenticated',
                            ],
                        ],
                    ],
                ],
                '/api/reports/label-nyangkut/pdf' => [
                    'post' => [
                        'summary' => 'Generate laporan label nyangkut PDF',
                        'security' => [
                            ['bearerAuth' => []],
                        ],
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/LabelNyangkutRequest',
                                    ],
                                ],
                                'application/x-www-form-urlencoded' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/LabelNyangkutRequest',
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
                            ],
                        ],
                    ],
                ],
                '/api/reports/label-nyangkut/health' => [
                    'post' => [
                        'summary' => 'Cek kesehatan struktur output SPWps_LapLabelNyangkut',
                        'security' => [
                            ['bearerAuth' => []],
                        ],
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/LabelNyangkutRequest',
                                    ],
                                ],
                                'application/x-www-form-urlencoded' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/LabelNyangkutRequest',
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
                                            '$ref' => '#/components/schemas/LabelNyangkutHealthResponse',
                                        ],
                                    ],
                                ],
                            ],
                            '422' => [
                                'description' => 'Validasi gagal',
                            ],
                            '401' => [
                                'description' => 'Unauthenticated',
                            ],
                        ],
                    ],
                ],
                '/api/reports/bahan-terpakai' => [
                    'post' => [
                        'summary' => 'Preview data laporan bahan terpakai',
                        'security' => [
                            ['bearerAuth' => []],
                        ],
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/BahanTerpakaiRequest',
                                    ],
                                ],
                                'application/x-www-form-urlencoded' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/BahanTerpakaiRequest',
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
                                            '$ref' => '#/components/schemas/BahanTerpakaiPreviewResponse',
                                        ],
                                    ],
                                ],
                            ],
                            '422' => [
                                'description' => 'Validasi gagal',
                            ],
                            '401' => [
                                'description' => 'Unauthenticated',
                            ],
                        ],
                    ],
                ],
                '/api/reports/bahan-terpakai/pdf' => [
                    'post' => [
                        'summary' => 'Generate laporan bahan terpakai PDF',
                        'security' => [
                            ['bearerAuth' => []],
                        ],
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/BahanTerpakaiRequest',
                                    ],
                                ],
                                'application/x-www-form-urlencoded' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/BahanTerpakaiRequest',
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
                            ],
                        ],
                    ],
                ],
                '/api/reports/bahan-terpakai/health' => [
                    'post' => [
                        'summary' => 'Cek kesehatan struktur output SPWps_LapBahanTerpakai',
                        'security' => [
                            ['bearerAuth' => []],
                        ],
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/BahanTerpakaiRequest',
                                    ],
                                ],
                                'application/x-www-form-urlencoded' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/BahanTerpakaiRequest',
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
                                            '$ref' => '#/components/schemas/BahanTerpakaiHealthResponse',
                                        ],
                                    ],
                                ],
                            ],
                            '422' => [
                                'description' => 'Validasi gagal',
                            ],
                            '401' => [
                                'description' => 'Unauthenticated',
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
                            'name' => ['type' => 'string', 'example' => 'John Doe'],
                            'email' => ['type' => 'string', 'format' => 'email', 'example' => 'john@example.com'],
                            'password' => ['type' => 'string', 'format' => 'password', 'example' => 'secret123'],
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
                            'email' => ['type' => 'string', 'format' => 'email', 'example' => 'john@example.com'],
                            'password' => ['type' => 'string', 'format' => 'password', 'example' => 'secret123'],
                        ],
                    ],
                    'AuthTokenResponse' => [
                        'type' => 'object',
                        'properties' => [
                            'access_token' => ['type' => 'string', 'example' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...'],
                            'token_type' => ['type' => 'string', 'example' => 'bearer'],
                            'expires_in' => ['type' => 'integer', 'example' => 3600],
                            'user' => ['$ref' => '#/components/schemas/User'],
                        ],
                    ],
                    'AuthUserResponse' => [
                        'type' => 'object',
                        'properties' => [
                            'user' => ['$ref' => '#/components/schemas/User'],
                        ],
                    ],
                    'MessageResponse' => [
                        'type' => 'object',
                        'properties' => [
                            'message' => ['type' => 'string', 'example' => 'Logout berhasil.'],
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
                    'MutasiBarangJadiRequest' => [
                        'type' => 'object',
                        'required' => ['TglAwal', 'TglAkhir'],
                        'properties' => [
                            'TglAwal' => ['type' => 'string', 'format' => 'date', 'example' => '2026-01-01'],
                            'TglAkhir' => ['type' => 'string', 'format' => 'date', 'example' => '2026-01-31'],
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
                            'message' => ['type' => 'string', 'example' => 'Preview laporan berhasil diambil.'],
                            'meta' => [
                                'type' => 'object',
                                'properties' => [
                                    'start_date' => ['type' => 'string', 'format' => 'date'],
                                    'end_date' => ['type' => 'string', 'format' => 'date'],
                                    'TglAwal' => ['type' => 'string', 'format' => 'date'],
                                    'TglAkhir' => ['type' => 'string', 'format' => 'date'],
                                    'total_rows' => ['type' => 'integer', 'example' => 14],
                                ],
                            ],
                            'data' => [
                                'type' => 'array',
                                'items' => ['$ref' => '#/components/schemas/MutasiBarangJadiRow'],
                            ],
                        ],
                    ],
                    'MutasiBarangJadiHealthResponse' => [
                        'type' => 'object',
                        'properties' => [
                            'message' => ['type' => 'string', 'example' => 'Struktur output SP_Mutasi_BarangJadi valid.'],
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
                    'MutasiFingerJointRequest' => [
                        'type' => 'object',
                        'required' => ['TglAwal', 'TglAkhir'],
                        'properties' => [
                            'TglAwal' => ['type' => 'string', 'format' => 'date', 'example' => '2026-01-01'],
                            'TglAkhir' => ['type' => 'string', 'format' => 'date', 'example' => '2026-01-31'],
                        ],
                    ],
                    'MutasiFingerJointRow' => [
                        'type' => 'object',
                        'additionalProperties' => true,
                        'example' => [
                            'Jenis' => 'FJ JABON',
                            'Awal' => 10.25,
                            'Masuk' => 2.1,
                            'Keluar' => 1.4,
                            'Akhir' => 10.95,
                        ],
                    ],
                    'MutasiFingerJointPreviewResponse' => [
                        'type' => 'object',
                        'properties' => [
                            'message' => ['type' => 'string', 'example' => 'Preview laporan berhasil diambil.'],
                            'meta' => [
                                'type' => 'object',
                                'properties' => [
                                    'start_date' => ['type' => 'string', 'format' => 'date'],
                                    'end_date' => ['type' => 'string', 'format' => 'date'],
                                    'TglAwal' => ['type' => 'string', 'format' => 'date'],
                                    'TglAkhir' => ['type' => 'string', 'format' => 'date'],
                                    'total_rows' => ['type' => 'integer', 'example' => 12],
                                    'total_sub_rows' => ['type' => 'integer', 'example' => 12],
                                    'column_order' => ['type' => 'array', 'items' => ['type' => 'string']],
                                ],
                            ],
                            'data' => [
                                'type' => 'array',
                                'items' => ['$ref' => '#/components/schemas/MutasiFingerJointRow'],
                            ],
                            'sub_data' => [
                                'type' => 'array',
                                'items' => ['$ref' => '#/components/schemas/MutasiFingerJointRow'],
                            ],
                        ],
                    ],
                    'MutasiFingerJointHealthResponse' => [
                        'type' => 'object',
                        'properties' => [
                            'message' => ['type' => 'string', 'example' => 'Struktur output SP_Mutasi_FingerJoint valid.'],
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
                                    'row_count' => ['type' => 'integer', 'example' => 12],
                                ],
                            ],
                        ],
                    ],
                    'MutasiMouldingRequest' => [
                        'type' => 'object',
                        'required' => ['TglAwal', 'TglAkhir'],
                        'properties' => [
                            'TglAwal' => ['type' => 'string', 'format' => 'date', 'example' => '2026-01-01'],
                            'TglAkhir' => ['type' => 'string', 'format' => 'date', 'example' => '2026-01-31'],
                        ],
                    ],
                    'MutasiMouldingRow' => [
                        'type' => 'object',
                        'additionalProperties' => true,
                        'example' => [
                            'Jenis' => 'MLD JABON',
                            'Awal' => 10.25,
                            'Masuk' => 2.1,
                            'Keluar' => 1.4,
                            'Akhir' => 10.95,
                        ],
                    ],
                    'MutasiMouldingPreviewResponse' => [
                        'type' => 'object',
                        'properties' => [
                            'message' => ['type' => 'string', 'example' => 'Preview laporan berhasil diambil.'],
                            'meta' => [
                                'type' => 'object',
                                'properties' => [
                                    'start_date' => ['type' => 'string', 'format' => 'date'],
                                    'end_date' => ['type' => 'string', 'format' => 'date'],
                                    'TglAwal' => ['type' => 'string', 'format' => 'date'],
                                    'TglAkhir' => ['type' => 'string', 'format' => 'date'],
                                    'total_rows' => ['type' => 'integer', 'example' => 12],
                                    'total_sub_rows' => ['type' => 'integer', 'example' => 24],
                                    'column_order' => ['type' => 'array', 'items' => ['type' => 'string']],
                                ],
                            ],
                            'data' => [
                                'type' => 'array',
                                'items' => ['$ref' => '#/components/schemas/MutasiMouldingRow'],
                            ],
                            'sub_data' => [
                                'type' => 'array',
                                'items' => ['$ref' => '#/components/schemas/MutasiMouldingRow'],
                            ],
                        ],
                    ],
                    'MutasiMouldingHealthResponse' => [
                        'type' => 'object',
                        'properties' => [
                            'message' => ['type' => 'string', 'example' => 'Struktur output SP_Mutasi_Moulding valid.'],
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
                                    'row_count' => ['type' => 'integer', 'example' => 12],
                                ],
                            ],
                        ],
                    ],
                    'MutasiS4SRequest' => [
                        'type' => 'object',
                        'required' => ['TglAwal', 'TglAkhir'],
                        'properties' => [
                            'TglAwal' => ['type' => 'string', 'format' => 'date', 'example' => '2026-01-01'],
                            'TglAkhir' => ['type' => 'string', 'format' => 'date', 'example' => '2026-01-31'],
                        ],
                    ],
                    'MutasiS4SRow' => [
                        'type' => 'object',
                        'additionalProperties' => true,
                        'example' => [
                            'Jenis' => 'S4S JABON',
                            'Awal' => 10.25,
                            'Masuk' => 2.1,
                            'Keluar' => 1.4,
                            'Akhir' => 10.95,
                        ],
                    ],
                    'MutasiS4SPreviewResponse' => [
                        'type' => 'object',
                        'properties' => [
                            'message' => ['type' => 'string', 'example' => 'Preview laporan berhasil diambil.'],
                            'meta' => [
                                'type' => 'object',
                                'properties' => [
                                    'start_date' => ['type' => 'string', 'format' => 'date'],
                                    'end_date' => ['type' => 'string', 'format' => 'date'],
                                    'TglAwal' => ['type' => 'string', 'format' => 'date'],
                                    'TglAkhir' => ['type' => 'string', 'format' => 'date'],
                                    'total_rows' => ['type' => 'integer', 'example' => 12],
                                    'total_sub_rows' => ['type' => 'integer', 'example' => 24],
                                    'column_order' => ['type' => 'array', 'items' => ['type' => 'string']],
                                ],
                            ],
                            'data' => [
                                'type' => 'array',
                                'items' => ['$ref' => '#/components/schemas/MutasiS4SRow'],
                            ],
                            'sub_data' => [
                                'type' => 'array',
                                'items' => ['$ref' => '#/components/schemas/MutasiS4SRow'],
                            ],
                        ],
                    ],
                    'MutasiS4SHealthResponse' => [
                        'type' => 'object',
                        'properties' => [
                            'message' => ['type' => 'string', 'example' => 'Struktur output SP_Mutasi_S4S valid.'],
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
                                    'row_count' => ['type' => 'integer', 'example' => 12],
                                ],
                            ],
                        ],
                    ],
                    'MutasiKayuBulatRequest' => [
                        'type' => 'object',
                        'required' => ['TglAwal', 'TglAkhir'],
                        'properties' => [
                            'TglAwal' => ['type' => 'string', 'format' => 'date', 'example' => '2026-01-01'],
                            'TglAkhir' => ['type' => 'string', 'format' => 'date', 'example' => '2026-01-31'],
                        ],
                    ],
                    'MutasiKayuBulatRow' => [
                        'type' => 'object',
                        'additionalProperties' => true,
                        'example' => [
                            'Jenis' => 'KB JABON',
                            'Awal' => 10.25,
                            'Masuk' => 2.1,
                            'Keluar' => 1.4,
                            'Akhir' => 10.95,
                        ],
                    ],
                    'MutasiKayuBulatPreviewResponse' => [
                        'type' => 'object',
                        'properties' => [
                            'message' => ['type' => 'string', 'example' => 'Preview laporan berhasil diambil.'],
                            'meta' => [
                                'type' => 'object',
                                'properties' => [
                                    'start_date' => ['type' => 'string', 'format' => 'date'],
                                    'end_date' => ['type' => 'string', 'format' => 'date'],
                                    'TglAwal' => ['type' => 'string', 'format' => 'date'],
                                    'TglAkhir' => ['type' => 'string', 'format' => 'date'],
                                    'total_rows' => ['type' => 'integer', 'example' => 12],
                                    'total_sub_rows' => ['type' => 'integer', 'example' => 24],
                                    'column_order' => ['type' => 'array', 'items' => ['type' => 'string']],
                                ],
                            ],
                            'data' => [
                                'type' => 'array',
                                'items' => ['$ref' => '#/components/schemas/MutasiKayuBulatRow'],
                            ],
                            'sub_data' => [
                                'type' => 'array',
                                'items' => ['$ref' => '#/components/schemas/MutasiKayuBulatRow'],
                            ],
                        ],
                    ],
                    'MutasiKayuBulatHealthResponse' => [
                        'type' => 'object',
                        'properties' => [
                            'message' => ['type' => 'string', 'example' => 'Struktur output SP_Mutasi_KayuBulat valid.'],
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
                                    'row_count' => ['type' => 'integer', 'example' => 12],
                                ],
                            ],
                        ],
                    ],
                    'MutasiKayuBulatKGV2Request' => [
                        'type' => 'object',
                        'required' => ['TglAwal', 'TglAkhir'],
                        'properties' => [
                            'TglAwal' => ['type' => 'string', 'format' => 'date', 'example' => '2026-01-01'],
                            'TglAkhir' => ['type' => 'string', 'format' => 'date', 'example' => '2026-01-31'],
                        ],
                    ],
                    'MutasiKayuBulatKGV2Row' => [
                        'type' => 'object',
                        'additionalProperties' => true,
                        'example' => [
                            'Jenis' => 'KB JABON',
                            'Awal' => 10.25,
                            'Masuk' => 2.1,
                            'Keluar' => 1.4,
                            'Akhir' => 10.95,
                        ],
                    ],
                    'MutasiKayuBulatKGV2PreviewResponse' => [
                        'type' => 'object',
                        'properties' => [
                            'message' => ['type' => 'string', 'example' => 'Preview laporan berhasil diambil.'],
                            'meta' => [
                                'type' => 'object',
                                'properties' => [
                                    'start_date' => ['type' => 'string', 'format' => 'date'],
                                    'end_date' => ['type' => 'string', 'format' => 'date'],
                                    'TglAwal' => ['type' => 'string', 'format' => 'date'],
                                    'TglAkhir' => ['type' => 'string', 'format' => 'date'],
                                    'total_rows' => ['type' => 'integer', 'example' => 12],
                                    'total_sub_rows' => ['type' => 'integer', 'example' => 24],
                                    'column_order' => ['type' => 'array', 'items' => ['type' => 'string']],
                                ],
                            ],
                            'data' => [
                                'type' => 'array',
                                'items' => ['$ref' => '#/components/schemas/MutasiKayuBulatKGV2Row'],
                            ],
                            'sub_data' => [
                                'type' => 'array',
                                'items' => ['$ref' => '#/components/schemas/MutasiKayuBulatKGV2Row'],
                            ],
                        ],
                    ],
                    'MutasiKayuBulatKGV2HealthResponse' => [
                        'type' => 'object',
                        'properties' => [
                            'message' => ['type' => 'string', 'example' => 'Struktur output SP_Mutasi_KayuBulatKGV2 valid.'],
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
                                    'row_count' => ['type' => 'integer', 'example' => 12],
                                ],
                            ],
                        ],
                    ],
                    'MutasiKayuBulatKGRequest' => [
                        'type' => 'object',
                        'required' => ['TglAwal', 'TglAkhir'],
                        'properties' => [
                            'TglAwal' => ['type' => 'string', 'format' => 'date', 'example' => '2026-01-01'],
                            'TglAkhir' => ['type' => 'string', 'format' => 'date', 'example' => '2026-01-31'],
                        ],
                    ],
                    'MutasiKayuBulatKGRow' => [
                        'type' => 'object',
                        'additionalProperties' => true,
                        'example' => [
                            'Jenis' => 'KB JABON',
                            'Awal' => 10.25,
                            'Masuk' => 2.1,
                            'Keluar' => 1.4,
                            'Akhir' => 10.95,
                        ],
                    ],
                    'MutasiKayuBulatKGPreviewResponse' => [
                        'type' => 'object',
                        'properties' => [
                            'message' => ['type' => 'string', 'example' => 'Preview laporan berhasil diambil.'],
                            'meta' => [
                                'type' => 'object',
                                'properties' => [
                                    'start_date' => ['type' => 'string', 'format' => 'date'],
                                    'end_date' => ['type' => 'string', 'format' => 'date'],
                                    'TglAwal' => ['type' => 'string', 'format' => 'date'],
                                    'TglAkhir' => ['type' => 'string', 'format' => 'date'],
                                    'total_rows' => ['type' => 'integer', 'example' => 12],
                                    'total_sub_rows' => ['type' => 'integer', 'example' => 24],
                                    'column_order' => ['type' => 'array', 'items' => ['type' => 'string']],
                                ],
                            ],
                            'data' => [
                                'type' => 'array',
                                'items' => ['$ref' => '#/components/schemas/MutasiKayuBulatKGRow'],
                            ],
                            'sub_data' => [
                                'type' => 'array',
                                'items' => ['$ref' => '#/components/schemas/MutasiKayuBulatKGRow'],
                            ],
                        ],
                    ],
                    'MutasiKayuBulatKGHealthResponse' => [
                        'type' => 'object',
                        'properties' => [
                            'message' => ['type' => 'string', 'example' => 'Struktur output SP_Mutasi_KayuBulatKG valid.'],
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
                                    'row_count' => ['type' => 'integer', 'example' => 12],
                                ],
                            ],
                        ],
                    ],
                    'RangkumanLabelInputRequest' => [
                        'type' => 'object',
                        'required' => ['TglAwal', 'TglAkhir'],
                        'properties' => [
                            'TglAwal' => ['type' => 'string', 'format' => 'date', 'example' => '2026-01-01'],
                            'TglAkhir' => ['type' => 'string', 'format' => 'date', 'example' => '2026-01-31'],
                        ],
                    ],
                    'RangkumanLabelInputRow' => [
                        'type' => 'object',
                        'additionalProperties' => true,
                        'example' => [
                            'Tanggal' => '2026-01-01',
                            'Shift' => '1',
                            'JumlahLabel' => 125,
                        ],
                    ],
                    'RangkumanLabelInputPreviewResponse' => [
                        'type' => 'object',
                        'properties' => [
                            'message' => ['type' => 'string', 'example' => 'Preview laporan berhasil diambil.'],
                            'meta' => [
                                'type' => 'object',
                                'properties' => [
                                    'start_date' => ['type' => 'string', 'format' => 'date'],
                                    'end_date' => ['type' => 'string', 'format' => 'date'],
                                    'TglAwal' => ['type' => 'string', 'format' => 'date'],
                                    'TglAkhir' => ['type' => 'string', 'format' => 'date'],
                                    'total_rows' => ['type' => 'integer', 'example' => 31],
                                    'column_order' => ['type' => 'array', 'items' => ['type' => 'string']],
                                ],
                            ],
                            'data' => [
                                'type' => 'array',
                                'items' => ['$ref' => '#/components/schemas/RangkumanLabelInputRow'],
                            ],
                        ],
                    ],
                    'RangkumanLabelInputHealthResponse' => [
                        'type' => 'object',
                        'properties' => [
                            'message' => ['type' => 'string', 'example' => 'Struktur output SPWps_LapRangkumanJlhLabelInput valid.'],
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
                                    'row_count' => ['type' => 'integer', 'example' => 31],
                                ],
                            ],
                        ],
                    ],
                    'LabelNyangkutRequest' => [
                        'type' => 'object',
                        'properties' => [],
                    ],
                    'LabelNyangkutRow' => [
                        'type' => 'object',
                        'additionalProperties' => true,
                        'example' => [
                            'Tanggal' => '2026-01-01',
                            'Shift' => '1',
                            'JumlahLabelNyangkut' => 12,
                        ],
                    ],
                    'LabelNyangkutPreviewResponse' => [
                        'type' => 'object',
                        'properties' => [
                            'message' => ['type' => 'string', 'example' => 'Preview laporan berhasil diambil.'],
                            'meta' => [
                                'type' => 'object',
                                'properties' => [
                                    'total_rows' => ['type' => 'integer', 'example' => 31],
                                    'column_order' => ['type' => 'array', 'items' => ['type' => 'string']],
                                ],
                            ],
                            'data' => [
                                'type' => 'array',
                                'items' => ['$ref' => '#/components/schemas/LabelNyangkutRow'],
                            ],
                        ],
                    ],
                    'LabelNyangkutHealthResponse' => [
                        'type' => 'object',
                        'properties' => [
                            'message' => ['type' => 'string', 'example' => 'Struktur output SPWps_LapLabelNyangkut valid.'],
                            'meta' => [
                                'type' => 'object',
                                'properties' => [],
                            ],
                            'health' => [
                                'type' => 'object',
                                'properties' => [
                                    'is_healthy' => ['type' => 'boolean', 'example' => true],
                                    'expected_columns' => ['type' => 'array', 'items' => ['type' => 'string']],
                                    'detected_columns' => ['type' => 'array', 'items' => ['type' => 'string']],
                                    'missing_columns' => ['type' => 'array', 'items' => ['type' => 'string']],
                                    'extra_columns' => ['type' => 'array', 'items' => ['type' => 'string']],
                                    'row_count' => ['type' => 'integer', 'example' => 31],
                                ],
                            ],
                        ],
                    ],
                    'BahanTerpakaiRequest' => [
                        'type' => 'object',
                        'required' => ['TglAwal'],
                        'properties' => [
                            'TglAwal' => ['type' => 'string', 'format' => 'date', 'example' => '2026-01-01'],
                        ],
                    ],
                    'BahanTerpakaiRow' => [
                        'type' => 'object',
                        'additionalProperties' => true,
                        'example' => [
                            'Group' => 'PROSES CCAKHIR',
                            'NamaMesin' => 'DOUBLE END CUTTER',
                            'Jenis' => 'LMT PULAI TASOBO',
                            'Tebal' => 48,
                            'Lebar' => 45,
                            'Panjang' => 2430,
                            'JmlhBatang' => 124,
                            'KubikIN' => 0.6508,
                        ],
                    ],
                    'BahanTerpakaiSubRow' => [
                        'type' => 'object',
                        'additionalProperties' => true,
                        'example' => [
                            'Group' => 'PROSES S4S',
                            'NamaMesin' => 'MULTI RIPSAW',
                            'Jenis' => 'ST RAMBUNG - STD',
                            'Tebal' => 44,
                            'Lebar' => 42,
                            'Ton' => 1.0688,
                        ],
                    ],
                    'BahanTerpakaiPreviewResponse' => [
                        'type' => 'object',
                        'properties' => [
                            'message' => ['type' => 'string', 'example' => 'Preview laporan berhasil diambil.'],
                            'meta' => [
                                'type' => 'object',
                                'properties' => [
                                    'date' => ['type' => 'string', 'format' => 'date'],
                                    'TglAwal' => ['type' => 'string', 'format' => 'date'],
                                    'total_rows' => ['type' => 'integer', 'example' => 31],
                                    'total_sub_rows' => ['type' => 'integer', 'example' => 5],
                                    'column_order' => ['type' => 'array', 'items' => ['type' => 'string']],
                                    'sub_column_order' => ['type' => 'array', 'items' => ['type' => 'string']],
                                ],
                            ],
                            'data' => [
                                'type' => 'array',
                                'items' => ['$ref' => '#/components/schemas/BahanTerpakaiRow'],
                            ],
                            'sub_data' => [
                                'type' => 'array',
                                'items' => ['$ref' => '#/components/schemas/BahanTerpakaiSubRow'],
                            ],
                        ],
                    ],
                    'BahanTerpakaiHealthResponse' => [
                        'type' => 'object',
                        'properties' => [
                            'message' => ['type' => 'string', 'example' => 'Struktur output SPWps_LapBahanTerpakai valid.'],
                            'meta' => [
                                'type' => 'object',
                                'properties' => [
                                    'date' => ['type' => 'string', 'format' => 'date'],
                                    'TglAwal' => ['type' => 'string', 'format' => 'date'],
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
                                    'row_count' => ['type' => 'integer', 'example' => 31],
                                    'expected_sub_columns' => ['type' => 'array', 'items' => ['type' => 'string']],
                                    'detected_sub_columns' => ['type' => 'array', 'items' => ['type' => 'string']],
                                    'missing_sub_columns' => ['type' => 'array', 'items' => ['type' => 'string']],
                                    'extra_sub_columns' => ['type' => 'array', 'items' => ['type' => 'string']],
                                    'sub_row_count' => ['type' => 'integer', 'example' => 5],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }
}

