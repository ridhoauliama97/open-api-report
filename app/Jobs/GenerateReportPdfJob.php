<?php

namespace App\Jobs;

use App\Models\PdfJobStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Request;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class GenerateReportPdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 300;

    /**
     * @param  array<string, mixed>  $requestPayload
     */
    public function __construct(
        private readonly string $jobId,
        private readonly string $reportType,
        private readonly string $controllerAction,
        private readonly array $requestPayload,
        private readonly ?string $requestedBy,
    ) {
    }

    public function handle(Router $router): void
    {
        $jobStatus = PdfJobStatus::query()->find($this->jobId);

        if (!$jobStatus instanceof PdfJobStatus) {
            throw new RuntimeException("Status job {$this->jobId} tidak ditemukan.");
        }

        $jobStatus->update(['status' => PdfJobStatus::STATUS_PROCESSING]);

        try {
            [$controllerClass, $method] = $this->parseControllerAction($this->controllerAction);
            $request = $this->buildSyntheticRequest();
            $controller = app($controllerClass);

            /** @var mixed $result */
            $result = app()->call([$controller, $method], ['request' => $request]);

            if (!$result instanceof Response) {
                throw new RuntimeException('Controller download() tidak mengembalikan response yang valid.');
            }

            $pdfContent = $this->extractPdfContent($result);

            $filename = sprintf(
                '%s_%s_%s.pdf',
                str_replace('/', '-', $this->reportType),
                now()->format('Ymd_His'),
                substr($this->jobId, 0, 8),
            );

            $storagePath = trim((string) config('app.pdf_storage_path', 'pdf_reports'), '/') . '/' . $filename;
            Storage::disk((string) config('app.pdf_storage_disk', 'local'))->put($storagePath, $pdfContent);

            $jobStatus->update([
                'status' => PdfJobStatus::STATUS_DONE,
                'file_path' => $storagePath,
                'error_message' => null,
            ]);
        } catch (Throwable $exception) {
            $jobStatus->update([
                'status' => PdfJobStatus::STATUS_FAILED,
                'error_message' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    public function failed(Throwable $exception): void
    {
        PdfJobStatus::query()
            ->where('job_id', $this->jobId)
            ->update([
                'status' => PdfJobStatus::STATUS_FAILED,
                'error_message' => 'Job gagal setelah retry: ' . $exception->getMessage(),
            ]);
    }

    /**
     * @return array{0: class-string, 1: string}
     */
    private function parseControllerAction(string $controllerAction): array
    {
        $parts = explode('@', $controllerAction, 2);
        if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
            throw new RuntimeException("Action controller tidak valid: {$controllerAction}");
        }

        /** @var class-string $controllerClass */
        $controllerClass = $parts[0];

        return [$controllerClass, $parts[1]];
    }

    private function buildSyntheticRequest(): Request
    {
        $request = Request::create(
            '/api/reports/' . $this->reportType . '/pdf',
            'POST',
            $this->requestPayload,
        );

        $request->headers->set('Accept', 'application/pdf');
        $request->headers->set('Content-Type', 'application/json');
        $request->setJson(new \Illuminate\Http\JsonBag($this->requestPayload));
        $request->attributes->set('report_token_claims', [
            'name' => $this->requestedBy ?? 'async-job',
            'username' => $this->requestedBy ?? 'async-job',
        ]);
        $request->setUserResolver(function (): object {
            return (object) [
                'name' => $this->requestedBy ?? 'async-job',
                'Username' => $this->requestedBy ?? 'async-job',
            ];
        });
        $request->setRouteResolver(static fn() => null);

        return $request;
    }

    private function extractPdfContent(Response $response): string
    {
        if ($response->getStatusCode() >= 400) {
            $message = trim((string) $response->getContent());
            throw new RuntimeException($message !== '' ? $message : 'Gagal menghasilkan PDF secara async.');
        }

        if ($response instanceof BinaryFileResponse) {
            $file = $response->getFile();
            $path = $file?->getPathname();
            if (!is_string($path) || !is_file($path)) {
                throw new RuntimeException('File PDF sementara tidak ditemukan.');
            }

            $content = file_get_contents($path);
            if ($content === false) {
                throw new RuntimeException('Gagal membaca file PDF sementara.');
            }

            return $content;
        }

        $content = $response->getContent();
        if (!is_string($content) || $content === '') {
            throw new RuntimeException('Konten PDF kosong.');
        }

        return $content;
    }
}
