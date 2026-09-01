<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('google_api_keys', function (Blueprint $table) {
            $table->id();
            $table->string('api_key')->unique();
            $table->string('cx')->nullable();
            $table->integer('requests_today')->default(0);
            $table->integer('daily_limit')->default(95);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_exhausted')->default(false);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'is_exhausted', 'requests_today']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('google_api_keys');
    }
};
