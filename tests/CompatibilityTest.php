<?php

namespace jeremykenedy\LaravelPackagist\Tests;

use Illuminate\Support\Facades\Cache;
use jeremykenedy\LaravelPackagist\App\Services\PackagistApiServices;
use jeremykenedy\LaravelPackagist\LaravelPackagistFacade;

class CompatibilityTest extends TestCase
{
    public function test_facade_resolves_the_registered_service()
    {
        $this->assertInstanceOf(PackagistApiServices::class, LaravelPackagistFacade::getFacadeRoot());
        $this->assertSame($this->app->make(PackagistApiServices::class), $this->app->make('laravelpackagist'));
    }

    public function test_package_details_accept_legacy_vendor_cache_objects()
    {
        Cache::put('acme/first', json_decode(json_encode($this->package())), 10);

        $this->assertSame($this->package(), PackagistApiServices::getVendorsPackageDetails('acme/first'));
    }

    public function test_vendor_totals_accept_legacy_package_cache_json()
    {
        Cache::put('acmepackagistVendorKey', collect(['acme/first']), 10);
        Cache::put('acme/first', json_encode(['package' => $this->package()]), 10);

        $this->assertSame(1200, PackagistApiServices::getVendorsTotalDownloads('acme'));
        $this->assertSame(10, PackagistApiServices::getVendorsTotalStars('acme'));
    }
}
