<?php

namespace jeremykenedy\LaravelPackagist\Tests;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use jeremykenedy\LaravelPackagist\App\Services\PackagistApiServices as Packagist;
use jeremykenedy\LaravelPackagist\Http\PackagistHttp;
use jeremykenedy\LaravelPackagist\LaravelPackagistFacade;
use jeremykenedy\LaravelPackagist\Tests\Fakes\PackagistClient;

class PackagistApiTest extends TestCase
{
    private function fake($responses = [])
    {
        $client = new PackagistClient;
        $client->responses = $responses;
        PackagistHttp::swap($client);

        return $client;
    }

    private function packageUrl($name = 'acme/first')
    {
        return 'https://packagist.org/packages/'.$name.'.json';
    }

    public function test_all_package_statistics_keep_their_public_return_values()
    {
        $client = $this->fake([$this->packageUrl() => json_encode(['package' => $this->package()])]);
        $methods = [
            'getPackageDailyDownloads' => 12,
            'getPackageMonthlyDownloads' => 120,
            'getPackageTotalDownloads' => 1200,
            'getPackageTotalForks' => 3,
            'getPackageTotalOpenIssues' => 2,
            'getPackageTotalRepo' => 'https://github.com/acme/first',
            'getPackageTotalStars' => 9,
            'getPackageTotalWatchers' => 4,
        ];

        foreach ($methods as $method => $expected) {
            $this->assertSame($expected, Packagist::$method('acme/first'));
        }

        $this->assertSame($this->package()['downloads'], Packagist::getPackageDownloads('acme/first'));
        $this->assertSame($this->package(), Packagist::getVendorsPackageDetails('acme/first'));
        $this->assertEquals((object) json_decode(json_encode($this->package())), Packagist::getVendorsPackageDetails('acme/first', true));
        $this->assertSame(1200, LaravelPackagistFacade::getPackageTotalDownloads('acme/first'));
        $this->assertSame(1200, \PackagistApiServices::getPackageTotalDownloads('acme/first'));
        $this->assertCount(1, $client->requests);
    }

    public function test_vendor_and_package_calls_share_cache_in_either_order()
    {
        foreach ([true, false] as $packageFirst) {
            Cache::flush();
            $client = $this->fake([
                'https://packagist.org/packages/list.json?vendor=acme' => '{"packageNames":["acme/first","acme/second"]}',
                $this->packageUrl() => json_encode(['package' => $this->package()]),
                $this->packageUrl('acme/second') => json_encode(['package' => $this->package('acme/second')]),
            ]);
            $this->app['config']->set('laravelpackagist.vendor.default', 'acme');

            if ($packageFirst) {
                $this->assertSame(1200, Packagist::getPackageTotalDownloads('acme/first'));
            }

            $this->assertSame(2, Packagist::getVendorPackagesCount());
            $this->assertSame(2400, Packagist::getVendorsTotalDownloads());
            $this->assertSame(20, Packagist::getVendorsTotalStars());
            $this->assertInstanceOf(Collection::class, Packagist::getVendorsPackagesDetails());
            $this->assertSame('acme/first', Packagist::getVendorsPackagesDetails()->first()->name);
            $this->assertSame($this->package(), Packagist::getVendorsPackageDetails('acme/first'));
            $this->assertCount(3, $client->requests);
        }
    }

    public function test_disabled_cache_is_neither_read_nor_written()
    {
        $this->app['config']->set('laravelpackagist.caching.enabled', false);
        Cache::shouldReceive('has')->never();
        Cache::shouldReceive('get')->never();
        Cache::shouldReceive('put')->never();
        $client = $this->fake([
            'https://packagist.org/packages/list.json?vendor=acme' => '{"packageNames":["acme/first"]}',
            $this->packageUrl() => json_encode(['package' => $this->package()]),
        ]);

        $this->assertSame(1200, Packagist::getVendorsTotalDownloads('acme'));
        $this->assertSame(1200, Packagist::getPackageTotalDownloads('acme/first'));
        $this->assertSame(1, Packagist::getVendorPackagesCount('acme'));
        $this->assertCount(4, $client->requests);
    }

