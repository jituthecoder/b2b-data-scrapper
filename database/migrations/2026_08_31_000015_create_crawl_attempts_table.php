<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crawl_attempts', function (Blueprint $table) {
            $table->id();
            $table->uuid('crawl_job_id');
            $table->foreign('crawl_job_id')->references('id')->on('crawl_jobs')->cascadeOnDelete();
            $table->string('crawler_id', 100)->index();
            $table->unsignedInteger('attempt_number');
            $table->string('status', 32); // started, success, failed, timeout
            $table->unsignedInteger('duration_ms')->nullable();
            $table->unsignedSmallInteger('response_code')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crawl_attempts');
    }
};
