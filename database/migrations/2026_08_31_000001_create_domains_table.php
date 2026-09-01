<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('domains', function (Blueprint $table) {
            $table->id();
            $table->string('domain')->unique();
            $table->string('normalized_domain')->unique();
            $table->string('scheme', 10)->nullable();
            $table->boolean('www_variant')->default(false);
            $table->string('tld', 63)->nullable()->index();
            $table->string('status', 32)->default('active')->index();
            $table->boolean('is_accessible')->nullable();
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->text('final_url')->nullable();
            $table->text('canonical_url')->nullable();
            $table->timestamp('first_discovered_at')->nullable();
            $table->timestamp('last_crawled_at')->nullable()->index();
            $table->timestamp('next_crawl_at')->nullable()->index();
            $table->string('crawl_status', 32)->default('pending')->index();
            $table->unsignedInteger('crawl_attempts')->default(0);
            $table->text('last_crawl_error')->nullable();
            $table->integer('priority')->default(0)->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domains');
    }
};
