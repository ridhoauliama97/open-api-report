<?php

namespace App\Console\Commands;

use App\Models\PdfJobStatus;
use App\Services\FilePdfJobStore;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class CleanExpiredPdfFiles extends Command
{
    protected $signature = 'pdf:clean-expired';

    protected $description = 'Hapus file PDF async yang sudah melewati waktu kadaluarsa.';

    public function handle(FilePdfJobStore $filePdfJobStore): int
    {
        $disk = Storage::disk((string) config('app.pdf_storage_disk', 'local'));
        $deleted = 0;

        foreach ($filePdfJobStore->expiredJobs() as $job) {
            if (is_string($job['file_path'] ?? null) && $job['file_path'] !== '' && $disk->exists($job['file_path'])) {
                $disk->delete($job['file_path']);
                $this->line('Dihapus file PDF: '.$job['file_path']);
            }

            if (is_string($job['job_id'] ?? null)) {
                $filePdfJobStore->delete($job['job_id']);
            }

            $deleted++;
        }

        if (! Schema::hasTable((new PdfJobStatus)->getTable())) {
            $this->info("Selesai. {$deleted} file job kadaluarsa dihapus. Tabel database PDF job tidak tersedia, cleanup DB dilewati.");

            return self::SUCCESS;
        }

        try {
            $expiredJobs = PdfJobStatus::query()
                ->where('status', PdfJobStatus::STATUS_DONE)
                ->whereNotNull('expires_at')
                ->where('expires_at', '<', now())
                ->get();
        } catch (QueryException) {
            $this->info("Selesai. {$deleted} file job kadaluarsa dihapus. Cleanup DB dilewati.");

            return self::SUCCESS;
        }

        foreach ($expiredJobs as $job) {
            if (is_string($job->file_path) && $job->file_path !== '' && $disk->exists($job->file_path)) {
                $disk->delete($job->file_path);
                $this->line('Dihapus file PDF: '.$job->file_path);
            }

            $job->delete();
            $deleted++;
        }

        $this->info("Selesai. {$deleted} file kadaluarsa dihapus.");

        return self::SUCCESS;
    }
}
