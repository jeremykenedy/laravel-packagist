<?php

namespace jeremykenedy\LaravelPackagist;

use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;
use jeremykenedy\LaravelPackagist\App\Services\PackagistApiServices;
use jeremykenedy\LaravelPackagist\Console\InstallCommand;
use jeremykenedy\LaravelPackagist\Console\UpdateCommand;
use jeremykenedy\LaravelPackagist\Contracts\PackagistClient;
use jeremykenedy\LaravelPackagist\Http\CurlPackagistClient;

class LaravelPackagistServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadTranslationsFrom(__DIR__.'/resources/lang', 'laravelpackagist');
        $this->publishes([
            __DIR__.'/config/laravelpackagist.php' => $this->app->configPath('laravelpackagist.php'),
        ], 'laravelpackagist-config');
        $this->publishes([
            __DIR__.'/resources/lang' => $this->app->langPath().'/vendor/laravelpackagist',
        ], 'laravelpackagist-lang');

        if ($this->app->runningInConsole()) {
            $this->commands([InstallCommand::class, UpdateCommand::class]);
        }
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/config/laravelpackagist.php', 'laravelpackagist');
        $this->app->singleton(PackagistClient::class, CurlPackagistClient::class);
        $this->app->singleton(PackagistApiServices::class);
        $this->app->alias(PackagistApiServices::class, 'laravelpackagist');
        AliasLoader::getInstance()->alias('PackagistApiServices', PackagistApiServices::class);
    }

    public function provides()
    {
        return ['laravelpackagist'];
    }
}
