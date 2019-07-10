<?php

namespace jeremykenedy\LaravelPackagist\Test;

use jeremykenedy\LaravelPackagist\LaravelPackagistServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

class TestCase extends OrchestraTestCase
{
    /**
     * Load package service provider.
     *
     * @param \Illuminate\Foundation\Application $app
     *
     * @return jeremykenedy\LaravelPackagist\LaravelPackagistServiceProvider
     */
    protected function getPackageProviders($app)
    {
        return [LaravelPackagistServiceProvider::class];
    }

    /**
     * Load package alias.
     *
     * @param \Illuminate\Foundation\Application $app
     *
     * @return array
     */
    protected function getPackageAliases($app)
    {
        return [
            'laravelpackagist',
        ];
    }
}
