<?php

namespace Deiucanta\Smart;

use File;
use Illuminate\Console\Command;

class MigrationCommand extends Command
{
    protected $signature = 'smart:migration';
    protected $description = 'Generate Smart Migration';

    protected $analyzer;
    protected $generator;

    public function handle()
    {
        $this->analyzer = new MigrationAnalyzer();
        $this->generator = new MigrationGenerator();

        $newData = $this->getNewData();
        $oldData = $this->getOldData();

        $up = $this->analyzer->diff($oldData, $newData);
        $down = $this->analyzer->diff($newData, $oldData);

        if ($up && $down) {
            $migrationName = $this->getMigrationName($up);
            File::put(
                database_path('migrations/'.date('Y_m_d_His').'_'.$migrationName.'.php'),
                $this->generator->print($up, $down, $this->snakeToCamelCase($migrationName))
            );
            $this->saveData($newData);
        } else {
            $this->info('No changes.');
        }
    }

    protected function getMigrationName($up)
    {
        $action = null;
        $actions = [];
        $tables = [];

        if (isset($up['created']))
        {
            $actions[] = 'create';

            foreach ($up['created'] as $table => $modelData) {
                $tables[] = $table;
            }
        }

        if (isset($up['updated']))
        {
            $actions[] = 'update';

            foreach ($up['updated'] as $table => $modelData) {
                $tables[] = $table;
            }
        }

        if (isset($up['deleted']))
        {
            $actions[] = 'delete';

            foreach ($up['deleted'] as $table => $modelData) {
                $tables[] = $table;
            }
        }

        $action = $actions[0] ?? 'smart';
        $tables[] = count($tables) > 1 ? 'tables' : 'table';
        $time = time();

        return $action.'_'.implode('_', array_slice($tables, 0, 5)).'_'.$time;
    }

    private function snakeToCamelCase($string, $capitalizeFirstCharacter = true)
    {
        $str = str_replace(' ', '', ucwords(str_replace('_', ' ', $string)));

        if (!$capitalizeFirstCharacter) {
            $str[0] = strtolower($str[0]);
        }

        return $str;
    }

    protected function getNewData()
    {
        $models = config('smart.models');

        return $this->analyzer->scan($models);
    }

    protected function getOldData()
    {
        $path = database_path('smart.json');

        if (File::exists($path)) {
            $content = File::get($path);
            return json_decode($content, true);
        }

        return [];
    }

    protected function saveData($data)
    {
        $path = database_path('smart.json');
        $content = json_encode($data, JSON_PRETTY_PRINT);

        if (file_exists($path))
        {
            rename($path, database_path('smart.json.old'));
        }

        File::put($path, $content);
    }
}
