<?php

namespace App\Console\Commands;

use App\Models\PdfJobStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanExpiredPdfFiles extends Command
{
    protected $signature = 'pdf:clean-expired';

    protected $description = 'Hapus file PDF async yang sudah melewati waktu kadaluarsa.';

    public function handle(): int
    {
        $expiredJobs = PdfJobStatus::query()
            ->where('status', PdfJobStatus::STATUS_DONE)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->get();

        $disk = Storage::disk((string) config('app.pdf_storage_disk', 'local'));

        foreach ($expiredJobs as $job) {
            if (is_string($job->file_path) && $job->file_path !== '' && $disk->exists($job->file_path)) {
                $disk->delete($job->file_path);
                $this->line('Dihapus: ' . $job->file_path);
            }

            $job->delete();
        }

        $this->info("Selesai. {$expiredJobs->count()} file kadaluarsa dihapus.");

        return self::SUCCESS;
    }
}
