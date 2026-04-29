<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;

class GenerateSwaggerSpec extends Command
{
    protected $signature = 'swagger:generate';
    protected $description = 'Generate OpenAPI 3.0 spec JSON from registered API routes for L5-Swagger UI';

    /**
     * Tag mapping: route path prefix => tag name.
     *
     * @var array<string, string>
     */
    private const TAG_MAP = [
        '/api/auth'                                        => 'Auth',
        '/api/reports/jobs'                                 => 'PDF Jobs (Async)',
        '/api/reports/barang-jadi'                          => 'Barang Jadi',
        '/api/reports/cross-cut-akhir'                      => 'Cross Cut Akhir',
        '/api/reports/dashboard'                            => 'Dashboard',
        '/api/reports/finger-joint'                         => 'Finger Joint',
        '/api/reports/kayu-bulat'                           => 'Kayu Bulat',
        '/api/reports/laminating'                           => 'Laminating',
        '/api/reports/management'                           => 'Management',
        '/api/reports/moulding'                             => 'Moulding',
        '/api/reports/mutasi'                               => 'Mutasi',
        '/api/reports/penjualan-kayu'                       => 'Penjualan Kayu',
        '/api/reports/proses-produksi'                      => 'Proses Produksi',
        '/api/reports/rendemen-kayu'                        => 'Rendemen Kayu',
        '/api/reports/reproses'                             => 'Reproses',
        '/api/reports/s4s'                                  => 'S4S',
        '/api/reports/sanding'                              => 'Sanding',
        '/api/reports/sawn-timber'                          => 'Sawn Timber',
        '/api/reports/pps'                                  => 'PPS (Plastik)',
        '/api/reports/verifikasi'                           => 'Verifikasi',
    ];

