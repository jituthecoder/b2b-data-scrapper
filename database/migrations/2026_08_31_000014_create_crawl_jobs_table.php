<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crawl_jobs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('domain_id')->constrained('domains')->cascadeOnDelete();
            $table->string('job_type', 50)->index(); // reachability, homepage, tech_detect, contact_discover, careers, social, seo, pagespeed
            $table->integer('priority')->default(0)->index();
            $table->string('status', 32)->default('pending')->index(); // pending, claimed, processing, completed, failed, expired
            $table->string('crawler_id', 100)->nullable()->index();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamp('lease_expires_at')->nullable()->index();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->unsignedInteger('attempt_count')->default(0);
            $table->unsignedInteger('max_attempts')->default(3);
            $table->text('last_error')->nullable();
            $table->json('payload')->nullable();
            $table->string('raw_result_s3_path', 500)->nullable();
            $table->string('idempotency_key', 100)->nullable()->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crawl_jobs');
    }
};
