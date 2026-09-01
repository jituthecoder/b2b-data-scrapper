<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index();
            $table->string('normalized_name')->index();
            $table->string('logo_url', 1000)->nullable();
            $table->text('description')->nullable();
            $table->string('industry', 100)->nullable()->index();
            $table->string('employee_count_range', 50)->nullable();
            $table->unsignedSmallInteger('founded_year')->nullable();
            $table->string('country', 100)->nullable()->index();
            $table->string('state_region', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->text('address')->nullable();
            $table->string('postal_code', 30)->nullable();
            $table->json('metadata')->nullable();
            $table->decimal('confidence_score', 5, 2)->default(1.00);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
