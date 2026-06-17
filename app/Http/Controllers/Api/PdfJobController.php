<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateReportPdfJob;
use App\Models\PdfJobStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Storage;

class PdfJobController extends Controller
{
    public function dispatch(Request $request, string $reportPath, Router $router): JsonResponse
    {
        $normalizedPath = trim($reportPath, '/');
        $syncUri = 'api/reports/'.$normalizedPath.'/pdf';
        $actionName = $this->findSyncPdfAction($router, $syncUri);

        if ($actionName === null) {
            return response()->json([
                'message' => "Jenis laporan '{$normalizedPath}' tidak ditemukan.",
            ], 404);
        }

        $jobStatus = PdfJobStatus::createJob(
            reportType: $normalizedPath,
            payload: $request->all(),
            requestedBy: $this->resolveAsyncRequestedBy($request),
        );

        GenerateReportPdfJob::dispatch(
            jobId: $jobStatus->job_id,
            reportType: $normalizedPath,
            controllerAction: $actionName,
            requestPayload: $request->all(),
            requestedBy: $jobStatus->requested_by,
        );

        return response()->json([
            'job_id' => $jobStatus->job_id,
            'status' => $jobStatus->status,
            'status_url' => route('api.pdf-jobs.status', $jobStatus->job_id),
            'message' => 'PDF sedang diproses di background. Cek status_url untuk update.',
        ], 202);
    }

    public function status(string $jobId): JsonResponse
    {
        $job = PdfJobStatus::query()->find($jobId);

        if (! $job instanceof PdfJobStatus) {
            return response()->json(['message' => 'Job tidak ditemukan.'], 404);
        }

        $response = [
            'job_id' => $job->job_id,
            'status' => $job->status,
            'report_type' => $job->report_type,
            'created_at' => $job->created_at?->toIso8601String(),
        ];

        if ($job->status === PdfJobStatus::STATUS_DONE) {
            $response['download_url'] = route('api.pdf-jobs.download', $job->job_id);
            $response['expires_at'] = $job->expires_at?->toIso8601String();
        }

        if ($job->status === PdfJobStatus::STATUS_FAILED) {
            $response['error'] = $job->error_message;
        }

        return response()->json($response);
    }

    public function download(string $jobId): Response|JsonResponse
    {
        $job = PdfJobStatus::query()->find($jobId);

        if (! $job instanceof PdfJobStatus) {
            return response()->json(['message' => 'Job tidak ditemukan.'], 404);
        }

        if ($job->status !== PdfJobStatus::STATUS_DONE) {
            return response()->json([
                'message' => 'PDF belum siap. Status saat ini: '.$job->status,
                'status' => $job->status,
            ], 409);
        }

        $disk = Storage::disk((string) config('app.pdf_storage_disk', 'local'));

        if (! is_string($job->file_path) || ! $disk->exists($job->file_path)) {
            return response()->json([
                'message' => 'File PDF tidak ditemukan. Mungkin sudah kadaluarsa.',
            ], 410);
        }

        $filename = basename($job->file_path);
        $content = $disk->get($job->file_path);

        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    private function resolveAsyncRequestedBy(Request $request): ?string
    {
        $user = $request->user() ?? auth('api')->user();
        if ($user !== null) {
            return (string) ($user->username ?? $user->Username ?? $user->name ?? 'unknown');
        }

        $claims = $request->attributes->get('report_token_claims');
        if (is_array($claims)) {
            return (string) ($claims['username'] ?? $claims['name'] ?? 'unknown');
        }

        return 'unknown';
    }

    private function findSyncPdfAction(Router $router, string $syncUri): ?string
    {
        /** @var Route $route */
        foreach ($router->getRoutes() as $route) {
            if ((string) $route->uri() !== $syncUri) {
                continue;
            }

            $methods = $route->methods();
            if (! in_array('POST', $methods, true) && ! in_array('GET', $methods, true)) {
                continue;
            }

            $actionName = $route->getActionName();
            if (is_string($actionName) && str_contains($actionName, '@')) {
                return $actionName;
            }
        }

        return null;
    }
}
