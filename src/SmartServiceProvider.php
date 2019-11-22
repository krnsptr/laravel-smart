<?php

namespace Deiucanta\Smart;

use Illuminate\Support\ServiceProvider;

class SmartServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ModelCommand::class,
                MigrationCommand::class,
                RollbackCommand::class,
            ]);
        }
    }

    public function register()
    {
        //
    }
}