    public function test_item_and_list_cache_lifetimes_are_minutes_and_independent()
    {
        $this->app['config']->set('laravelpackagist.caching.vendorListCacheTime', '17');
        $this->app['config']->set('laravelpackagist.caching.vendorItemCacheTime', '43');
        $now = Carbon::parse('2026-01-02 12:00:00');
        Carbon::setTestNow($now);

        try {
            Cache::shouldReceive('has')->andReturn(false);
            Cache::shouldReceive('put')->once()->with('acmepackagistVendorKey', \Mockery::type(Collection::class), \Mockery::on(function ($expiry) use ($now) {
                return $expiry->eq($now->copy()->addMinutes(17));
            }));
            Cache::shouldReceive('put')->once()->with('acme/first', \Mockery::type('string'), \Mockery::on(function ($expiry) use ($now) {
                return $expiry->eq($now->copy()->addMinutes(43));
            }));
            $this->fake([
                'https://packagist.org/packages/list.json?vendor=acme' => '{"packageNames":["acme/first"]}',
                $this->packageUrl() => json_encode(['package' => $this->package()]),
            ]);

            $this->assertSame(1200, Packagist::getVendorsTotalDownloads('acme'));
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_invalid_package_names_return_translations_without_requests()
    {
        $client = $this->fake();
        $this->assertSame('Missing "vendor/package"', Packagist::getVendorsPackageDetails());

        foreach (['acme', 'acme/first/extra', '/first', 'acme/', '../first', 'acme/first?x=1', 'acme/first#fragment', ['acme', 'first']] as $name) {
            $this->assertSame('Malformed "vendor/package"', Packagist::getVendorsPackageDetails($name));
        }

        $this->assertSame('Missing "vendor/package"', Packagist::getPackageTotalDownloads());
        $this->assertSame([], $client->requests);
    }

    public function test_failed_responses_are_not_cached_and_can_recover()
    {
        foreach ([null, '', 'not json', '{"status":"error","message":"Package not found"}', '{"package":null}', '{"package":[]}', '[]'] as $body) {
            Cache::flush();
            $client = $this->fake([$this->packageUrl() => $body]);
            $this->assertSame('Packagist package not found.', Packagist::getVendorsPackageDetails('acme/first'));
            $this->assertFalse(Cache::has('acme/first'));
            $client->responses[$this->packageUrl()] = json_encode(['package' => $this->package()]);
            $this->assertSame(1200, Packagist::getPackageTotalDownloads('acme/first'));
            $this->assertCount(2, $client->requests);
        }
    }

    public function test_invalid_cached_entries_are_refetched()
    {
        Cache::put('acme/first', 'invalid', 10);
        $client = $this->fake([$this->packageUrl() => json_encode(['package' => $this->package()])]);

        $this->assertSame($this->package(), Packagist::getVendorsPackageDetails('acme/first'));
        $this->assertCount(1, $client->requests);
    }

    public function test_missing_optional_statistics_return_null()
    {
        $this->fake([$this->packageUrl() => '{"package":{"name":"acme/first","downloads":{}}}']);

        $this->assertNull(Packagist::getPackageTotalStars('acme/first'));
        $this->assertNull(Packagist::getPackageDailyDownloads('acme/first'));
        $this->assertNull(Packagist::getPackageDownloads('acme/first', 'unknown'));
    }

    public function test_failed_vendor_lists_are_empty_and_retried_on_the_next_call()
    {
        $url = 'https://packagist.org/packages/list.json?vendor=acme';

        foreach ([null, 'not json', '{"packageNames":null}', '{"packageNames":"invalid"}'] as $body) {
            $client = $this->fake([$url => $body]);
            $this->assertSame([], Packagist::getPackagistVendorRepositoriesList('acme')->all());
            $this->assertFalse(Cache::has('acmepackagistVendorKey'));
            $this->assertSame(0, Packagist::getVendorPackagesCount('acme'));
            $this->assertCount(2, $client->requests);
        }
    }

    public function test_empty_vendor_lists_are_cached()
    {
        $client = $this->fake(['https://packagist.org/packages/list.json?vendor=acme' => '{"packageNames":[]}']);

        $this->assertSame(0, Packagist::getVendorPackagesCount('acme'));
        $this->assertSame(0, Packagist::getVendorsTotalStars('acme'));
        $this->assertCount(1, $client->requests);
    }

    public function test_vendor_totals_skip_unavailable_packages_and_missing_statistics()
    {
        $this->fake([
            'https://packagist.org/packages/list.json?vendor=acme' => '{"packageNames":["acme/first","acme/second","acme/third"]}',
            $this->packageUrl() => json_encode(['package' => $this->package()]),
            $this->packageUrl('acme/second') => null,
            $this->packageUrl('acme/third') => '{"package":{"name":"acme/third"}}',
        ]);

        $this->assertSame(1200, Packagist::getVendorsTotalDownloads('acme'));
        $this->assertSame(10, Packagist::getVendorsTotalStars('acme'));
        $this->assertCount(2, Packagist::getVendorsPackagesDetails('acme'));
    }

    public function test_custom_urls_and_vendor_query_encoding_are_respected()
    {
        $this->app['config']->set('laravelpackagist.urls', [
            'vendorBase' => 'https://mirror.example/list?vendor=',
            'projectPreFix' => 'https://mirror.example/packages/',
            'projectPostFix' => '?format=json',
        ]);
        $client = $this->fake([
            'https://mirror.example/list?vendor=acme%26type%3Dlibrary' => '{"packageNames":["acme/first"]}',
            'https://mirror.example/packages/acme/first?format=json' => json_encode(['package' => $this->package()]),
        ]);

        $this->assertSame(1200, Packagist::getVendorsTotalDownloads('acme&type=library'));
        $this->assertCount(2, $client->requests);
    }
}
