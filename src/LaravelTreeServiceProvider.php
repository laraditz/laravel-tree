<?php

namespace Laraditz\LaravelTree;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Schema\Blueprint;

class LaravelTreeServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        /*
         * Optional methods to load your package assets
         */
        // $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'laravel-tree');
        // $this->loadViewsFrom(__DIR__.'/../resources/views', 'laravel-tree');
        // $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        // $this->loadRoutesFrom(__DIR__.'/routes.php');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/config.php' => config_path('laraveltree.php'),
            ], 'config');

            // Publishing the views.
            /*$this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/laravel-tree'),
            ], 'views');*/

            // Publishing assets.
            /*$this->publishes([
                __DIR__.'/../resources/assets' => public_path('vendor/laravel-tree'),
            ], 'assets');*/

            // Publishing the translation files.
            /*$this->publishes([
                __DIR__.'/../resources/lang' => resource_path('lang/vendor/laravel-tree'),
            ], 'lang');*/

            // Registering package commands.
            // $this->commands([]);
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__ . '/../config/config.php', 'laraveltree');

        // Register the main class to use with the facade
        $this->app->singleton('laravelTree', function () {
            return new LaravelTree;
        });

        Blueprint::macro('addLaravelTreeColumns', function () {
            LaravelTree::addColumns($this);
        });

        Blueprint::macro('droplaravelTreeColumns', function () {
            LaravelTree::dropColumns($this);
        });
    }
}
