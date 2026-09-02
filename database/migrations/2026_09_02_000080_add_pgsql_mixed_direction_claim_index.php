<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();
        if ($driver === 'pgsql') {
            DB::statement('CREATE INDEX IF NOT EXISTS crawl_jobs_mixed_claim_idx ON crawl_jobs (status, priority DESC, created_at ASC)');
            DB::statement('CREATE INDEX IF NOT EXISTS crawl_jobs_mixed_claim_desc_idx ON crawl_jobs (status, priority DESC, created_at DESC)');
        }
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();
        if ($driver === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS crawl_jobs_mixed_claim_idx');
            DB::statement('DROP INDEX IF EXISTS crawl_jobs_mixed_claim_desc_idx');
        }
    }
};
