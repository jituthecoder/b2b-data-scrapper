<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crawl_jobs', function (Blueprint $table) {
            $table->index('domain_id');
        });

        Schema::table('crawl_attempts', function (Blueprint $table) {
            $table->index('crawl_job_id');
        });

        Schema::table('pages', function (Blueprint $table) {
            $table->index('domain_id');
        });

        Schema::table('emails', function (Blueprint $table) {
            $table->index('domain_id');
        });

        Schema::table('company_domains', function (Blueprint $table) {
            $table->index('domain_id');
        });

        Schema::table('domain_technologies', function (Blueprint $table) {
            $table->index('domain_id');
        });
    }

    public function down(): void
    {
        Schema::table('crawl_jobs', function (Blueprint $table) {
            $table->dropIndex(['domain_id']);
        });

        Schema::table('crawl_attempts', function (Blueprint $table) {
            $table->dropIndex(['crawl_job_id']);
        });

        Schema::table('pages', function (Blueprint $table) {
            $table->dropIndex(['domain_id']);
        });

        Schema::table('emails', function (Blueprint $table) {
            $table->dropIndex(['domain_id']);
        });

        Schema::table('company_domains', function (Blueprint $table) {
            $table->dropIndex(['domain_id']);
        });

        Schema::table('domain_technologies', function (Blueprint $table) {
            $table->dropIndex(['domain_id']);
        });
    }
};
