<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FilePdfJobStore
{
    public const STATUS_QUEUED = 'queued';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_DONE = 'done';

    public const STATUS_FAILED = 'failed';

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function create(string $reportType, array $payload = [], ?string $requestedBy = null): array
    {
        $job = [
            'job_id' => Str::uuid()->toString(),
            'report_type' => $reportType,
            'status' => self::STATUS_QUEUED,
            'file_path' => null,
            'error_message' => null,
            'request_payload' => $payload,
            'requested_by' => $requestedBy,
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
            'expires_at' => now()->addHours((int) config('app.pdf_retention_hours', 24))->toIso8601String(),
        ];

        $this->put($job);

        return $job;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(string $jobId): ?array
    {
        $path = $this->path($jobId);
        if (! is_file($path)) {
            return null;
        }

        $content = file_get_contents($path);
        if (! is_string($content) || trim($content) === '') {
            return null;
        }

        $decoded = json_decode($content, true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findLatestDone(string $reportType, ?string $requestedBy = null): ?array
    {
        return $this->findLatestDoneMatchingPayload($reportType, null, $requestedBy);
    }

    /**
     * @param  array<string, mixed>|null  $payload
     * @return array<string, mixed>|null
     */
    public function findLatestDoneMatchingPayload(string $reportType, ?array $payload = null, ?string $requestedBy = null): ?array
    {
        if (! is_dir($this->directory())) {
            return null;
        }

        $files = glob($this->directory().DIRECTORY_SEPARATOR.'*.json') ?: [];
        $latest = null;
        $latestTimestamp = 0;

        foreach ($files as $file) {
            if (! is_file($file)) {
                continue;
            }

            $content = file_get_contents($file);
            if (! is_string($content) || trim($content) === '') {
                continue;
            }

            $job = json_decode($content, true);
            if (! is_array($job)) {
                continue;
            }

            if (($job['report_type'] ?? null) !== $reportType) {
                continue;
            }

            if (($job['status'] ?? null) !== self::STATUS_DONE) {
                continue;
            }

            if ($requestedBy !== null && ($job['requested_by'] ?? null) !== $requestedBy) {
                continue;
            }

            if ($payload !== null && $this->normalizePayload($job['request_payload'] ?? []) !== $this->normalizePayload($payload)) {
                continue;
            }

            if (! is_string($job['file_path'] ?? null)) {
                continue;
            }

            if (! Storage::disk((string) config('app.pdf_storage_disk', 'local'))->exists((string) $job['file_path'])) {
                continue;
            }

            $updatedAt = strtotime((string) ($job['updated_at'] ?? $job['created_at'] ?? '')) ?: filemtime($file);
            if ($updatedAt === false || $updatedAt <= $latestTimestamp) {
                continue;
            }

            $latest = $job;
            $latestTimestamp = $updatedAt;
        }

        return $latest;
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizePayload(mixed $payload): array
    {
        if (! is_array($payload)) {
            return [];
        }

        ksort($payload);

        return $payload;
    }

    public function delete(string $jobId): void
    {
        $path = $this->path($jobId);
        if (is_file($path)) {
            @unlink($path);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function expiredJobs(): array
    {
        if (! is_dir($this->directory())) {
            return [];
        }

        $jobs = [];
        $files = glob($this->directory().DIRECTORY_SEPARATOR.'*.json') ?: [];

        foreach ($files as $file) {
            if (! is_file($file)) {
                continue;
            }

            $content = file_get_contents($file);
            if (! is_string($content) || trim($content) === '') {
                continue;
            }

            $job = json_decode($content, true);
            if (! is_array($job)) {
                continue;
            }

            $expiresAt = strtotime((string) ($job['expires_at'] ?? ''));
            if ($expiresAt === false || $expiresAt >= now()->getTimestamp()) {
                continue;
            }

            $jobs[] = $job;
        }

        return $jobs;
    }

    public function markProcessing(string $jobId): void
    {
        $this->update($jobId, [
            'status' => self::STATUS_PROCESSING,
            'error_message' => null,
        ]);
    }

    public function markDone(string $jobId, string $filePath): void
    {
        $this->update($jobId, [
            'status' => self::STATUS_DONE,
            'file_path' => $filePath,
            'error_message' => null,
        ]);
    }

    public function markFailed(string $jobId, string $message): void
    {
        $this->update($jobId, [
            'status' => self::STATUS_FAILED,
            'error_message' => $message,
        ]);
    }

    /**
     * @param  array<string, mixed>  $changes
     */
    private function update(string $jobId, array $changes): void
    {
        $job = $this->find($jobId);
        if ($job === null) {
            return;
        }

        $this->put(array_merge($job, $changes, [
            'updated_at' => now()->toIso8601String(),
        ]));
    }

    /**
     * @param  array<string, mixed>  $job
     */
    private function put(array $job): void
    {
        $this->ensureDirectory();

        $encoded = json_encode($job, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        file_put_contents($this->path((string) $job['job_id']), $encoded !== false ? $encoded : '{}');
    }

    private function path(string $jobId): string
    {
        return $this->directory().DIRECTORY_SEPARATOR.$jobId.'.json';
    }

    private function directory(): string
    {
        return storage_path('app/pdf-job-statuses');
    }

    private function ensureDirectory(): void
    {
        if (! is_dir($this->directory())) {
            @mkdir($this->directory(), 0777, true);
        }
    }
}
