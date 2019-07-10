<?php

namespace jeremykenedy\LaravelPackagist;

use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;

class LaravelPackagistServiceProvider extends ServiceProvider
{
    private $_packageTag = 'laravelpackagist';

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadTranslationsFrom(__DIR__.'/resources/lang/', $this->_packageTag);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->packageRegistration();
        $this->mergeConfigFrom(__DIR__.'/config/'.$this->_packageTag.'.php', $this->_packageTag);
        $this->publishFiles();
    }

    /**
     * Package Registration.
     *
     * @return void
     */
    private function packageRegistration()
    {
        $this->app->make('jeremykenedy\LaravelPackagist\App\Services\PackagistApiServices');
        AliasLoader::getInstance()->alias('PackagistApiServices', \jeremykenedy\LaravelPackagist\App\Services\PackagistApiServices::class);
        $this->app->singleton(jeremykenedy\LaravelPackagist\App\Services\PackagistApiServices::class, function() {
            return new jeremykenedy\LaravelPackagist\App\Services\PackagistApiServices();
        });
        $this->app->alias(jeremykenedy\LaravelPackagist\App\Services\PackagistApiServices::class, $this->_packageTag);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [$this->_packageTag];
    }

    /**
     * Publish files for Laravel Blocker.
     *
     * @return void
     */
    private function publishFiles()
    {
        $this->publishes([
            __DIR__.'/config/'.$this->_packageTag.'.php' => base_path('config/'.$this->_packageTag.'.php'),
        ], $this->_packageTag.'-config');

        $this->publishes([
            __DIR__.'/resources/lang' => base_path('resources/lang/vendor/'.$this->_packageTag),
        ], $this->_packageTag.'-lang');
    }
}
