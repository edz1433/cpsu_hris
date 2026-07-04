<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportSql extends Command
{
    /**
     * Command name (THIS is what you run in terminal)
     */
    protected $signature = 'db:import-sql';

    /**
     * Description
     */
    protected $description = 'Import SQL file from storage/app';

    /**
     * Execute command
     */
    public function handle(): int
    {
        $path = storage_path('app/dbcpsuhris.sql');

        if (!file_exists($path)) {
            $this->error("❌ SQL file not found: " . $path);
            return self::FAILURE;
        }

        $this->info("📥 Importing SQL file...");

        try {
            set_time_limit(0);

            $sql = file_get_contents($path);

            DB::unprepared($sql);

            $this->info("✅ Database imported successfully!");

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());

            return self::FAILURE;
        }
    }
}