<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('domains', function (Blueprint $table) {
            $table->index('created_at');
            $table->index(['is_accessible', 'crawl_status']);
        });

        Schema::table('crawl_jobs', function (Blueprint $table) {
            $table->index('created_at');
            $table->index(['crawler_id', 'status']);
            $table->index(['crawler_id', 'completed_at']);
            $table->index(['crawler_id', 'failed_at']);
        });
    }

    public function down(): void
    {
        Schema::table('domains', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
            $table->dropIndex(['is_accessible', 'crawl_status']);
        });

        Schema::table('crawl_jobs', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
            $table->dropIndex(['crawler_id', 'status']);
            $table->dropIndex(['crawler_id', 'completed_at']);
            $table->dropIndex(['crawler_id', 'failed_at']);
        });
    }
};
