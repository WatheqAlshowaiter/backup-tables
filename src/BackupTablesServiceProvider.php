<?php

namespace WatheqAlshowaiter\BackupTablesServiceProvider;

use Illuminate\Support\ServiceProvider;

class BackupTablesServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        // This migration works only in the package test
        if ($this->app->runningInConsole() && $this->app->environment() === 'testing') {
            $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        }
    }
}
