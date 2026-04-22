<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PdfJobStatus extends Model
{
    public const STATUS_QUEUED = 'queued';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_DONE = 'done';
    public const STATUS_FAILED = 'failed';

    protected $table = 'pdf_job_statuses';

    protected $primaryKey = 'job_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'job_id',
        'report_type',
        'status',
        'file_path',
        'error_message',
        'request_payload',
        'requested_by',
        'expires_at',
    ];

    protected $casts = [
        'request_payload' => 'array',
        'expires_at' => 'datetime',
    ];

    public static function createJob(string $reportType, array $payload, ?string $requestedBy = null): self
    {
        return self::query()->create([
            'job_id' => Str::uuid()->toString(),
            'report_type' => $reportType,
            'status' => self::STATUS_QUEUED,
            'request_payload' => $payload,
            'requested_by' => $requestedBy,
            'expires_at' => now()->addHours((int) config('app.pdf_retention_hours', 24)),
        ]);
    }
}
