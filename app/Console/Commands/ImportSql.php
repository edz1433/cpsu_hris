<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ImportSql extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:name';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $path = storage_path('app/dbcpsuhris.sql');

        if (!file_exists($path)) {
            $this->error("SQL file not found!");
            return;
        }

        $sql = file_get_contents($path);

        \DB::unprepared($sql);

        $this->info("Database imported successfully!");
    }
}
