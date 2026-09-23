<?php

namespace jeremykenedy\LaravelPackagist\Tests;

use jeremykenedy\LaravelPackagist\Contracts\PackagistClient;
use jeremykenedy\LaravelPackagist\Http\CurlPackagistClient;
use jeremykenedy\LaravelPackagist\LaravelPackagistServiceProvider;

class ServiceProviderTest extends TestCase
{
    public function test_http_client_binding_resolves_with_framework_dependencies()
    {
        $this->assertInstanceOf(CurlPackagistClient::class, $this->app->make(PackagistClient::class));
        $this->assertSame($this->app->make(PackagistClient::class), $this->app->make(PackagistClient::class));
    }

    public function test_existing_configuration_keeps_custom_values_and_receives_nested_defaults()
    {
        $this->app['config']->set('laravelpackagist', [
            'curl' => ['timeout' => 7, 'maxredirects' => 0],
            'vendor' => ['default' => 'custom'],
            'caching' => ['enabled' => false],
        ]);

        (new LaravelPackagistServiceProvider($this->app))->register();

        $this->assertSame(7, config('laravelpackagist.curl.timeout'));
        $this->assertSame(0, config('laravelpackagist.curl.maxredirects'));
        $this->assertSame(300, config('laravelpackagist.curl.connectTimeout'));
        $this->assertSame(0, config('laravelpackagist.curl.retries'));
        $this->assertSame('custom', config('laravelpackagist.vendor.default'));
        $this->assertFalse(config('laravelpackagist.caching.enabled'));
        $this->assertSame(100, config('laravelpackagist.caching.vendorItemCacheTime'));
    }
}
