<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_phones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->constrained('contacts')->cascadeOnDelete();
            $table->foreignId('phone_id')->constrained('phones')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['contact_id', 'phone_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_phones');
    }
};
