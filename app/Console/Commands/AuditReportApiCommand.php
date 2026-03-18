<?php

namespace App\Console\Commands;

use App\Http\Controllers\Api\OpenApiController;
use Illuminate\Console\Command;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;

class AuditReportApiCommand extends Command
{
    protected $signature = 'reports:audit-api {--fail-on-missing-openapi : Exit code non-zero if OpenAPI misses any api/reports paths}';

    protected $description = 'Audit API laporan: bandingkan route api/reports/* yang terdaftar vs OpenAPI, dan cek kelengkapan preview/pdf/health per report.';

    public function handle(Router $router, OpenApiController $openApiController): int
    {
        $routePaths = $this->collectApiReportPaths($router);
        $openApiPaths = $this->collectOpenApiReportPaths($openApiController);

        $missingInOpenApi = array_values(array_diff($routePaths, $openApiPaths));
        $extraInOpenApi = array_values(array_diff($openApiPaths, $routePaths));

        sort($missingInOpenApi);
        sort($extraInOpenApi);

        $this->line('API Report Route Count: ' . count($routePaths));
        $this->line('OpenAPI Report Path Count: ' . count($openApiPaths));
        $this->newLine();

        if ($missingInOpenApi !== []) {
            $this->warn('Paths missing in OpenAPI (but exist in routes):');
            foreach ($missingInOpenApi as $p) {
                $this->line('- ' . $p);
            }
            $this->newLine();
        } else {
            $this->info('OpenAPI sudah mencakup semua paths api/reports yang terdaftar.');
            $this->newLine();
        }

        if ($extraInOpenApi !== []) {
            $this->warn('Paths present in OpenAPI (but not found in routes):');
            foreach ($extraInOpenApi as $p) {
                $this->line('- ' . $p);
            }
            $this->newLine();
        }

        $groupIssues = $this->auditEndpointTriplets($router);
        if ($groupIssues !== []) {
            $this->warn('Endpoint group issues (preview/pdf/health):');
            foreach ($groupIssues as $issue) {
                $this->line('- ' . $issue);
            }
            $this->newLine();
        } else {
            $this->info('Semua group endpoint api/reports memiliki preview + pdf + health.');
            $this->newLine();
        }

        if ((bool) $this->option('fail-on-missing-openapi') && $missingInOpenApi !== []) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * @return array<int, string>
     */
    private function collectApiReportPaths(Router $router): array
    {
        $paths = [];

        /** @var Route $route */
        foreach ($router->getRoutes() as $route) {
            $uri = (string) $route->uri();
            if (!str_starts_with($uri, 'api/reports/')) {
                continue;
            }
            $paths[] = '/' . $uri;
        }

        return array_values(array_unique($paths));
    }

    /**
     * @return array<int, string>
     */
    private function collectOpenApiReportPaths(OpenApiController $openApiController): array
    {
        $json = $openApiController->index()->getData(true);
        $paths = (array) ($json['paths'] ?? []);

        $out = [];
        foreach (array_keys($paths) as $path) {
            if (!is_string($path)) {
                continue;
            }
            if (!str_starts_with($path, '/api/reports/')) {
                continue;
            }
            $out[] = $path;
        }

        return array_values(array_unique($out));
    }

    /**
     * @return array<int, string>
     */
    private function auditEndpointTriplets(Router $router): array
    {
        // We normalize "group base" as /api/reports/<something> without suffixes:
        // - /health
        // - /pdf
        $seen = [];

        /** @var Route $route */
        foreach ($router->getRoutes() as $route) {
            $uri = (string) $route->uri();
            if (!str_starts_with($uri, 'api/reports/')) {
                continue;
            }

            $path = '/' . $uri;
            $base = preg_replace('#/(health|pdf)$#', '', $path) ?? $path;
            $seen[$base] ??= [
                'preview' => false,
                'health' => false,
                'pdf' => false,
            ];

            if (str_ends_with($path, '/health')) {
                $seen[$base]['health'] = true;
                continue;
            }
            if (str_ends_with($path, '/pdf')) {
                $seen[$base]['pdf'] = true;
                continue;
            }
            $seen[$base]['preview'] = true;
        }

        $issues = [];
        foreach ($seen as $base => $flags) {
            $missing = [];
            if (!$flags['preview']) {
                $missing[] = 'preview';
            }
            if (!$flags['pdf']) {
                $missing[] = 'pdf';
            }
            if (!$flags['health']) {
                $missing[] = 'health';
            }

            if ($missing !== []) {
                $issues[] = $base . ' missing: ' . implode(', ', $missing);
            }
        }

        sort($issues);
        return $issues;
    }
}

