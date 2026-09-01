<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('domains', 'screenshot_url')) {
            Schema::table('domains', function (Blueprint $table) {
                $table->string('screenshot_url', 1000)->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('domains', 'screenshot_url')) {
            Schema::table('domains', function (Blueprint $table) {
                $table->dropColumn('screenshot_url');
            });
        }
    }
};
