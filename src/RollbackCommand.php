<?php


namespace Deiucanta\Smart;

use File;
use Illuminate\Console\Command;

class RollbackCommand extends Command
{
    protected $signature = 'smart:rollback';
    protected $description = 'Rollback last generated migration';

    public function handle()
    {
        if (!file_exists(database_path('smart.json.old')))
        {
            return $this->info('No previous version found.');
        }
        rename(database_path('smart.json.old'), database_path('smart.json'));

        $migrationName = $this->removeLastMigration();

        $this->info('Migration '.$migrationName.' has been erased');
    }

    public function removeLastMigration()
    {
        $files = scandir(database_path('migrations'));
        $lastMigration = end($files);
        unlink(database_path('migrations/'.$lastMigration));
        return $lastMigration;
    }
}
