<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pdf_job_statuses', function (Blueprint $table) {
            $table->uuid('job_id')->primary();
            $table->string('report_type');
            $table->string('status');
            $table->string('file_path')->nullable();
            $table->text('error_message')->nullable();
            $table->json('request_payload');
            $table->string('requested_by')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pdf_job_statuses');
    }
};
