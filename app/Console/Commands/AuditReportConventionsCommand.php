<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class AuditReportConventionsCommand extends Command
{
    protected $signature = 'reports:audit-conventions';

    protected $description = 'Audit konvensi report API yang diwajibkan di AGENT_INSTRUCTIONS.md.';

    public function handle(Router $router): int
    {
        $requestIssues = $this->auditRequests();
        $mpdfIssues = $this->auditMpdfUsage();
        $middlewareIssues = $this->auditReportRouteMiddleware($router);
        $controllerIssues = $this->auditReportControllers($router);

        if ($requestIssues === [] && $mpdfIssues === [] && $middlewareIssues === [] && $controllerIssues === []) {
            $this->info('Semua audit konvensi report lulus.');

            return self::SUCCESS;
        }

        if ($requestIssues !== []) {
            $this->warn('Request convention issues:');
            foreach ($requestIssues as $issue) {
                $this->line('- ' . $issue);
            }
            $this->newLine();
        }

        if ($mpdfIssues !== []) {
            $this->warn('mPDF usage issues:');
            foreach ($mpdfIssues as $issue) {
                $this->line('- ' . $issue);
            }
            $this->newLine();
        }

        if ($middlewareIssues !== []) {
            $this->warn('Route middleware issues:');
            foreach ($middlewareIssues as $issue) {
                $this->line('- ' . $issue);
            }
            $this->newLine();
        }

        if ($controllerIssues !== []) {
            $this->warn('Controller convention issues:');
            foreach ($controllerIssues as $issue) {
                $this->line('- ' . $issue);
            }
            $this->newLine();
        }

        return self::FAILURE;
    }

    /**
     * @return array<int, string>
     */
    private function auditRequests(): array
    {
        $issues = [];
        $requestRoot = app_path('Http/Requests');

        foreach ($this->phpFilesIn($requestRoot) as $absolutePath) {
            $relativePath = $this->normalizeRelativePath($absolutePath);
            $contents = file_get_contents($absolutePath);
            if ($contents === false) {
                $issues[] = $relativePath . ' tidak dapat dibaca.';

                continue;
            }

            if ($relativePath !== 'app/Http/Requests/BaseReportRequest.php'
                && preg_match('/extends\s+FormRequest\b/', $contents) === 1) {
                $issues[] = $relativePath . ' masih extend FormRequest langsung.';
            }

            if ($relativePath !== 'app/Http/Requests/BaseReportRequest.php'
                && preg_match('/function\s+failedValidation\s*\(/', $contents) === 1) {
                $issues[] = $relativePath . ' mengoverride failedValidation().';
            }
        }

        sort($issues);

        return $issues;
    }

    /**
     * @return array<int, string>
     */
    private function auditMpdfUsage(): array
    {
        $issues = [];

        foreach ([app_path(), resource_path(), base_path('routes')] as $root) {
            foreach ($this->phpFilesIn($root) as $absolutePath) {
                $relativePath = $this->normalizeRelativePath($absolutePath);
                if ($relativePath === 'app/Services/PdfGenerator.php') {
                    continue;
                }

                $contents = file_get_contents($absolutePath);
                if ($contents === false) {
                    $issues[] = $relativePath . ' tidak dapat dibaca.';

                    continue;
                }

                if (preg_match('/new\s+\\\\?Mpdf\b|use\s+Mpdf\\\\Mpdf;/', $contents) === 1) {
                    $issues[] = $relativePath . ' memakai mPDF langsung di luar PdfGenerator.';
                }
            }
        }

        sort($issues);

        return $issues;
    }

    /**
     * @return array<int, string>
     */
    private function auditReportRouteMiddleware(Router $router): array
    {
        $issues = [];

        /** @var Route $route */
        foreach ($router->getRoutes() as $route) {
            $uri = (string) $route->uri();
            if (! str_starts_with($uri, 'api/reports/')) {
                continue;
            }

            $middleware = $route->gatherMiddleware();
            $hasReportMiddleware = in_array('report.jwt.claims', $middleware, true)
                || in_array(\App\Http\Middleware\AuthenticateReportJwtClaims::class, $middleware, true);

            if (! $hasReportMiddleware) {
                $issues[] = '/' . $uri . ' tidak memakai middleware report.jwt.claims.';
            }
        }

        sort($issues);

        return $issues;
    }

    /**
     * @return array<int, string>
     */
    private function auditReportControllers(Router $router): array
    {
        $issues = [];
        $controllers = [];

        /** @var Route $route */
        foreach ($router->getRoutes() as $route) {
            $uri = (string) $route->uri();
            if (! str_starts_with($uri, 'api/reports/')) {
                continue;
            }
            if (str_starts_with($uri, 'api/reports/jobs/')) {
                continue;
            }
            if (str_ends_with($uri, '/pdf/async')) {
                continue;
            }

            $action = $route->getActionName();
            if (! is_string($action) || ! str_contains($action, '@')) {
                continue;
            }

            [$controllerClass] = explode('@', $action, 2);
            if (! is_string($controllerClass) || ! class_exists($controllerClass)) {
                continue;
            }

            $controllers[$controllerClass] = true;
        }

        foreach (array_keys($controllers) as $controllerClass) {
            $reflection = new \ReflectionClass($controllerClass);
            $fileName = $reflection->getFileName();
            if (! is_string($fileName) || ! is_file($fileName)) {
                $issues[] = $controllerClass . ' tidak memiliki file controller yang bisa diaudit.';

                continue;
            }

            $contents = file_get_contents($fileName);
            if ($contents === false) {
                $issues[] = $this->normalizeRelativePath($fileName) . ' tidak dapat dibaca.';

                continue;
            }

            foreach (['preview', 'download', 'health'] as $requiredMethod) {
                if (! $reflection->hasMethod($requiredMethod)) {
                    $issues[] = $reflection->getShortName() . " tidak memiliki method {$requiredMethod}().";
                }
            }

            if ($reflection->hasMethod('download') && ! str_contains($contents, 'PdfGenerator $pdfGenerator')) {
                $issues[] = $reflection->getShortName() . ' download() tidak menginjeksi PdfGenerator.';
            }

            if (str_contains($contents, 'function download(') && ! str_contains($contents, "'Content-Type' => 'application/pdf'")) {
                $issues[] = $reflection->getShortName() . " download() tidak menetapkan header application/pdf.";
            }
        }

        sort($issues);

        return $issues;
    }

    /**
     * @return array<int, string>
     */
    private function phpFilesIn(string $root): array
    {
        if (! is_dir($root)) {
            return [];
        }

        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $files[] = $file->getPathname();
        }

        sort($files);

        return $files;
    }

    private function normalizeRelativePath(string $absolutePath): string
    {
        $normalizedBase = str_replace('\\', '/', base_path());
        $normalizedPath = str_replace('\\', '/', $absolutePath);

        return ltrim(str_replace($normalizedBase, '', $normalizedPath), '/');
    }
}
