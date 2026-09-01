<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crawler_nodes', function (Blueprint $table) {
            $table->id();
            $table->string('crawler_id', 100)->unique();
            $table->string('api_key_hash', 64);
            $table->string('hostname', 255);
            $table->string('version', 50);
            $table->unsignedInteger('worker_count')->default(20);
            $table->string('status', 32)->default('active')->index(); // active, inactive, maintenance
            $table->json('capabilities'); // e.g. ["reachability", "homepage", "tech_detect", "contact_discover", "seo", "pagespeed"]
            $table->timestamp('last_heartbeat_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crawler_nodes');
    }
};
