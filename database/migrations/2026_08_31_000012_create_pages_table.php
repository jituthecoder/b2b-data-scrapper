<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('domain_id')->constrained('domains')->cascadeOnDelete();
            $table->text('url');
            $table->string('normalized_url', 500)->index();
            $table->string('page_type', 50)->default('homepage')->index(); // homepage, contact, about, careers, blog, sitemap
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->string('title', 500)->nullable();
            $table->string('html_snapshot_s3_path', 500)->nullable();
            $table->json('content_metadata')->nullable();
            $table->timestamp('crawled_at')->nullable();
            $table->timestamps();

            $table->unique(['domain_id', 'normalized_url']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
