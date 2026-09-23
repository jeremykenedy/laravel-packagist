<?php

namespace jeremykenedy\LaravelPackagist\App\Traits;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use jeremykenedy\LaravelPackagist\Http\PackagistHttp;
use stdClass;

trait PackagistApiTrait
{
    private static function curlPackagist($baseUrl)
    {
        return PackagistHttp::get($baseUrl);
    }

    private static function checkIfItemIsCached($key = null)
    {
        return config('laravelpackagist.caching.enabled') && Cache::has($key);
    }

    private static function assignVendorCacheKey($key)
    {
        return $key.'packagistVendorKey';
    }

    private static function getSpecificPackageDetail($vendorAndPackage, $detail = null)
    {
        $package = self::getVendorsPackageDetails($vendorAndPackage);

        return is_array($package) ? ($package[$detail] ?? null) : $package;
    }

    private static function getVendorListCacheTime($minutes = null)
    {
        return Carbon::now()->addMinutes((int) ($minutes ?? config('laravelpackagist.caching.vendorListCacheTime', 100)));
    }

    private static function getCachedPackage($name)
    {
        if (self::checkIfItemIsCached($name)) {
            $package = self::decodePackage(Cache::get($name));

            if ($package !== null) {
                return $package;
            }
        }

        $url = config('laravelpackagist.urls.projectPreFix').$name.config('laravelpackagist.urls.projectPostFix');
        $response = self::curlPackagist($url);
        $package = self::decodePackage($response);

        if ($package !== null && config('laravelpackagist.caching.enabled')) {
            Cache::put($name, $response, self::getVendorListCacheTime(config('laravelpackagist.caching.vendorItemCacheTime', 100)));
        }

        return $package;
    }

    private static function decodePackage($value)
    {
        $data = is_string($value) ? json_decode($value) : json_decode(json_encode($value));
        $package = $data instanceof stdClass ? ($data->package ?? $data) : null;

        return $package instanceof stdClass && isset($package->name) ? $package : null;
    }
}
