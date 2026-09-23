<?php

namespace jeremykenedy\LaravelPackagist\Tests;

use jeremykenedy\LaravelPackagist\LaravelPackagistServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app)
    {
        return [LaravelPackagistServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('cache.default', 'array');
        $app['config']->set('laravelpackagist.caching.enabled', true);
    }

    protected function package($name = 'acme/first')
    {
        return [
            'name' => $name,
            'description' => 'An example package.',
            'repository' => 'https://github.com/'.$name,
            'downloads' => ['total' => 1200, 'monthly' => 120, 'daily' => 12],
            'github_forks' => 3,
            'github_open_issues' => 2,
            'github_stars' => 9,
            'github_watchers' => 4,
            'favers' => 10,
        ];
    }
}
