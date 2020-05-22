<?php

namespace TibbsA\TranslatableWorkflowManager;

use Illuminate\Support\ServiceProvider;
use TibbsA\TranslatableWorkflowManager\TranslatableWorkflowManager;

class TranslatableWorkflowManagerServiceProvider extends ServiceProvider
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
    }
}
