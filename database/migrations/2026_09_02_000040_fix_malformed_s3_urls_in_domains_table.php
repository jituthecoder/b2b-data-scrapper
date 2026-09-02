<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Fix malformed favicon_url
        DB::statement("
            UPDATE domains 
            SET favicon_url = REPLACE(favicon_url, 'https://b2b-company-logos.s3.us-east-1.amazonaws.comapp_key=', 'https://b2b-company-logos.s3.us-east-1.amazonaws.com/')
            WHERE favicon_url LIKE '%amazonaws.comapp_key=%'
        ");

        // Fix malformed screenshot_url
        DB::statement("
            UPDATE domains 
            SET screenshot_url = REPLACE(screenshot_url, 'https://b2b-company-logos.s3.us-east-1.amazonaws.comapp_key=', 'https://b2b-company-logos.s3.us-east-1.amazonaws.com/')
            WHERE screenshot_url LIKE '%amazonaws.comapp_key=%'
        ");

        // Fix malformed logo_url in companies
        DB::statement("
            UPDATE companies 
            SET logo_url = REPLACE(logo_url, 'https://b2b-company-logos.s3.us-east-1.amazonaws.comapp_key=', 'https://b2b-company-logos.s3.us-east-1.amazonaws.com/')
            WHERE logo_url LIKE '%amazonaws.comapp_key=%'
        ");
    }

    public function down(): void
    {
    }
};
