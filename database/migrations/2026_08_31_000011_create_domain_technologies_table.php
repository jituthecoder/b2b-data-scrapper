<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('domain_technologies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('domain_id')->constrained('domains')->cascadeOnDelete();
            $table->foreignId('technology_id')->constrained('technologies')->cascadeOnDelete();
            $table->string('version', 50)->nullable();
            $table->string('detection_source', 100)->nullable();
            $table->decimal('confidence_score', 5, 2)->default(1.00);
            $table->timestamp('first_detected_at')->nullable();
            $table->timestamp('last_detected_at')->nullable();
            $table->timestamps();

            $table->unique(['domain_id', 'technology_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domain_technologies');
    }
};
