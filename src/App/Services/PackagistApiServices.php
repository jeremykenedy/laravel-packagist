<?php

namespace jeremykenedy\LaravelPackagist\App\Services;

use Illuminate\Support\Facades\Cache;
use jeremykenedy\LaravelPackagist\App\Traits\PackagistApiTrait;

class PackagistApiServices
{
    use PackagistApiTrait;

    public static function getPackageDownloads($vendorAndPackage = null, $type = null)
    {
        $downloads = self::getSpecificPackageDetail($vendorAndPackage, 'downloads');

        if (! is_array($downloads)) {
            return $downloads;
        }

        if ($type) {
            return $downloads[$type] ?? null;
        }

        return $downloads;
    }

    public static function getPackageDailyDownloads($vendorAndPackage = null)
    {
        return self::getPackageDownloads($vendorAndPackage, 'daily');
    }

    public static function getPackageMonthlyDownloads($vendorAndPackage = null)
    {
        return self::getPackageDownloads($vendorAndPackage, 'monthly');
    }

    public static function getPackageTotalDownloads($vendorAndPackage = null)
    {
        return self::getPackageDownloads($vendorAndPackage, 'total');
    }

    public static function getPackageTotalForks($vendorAndPackage = null)
    {
        return self::getSpecificPackageDetail($vendorAndPackage, 'github_forks');
    }

    public static function getPackageTotalOpenIssues($vendorAndPackage = null)
    {
        return self::getSpecificPackageDetail($vendorAndPackage, 'github_open_issues');
    }

    public static function getPackageTotalRepo($vendorAndPackage = null)
    {
        return self::getSpecificPackageDetail($vendorAndPackage, 'repository');
    }

    public static function getPackageTotalStars($vendorAndPackage = null)
    {
        return self::getSpecificPackageDetail($vendorAndPackage, 'github_stars');
    }

    public static function getPackageTotalWatchers($vendorAndPackage = null)
    {
        return self::getSpecificPackageDetail($vendorAndPackage, 'github_watchers');
    }

    public static function getPackagistVendorRepositoriesList($vendor = null)
    {
        $vendor = $vendor ?: config('laravelpackagist.vendor.default');
        $key = self::assignVendorCacheKey($vendor);

        if (self::checkIfItemIsCached($key)) {
            return collect(Cache::get($key));
        }

        $response = self::curlPackagist(config('laravelpackagist.urls.vendorBase').rawurlencode($vendor));
        $data = json_decode($response ?? '', true);

        if (! isset($data['packageNames']) || ! is_array($data['packageNames'])) {
            return collect();
        }

        $list = collect($data['packageNames']);

        if (config('laravelpackagist.caching.enabled')) {
            Cache::put($key, $list, self::getVendorListCacheTime());
        }

        return $list;
    }

    public static function getVendorPackagesCount($vendor = null)
    {
        return self::getPackagistVendorRepositoriesList($vendor)->count();
    }

    public static function getVendorsPackagesDetails($vendor = null)
    {
        $packages = collect();

        foreach (self::getPackagistVendorRepositoriesList($vendor) as $name) {
            $package = self::getVendorsPackageDetails($name, true);

            if (is_object($package)) {
                $packages->push($package);
            }
        }

        return $packages;
    }

    public static function getVendorsPackageDetails($vendorAndPackage = null, $object = false)
    {
        if (! $vendorAndPackage) {
            return trans('laravelpackagist::laravelpackagist.missing-vendor-package');
        }

        if (! is_string($vendorAndPackage) || ! preg_match('{^[a-z0-9][a-z0-9_.-]*/[a-z0-9][a-z0-9_.-]*$}iD', $vendorAndPackage)) {
            return trans('laravelpackagist::laravelpackagist.malformed-vendor-package');
        }

        $package = self::getCachedPackage($vendorAndPackage);

        if ($package === null) {
            return trans('laravelpackagist::laravelpackagist.package-not-found');
        }

        return $object === true ? $package : json_decode(json_encode($package), true);
    }

    public static function getVendorsTotalDownloads($vendor = null)
    {
        return self::getVendorsPackagesDetails($vendor)->sum(function ($package) {
            return $package->downloads->total ?? 0;
        });
    }

    public static function getVendorsTotalStars($vendor = null)
    {
        return self::getVendorsPackagesDetails($vendor)->sum(function ($package) {
            return $package->favers ?? 0;
        });
    }
}
