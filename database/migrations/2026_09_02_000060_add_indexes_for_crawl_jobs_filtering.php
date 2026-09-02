<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crawl_jobs', function (Blueprint $table) {
            $table->index(['crawler_id', 'created_at']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('crawl_jobs', function (Blueprint $table) {
            $table->dropIndex(['crawler_id', 'created_at']);
            $table->dropIndex(['status', 'created_at']);
        });
    }
};
