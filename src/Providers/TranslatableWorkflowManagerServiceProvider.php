<?php

namespace TibbsA\TranslatableWorkflowManager\Providers;

use Illuminate\Support\ServiceProvider;
use TibbsA\TranslatableWorkflowManager\TranslatableWorkflowManager;
use TibbsA\TranslatableWorkflowManager\Commands as TWMCommands;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    /**
     * Register services
     *
     * @return void
     */
    public function register()
    {
        app()->singleton('translatableworkflow', function () {
            return new TranslatableWorkflowManager();
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            die('adding commands');
            $this->commands([
                TWMCommands\ImportTranslations::class,
                TWMCommands\ExportTranslations::class
            ]);
        }
    }
}