    public function handle(Router $router): int
    {
        $spec = [
            'openapi' => '3.0.3',
            'info' => [
                'title'       => 'Open API Report',
                'version'     => '1.0.0',
                'description' => 'API autentikasi JWT dan laporan PDF berbasis rentang tanggal. Semua endpoint laporan memerlukan Bearer token.',
                'contact'     => [
                    'name'  => 'API Support',
                    'email' => 'support@example.com',
                ],
            ],
            'servers' => [
                ['url' => config('app.url', 'http://localhost'), 'description' => 'API Server'],
            ],
            'tags'                => [],
            'paths'               => [],
            'components'          => $this->buildComponents(),
        ];

        $usedTags = [];

        /** @var Route $route */
        foreach ($router->getRoutes() as $route) {
            $uri = '/' . ltrim((string) $route->uri(), '/');

            // Only include /api/* routes
            if (!str_starts_with($uri, '/api/')) {
                continue;
            }

            // Skip internal/infrastructure routes
            if (in_array($uri, [
                '/api/openapi.json',
                '/api/documentation',
                '/api/docs/{jsonFile?}',
                '/api/docs',
                '/api/oauth2-callback',
            ], true)) {
                continue;
            }

            $methods = array_map('strtolower', $route->methods());
            $methods = array_diff($methods, ['head', 'options']);

            foreach ($methods as $method) {
                $tag = $this->resolveTag($uri);
                $usedTags[$tag] = true;

                $summary   = $this->buildSummary($uri, $method);
                $operation = [
                    'tags'    => [$tag],
                    'summary' => $summary,
                    'operationId' => $method . '_' . str_replace(['/', '{', '}', '-'], ['_', '', '', '_'], $uri),
                    'responses' => $this->buildResponses($uri, $method),
                ];

                // Add security for authenticated routes
                if ($tag !== 'Auth' || in_array($uri, ['/api/auth/me', '/api/auth/logout', '/api/auth/refresh'])) {
                    if ($uri !== '/api/auth/register' && $uri !== '/api/auth/login') {
                        $operation['security'] = [['bearerAuth' => []]];
                    }
                }

                // Add request body for POST endpoints (skip non-body auth endpoints)
                if ($method === 'post' && !in_array($uri, ['/api/auth/logout', '/api/auth/refresh'])) {
                    $operation['requestBody'] = $this->buildRequestBody($uri, $tag);
                }

                // Add path parameters
                if (preg_match_all('/\{(\w+)\}/', $uri, $matches)) {
                    $operation['parameters'] = [];
                    foreach ($matches[1] as $param) {
                        $operation['parameters'][] = [
                            'name'     => $param,
                            'in'       => 'path',
                            'required' => true,
                            'schema'   => ['type' => 'string'],
                        ];
                    }
                }

                $spec['paths'][$uri][$method] = $operation;
            }
        }

        // Build tags with descriptions
        foreach (self::TAG_MAP as $tag) {
            if (isset($usedTags[$tag])) {
                $spec['tags'][] = [
                    'name'        => $tag,
                    'description' => "Endpoint untuk {$tag}",
                ];
            }
        }

        // Handle any unmatched tags
        foreach ($usedTags as $tag => $_) {
            $exists = false;
            foreach ($spec['tags'] as $t) {
                if ($t['name'] === $tag) {
                    $exists = true;
                    break;
                }
            }
            if (!$exists) {
                $spec['tags'][] = [
                    'name'        => $tag,
                    'description' => "Endpoint untuk {$tag}",
                ];
            }
        }

        // Sort paths alphabetically
        ksort($spec['paths']);

        $outputDir = storage_path('api-docs');
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $outputPath = $outputDir . '/api-docs.json';
        file_put_contents(
            $outputPath,
            json_encode($spec, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        $pathCount = count($spec['paths']);
        $tagCount  = count($spec['tags']);

        $this->info("✅ OpenAPI spec generated: {$outputPath}");
        $this->info("   {$pathCount} paths, {$tagCount} tags");

        return self::SUCCESS;
    }

    private function resolveTag(string $uri): string
    {
        // Sort by longest prefix first for most specific match
        $sorted = self::TAG_MAP;
        uksort($sorted, fn(string $a, string $b) => strlen($b) - strlen($a));

        foreach ($sorted as $prefix => $tag) {
            if (str_starts_with($uri, $prefix)) {
                return $tag;
            }
        }

        // Fallback: standalone report endpoints
        if (str_starts_with($uri, '/api/reports/')) {
            return 'Lainnya';
        }

        return 'Other';
    }

    private function buildSummary(string $uri, string $method): string
    {
        // Auth endpoints
        $authSummaries = [
            '/api/auth/register' => 'Registrasi user baru',
            '/api/auth/login'    => 'Login user',
            '/api/auth/logout'   => 'Logout user (invalidate token)',
            '/api/auth/refresh'  => 'Refresh access token',
            '/api/auth/me'       => 'Data user yang sedang login',
        ];

        if (isset($authSummaries[$uri])) {
            return $authSummaries[$uri];
        }

        // PDF jobs
        if (str_contains($uri, '/jobs/') && str_contains($uri, '/status')) {
            return 'Cek status PDF job';
        }
        if (str_contains($uri, '/jobs/') && str_contains($uri, '/download')) {
            return 'Download hasil PDF job';
        }
        if (str_ends_with($uri, '/pdf/async')) {
            return 'Generate laporan PDF (async/background)';
        }

        // Report endpoints
        $baseName = $this->extractReportName($uri);

        if (str_ends_with($uri, '/pdf')) {
            return "Generate PDF: {$baseName}";
        }
        if (str_ends_with($uri, '/health')) {
            return "Health check: {$baseName}";
        }

        return "Preview data: {$baseName}";
    }

    private function extractReportName(string $uri): string
    {
        $path = preg_replace('#/api/reports/#', '', $uri) ?? $uri;
        $path = preg_replace('#/(pdf|health)$#', '', $path) ?? $path;
        $path = preg_replace('#/pdf/async$#', '', $path) ?? $path;

        $segments = explode('/', $path);
        $name     = end($segments) ?: $path;

        return ucwords(str_replace('-', ' ', $name));
    }

    /**
     * @return array<string, mixed>
     */
    private function buildRequestBody(string $uri, string $tag): array
    {
        // Auth endpoints have specific schemas
        if ($uri === '/api/auth/register') {
            return [
                'required' => true,
                'content'  => [
                    'application/json' => [
                        'schema' => ['$ref' => '#/components/schemas/AuthRegisterRequest'],
                    ],
                ],
            ];
        }

        if ($uri === '/api/auth/login') {
            return [
                'required' => true,
                'content'  => [
                    'application/json' => [
                        'schema' => ['$ref' => '#/components/schemas/AuthLoginRequest'],
                    ],
                ],
            ];
        }

        // Generic report request
        return [
            'required' => false,
            'content'  => [
                'application/json' => [
                    'schema' => ['$ref' => '#/components/schemas/ReportDateRangeRequest'],
                ],
                'application/x-www-form-urlencoded' => [
                    'schema' => ['$ref' => '#/components/schemas/ReportDateRangeRequest'],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildResponses(string $uri, string $method): array
    {
        // Auth token responses
        if (in_array($uri, ['/api/auth/register', '/api/auth/login', '/api/auth/refresh'])) {
            $code = $uri === '/api/auth/register' ? '201' : '200';
            return [
                $code  => [
                    'description' => $uri === '/api/auth/register' ? 'Registrasi berhasil' : 'Berhasil',
                    'content'     => [
                        'application/json' => [
                            'schema' => ['$ref' => '#/components/schemas/AuthTokenResponse'],
                        ],
                    ],
                ],
                '401' => ['description' => 'Kredensial tidak valid'],
                '422' => ['description' => 'Validasi gagal'],
            ];
        }

        if ($uri === '/api/auth/me') {
            return [
                '200' => [
                    'description' => 'Data user',
                    'content'     => [
                        'application/json' => [
                            'schema' => ['$ref' => '#/components/schemas/AuthUserResponse'],
                        ],
                    ],
                ],
                '401' => ['description' => 'Unauthenticated'],
            ];
        }

        if (in_array($uri, ['/api/auth/logout'])) {
            return [
                '200' => [
                    'description' => 'Logout berhasil',
                    'content'     => [
                        'application/json' => [
                            'schema' => ['$ref' => '#/components/schemas/MessageResponse'],
                        ],
                    ],
                ],
                '401' => ['description' => 'Token tidak valid'],
            ];
        }

        // PDF download
        if (str_ends_with($uri, '/pdf')) {
            return [
                '200' => [
                    'description' => 'PDF berhasil dibuat',
                    'content'     => [
                        'application/pdf' => [
                            'schema' => ['type' => 'string', 'format' => 'binary'],
                        ],
                    ],
                ],
                '401' => ['description' => 'Unauthenticated'],
                '422' => ['description' => 'Validasi gagal'],
            ];
        }

        // Default JSON response
        return [
            '200' => [
                'description' => 'Berhasil',
                'content'     => [
                    'application/json' => [
                        'schema' => ['type' => 'object'],
                    ],
                ],
            ],
            '401' => ['description' => 'Unauthenticated'],
            '422' => ['description' => 'Validasi gagal'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildComponents(): array
    {
        return [
            'securitySchemes' => [
                'bearerAuth' => [
                    'type'         => 'http',
                    'scheme'       => 'bearer',
                    'bearerFormat' => 'JWT',
                ],
            ],
            'schemas' => [
                'AuthRegisterRequest' => [
                    'type'       => 'object',
                    'required'   => ['username', 'password'],
                    'properties' => [
                        'username'              => ['type' => 'string', 'example' => 'admin'],
                        'password'              => ['type' => 'string', 'format' => 'password', 'example' => 'secret123'],
                        'password_confirmation' => ['type' => 'string', 'format' => 'password', 'example' => 'secret123'],
                    ],
                ],
                'AuthLoginRequest' => [
                    'type'       => 'object',
                    'required'   => ['username', 'password'],
                    'properties' => [
                        'username' => ['type' => 'string', 'example' => 'admin'],
                        'password' => ['type' => 'string', 'format' => 'password', 'example' => 'secret123'],
                    ],
                ],
                'AuthTokenResponse' => [
                    'type'       => 'object',
                    'properties' => [
                        'access_token' => ['type' => 'string'],
                        'token_type'   => ['type' => 'string', 'example' => 'bearer'],
                        'expires_in'   => ['type' => 'integer', 'nullable' => true],
                        'user'         => ['$ref' => '#/components/schemas/User'],
                    ],
                ],
                'AuthUserResponse' => [
                    'type'       => 'object',
                    'properties' => [
                        'user' => ['$ref' => '#/components/schemas/User'],
                    ],
                ],
                'MessageResponse' => [
                    'type'       => 'object',
                    'properties' => [
                        'message' => ['type' => 'string'],
                    ],
                ],
                'User' => [
                    'type'       => 'object',
                    'properties' => [
                        'id'       => ['type' => 'string'],
                        'username' => ['type' => 'string'],
                        'name'     => ['type' => 'string'],
                        'email'    => ['type' => 'string', 'nullable' => true],
                        'source'   => ['type' => 'string', 'enum' => ['wps', 'pps']],
                    ],
                ],
                'ReportDateRangeRequest' => [
                    'type'       => 'object',
                    'properties' => [
                        'dari_tanggal'   => ['type' => 'string', 'format' => 'date', 'example' => '2026-01-01', 'description' => 'Tanggal awal'],
                        'sampai_tanggal' => ['type' => 'string', 'format' => 'date', 'example' => '2026-01-31', 'description' => 'Tanggal akhir'],
                        'jenis_kayu'     => ['type' => 'string', 'description' => 'Filter jenis kayu (opsional)', 'nullable' => true],
                    ],
                ],
            ],
        ];
    }
}
