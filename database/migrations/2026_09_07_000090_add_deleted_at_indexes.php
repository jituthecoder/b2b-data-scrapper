<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('domains', function (Blueprint $table) {
            $table->index('deleted_at');
        });

        Schema::table('crawler_nodes', function (Blueprint $table) {
            $table->index(['crawler_id', 'api_key_hash', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('domains', function (Blueprint $table) {
            $table->dropIndex(['deleted_at']);
        });

        Schema::table('crawler_nodes', function (Blueprint $table) {
            $table->dropIndex(['crawler_id', 'api_key_hash', 'status']);
        });
    }
};
