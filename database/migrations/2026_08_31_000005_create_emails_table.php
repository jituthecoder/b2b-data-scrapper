<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emails', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('normalized_email')->unique();
            $table->foreignId('domain_id')->nullable()->constrained('domains')->nullOnDelete();
            $table->string('type', 50)->default('unknown'); // generic, personal, role
            $table->string('verification_status', 50)->default('unverified');
            $table->decimal('confidence_score', 5, 2)->default(1.00);
            $table->timestamp('first_discovered_at')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emails');
    }
};
