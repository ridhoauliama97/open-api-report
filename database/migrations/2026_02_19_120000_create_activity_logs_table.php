<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('user_identifier')->nullable()->index();
            $table->string('user_name')->nullable();
            $table->string('user_email')->nullable();
            $table->string('auth_guard', 50)->nullable();
            $table->string('route_name')->nullable()->index();
            $table->string('http_method', 10);
            $table->string('path');
            $table->text('full_url')->nullable();
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('request_meta')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
