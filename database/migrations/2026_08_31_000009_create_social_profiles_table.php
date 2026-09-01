<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('platform', 50)->index(); // linkedin, twitter, facebook, instagram, github, youtube
            $table->text('profile_url');
            $table->string('normalized_url', 500)->unique();
            $table->string('username_handle', 100)->nullable();
            $table->string('entity_type', 50); // company, contact, domain
            $table->unsignedBigInteger('entity_id');
            $table->timestamps();

            $table->index(['entity_type', 'entity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_profiles');
    }
};
