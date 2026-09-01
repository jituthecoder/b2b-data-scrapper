<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phones', function (Blueprint $table) {
            $table->id();
            $table->string('phone_number', 50);
            $table->string('normalized_phone', 50)->index();
            $table->string('country_code', 10)->nullable();
            $table->string('type', 30)->default('unknown'); // landline, mobile, toll_free
            $table->decimal('confidence_score', 5, 2)->default(1.00);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phones');
    }
};
