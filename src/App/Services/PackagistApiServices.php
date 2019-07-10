<?php

namespace jeremykenedy\LaravelPackagist\App\Services;

use Illuminate\Support\Facades\Cache;
use jeremykenedy\LaravelPackagist\App\Traits\PackagistApiTrait;

class PackagistApiServices
{
    use PackagistApiTrait;

    /**
     * Gets the package downloads.
     *
     * @param string $vendorAndPackage The vendor and package
     *
     * @return int || string || array   The package total downloads.
     */
    public static function getPackageDownloads($vendorAndPackage = null, $type = null)
    {
        $downloads = self::getSpecificPackageDetail($vendorAndPackage, 'downloads');

        if (!is_array($downloads)) {
            return $downloads;
        }

        if ($type) {
            return $downloads[$type];
        }

        return $downloads;
    }

    /**
     * Gets the package daily downloads.
     *
     * @param string $vendorAndPackage The vendor and package
     *
     * @return int || string        The package daily downloads.
     */
    public static function getPackageDailyDownloads($vendorAndPackage = null)
    {
        return self::getPackageDownloads($vendorAndPackage, 'daily');
    }

    /**
     * Gets the package monthly downloads.
     *
     * @param string $vendorAndPackage The vendor and package
     *
     * @return int || string        The package monthly downloads.
     */
    public static function getPackageMonthlyDownloads($vendorAndPackage = null)
    {
        return self::getPackageDownloads($vendorAndPackage, 'monthly');
    }

    /**
     * Gets the package total downloads.
     *
     * @param string $vendorAndPackage The vendor and package
     *
     * @return int || string        The package total downloads.
     */
    public static function getPackageTotalDownloads($vendorAndPackage = null)
    {
        return self::getPackageDownloads($vendorAndPackage, 'total');
    }

    /**
     * Gets the package total forks.
     *
     * @param string $vendorAndPackage The vendor and package
     *
     * @return int || string        The package total forks.
     */
    public static function getPackageTotalForks($vendorAndPackage = null)
    {
        return self::getSpecificPackageDetail($vendorAndPackage, 'github_forks');
    }

    /**
     * Gets the package total open issues.
     *
     * @param string $vendorAndPackage The vendor and package
     *
     * @return string The package open issues.
     */
    public static function getPackageTotalOpenIssues($vendorAndPackage = null)
    {
        return self::getSpecificPackageDetail($vendorAndPackage, 'github_open_issues');
    }

    /**
     * Gets the package repository.
     *
     * @param string $vendorAndPackage The vendor and package
     *
     * @return string The package repository.
     */
    public static function getPackageTotalRepo($vendorAndPackage = null)
    {
        return self::getSpecificPackageDetail($vendorAndPackage, 'repository');
    }

    /**
     * Gets the package total stars.
     *
     * @param string $vendorAndPackage The vendor and package
     *
     * @return int || string        The package total stars.
     */
    public static function getPackageTotalStars($vendorAndPackage = null)
    {
        return self::getSpecificPackageDetail($vendorAndPackage, 'github_stars');
    }

    /**
     * Gets the package total watchers.
     *
     * @param string $vendorAndPackage The vendor and package
     *
     * @return string The package watchers.
     */
    public static function getPackageTotalWatchers($vendorAndPackage = null)
    {
        return self::getSpecificPackageDetail($vendorAndPackage, 'github_watchers');
    }

    /**
     * Gets the packagist vendor repositories list.
     *
     * @param string $vendor The vendor
     *
     * @return collection The packagist vendor repositories list.
     */
    public static function getPackagistVendorRepositoriesList($vendor = null)
    {
        if (!$vendor) {
            $vendor = config('laravelpackagist.vendor.default');
        }

        $cachingEnabled = config('laravelpackagist.caching.enabled');

        if ($cachingEnabled) {
            $vendorKey = self::assignVendorCacheKey($vendor);
            if (self::checkIfItemIsCached($vendorKey)) {
                return Cache::get($vendorKey);
            }
        }

        $baseUrl = config('laravelpackagist.urls.vendorBase').$vendor;
        $response = self::curlPackagist($baseUrl);
        $list = collect(json_decode($response)->packageNames);

        if ($cachingEnabled) {
            Cache::put($vendorKey, $list, self::getVendorListCacheTime());
        }

        return $list;
    }

    /**
     * Gets the vendor packages count.
     *
     * @param string $vendor The vendor
     *
     * @return int The vendor packages count.
     */
    public static function getVendorPackagesCount($vendor = null)
    {
        if (!$vendor) {
            $vendor = config('laravelpackagist.vendor.default');
        }

        return self::getPackagistVendorRepositoriesList($vendor)->count();
    }

    /**
     * Gets the vendors packages details.
     *
     * @param string $vendor The vendor
     *
     * @return collection The vendors packages details.
     */
    public static function getVendorsPackagesDetails($vendor = null)
    {
        if (!$vendor) {
            $vendor = config('laravelpackagist.vendor.default');
        }

        $projects = self::getPackagistVendorRepositoriesList($vendor);
        $vendorProjects = collect([]);
        $cachingEnabled = config('laravelpackagist.caching.enabled');

        foreach ($projects as $project) {
            if (self::checkIfItemIsCached($project) && $cachingEnabled) {
                $item = Cache::get($project);
            } else {
                $baseUrl = config('laravelpackagist.urls.projectPreFix').$project.config('laravelpackagist.urls.projectPostFix');
                $item = json_decode(self::curlPackagist($baseUrl))->package;
                if ($cachingEnabled) {
                    Cache::put($project, $item, self::getVendorListCacheTime());
                }
            }
            $vendorProjects[] = $item;
        }

        return $vendorProjects;
    }

    /**
     * Gets the vendors package details.
     *
     * @param string $vendorAndPackage The vendor and package as 'vendor/package'
     * @param bool   $object           Return as object
     *
     * @return object || Array The vendors package details.
     */
    public static function getVendorsPackageDetails($vendorAndPackage = null, $object = false)
    {
        if (!$vendorAndPackage) {
            return trans('laravelpackagist::laravelpackagist.missing-vendor-package');
        }

        if ((!strstr($vendorAndPackage, '/')) || (count(explode('/', $vendorAndPackage)) != 2)) {
            return trans('laravelpackagist::laravelpackagist.malformed-vendor-package');
        }

        $decode = true;
        if ($object == true) {
            $decode = false;
        }

        $packageFound = false;
        $packageDetails = trans('laravelpackagist::laravelpackagist.package-not-found');
        $cachingEnabled = config('laravelpackagist.caching.enabled');

        if (self::checkIfItemIsCached($vendorAndPackage) && $cachingEnabled) {
            $item = Cache::get($vendorAndPackage);
            $packageFound = true;
        } else {
            $baseUrl = config('laravelpackagist.urls.projectPreFix').$vendorAndPackage.config('laravelpackagist.urls.projectPostFix');
            $item = self::curlPackagist($baseUrl);

            if ($item != '{"status":"error","message":"Package not found"}') {
                $packageFound = true;
            }

            if ($packageFound && $cachingEnabled) {
                Cache::put($vendorAndPackage, $item, self::getVendorListCacheTime());
            }
        }

        if ($packageFound) {
            $packageDetails = json_decode(json_encode(json_decode($item)->package), $decode);
        }

        return $packageDetails;
    }

    /**
     * Gets the vendors total downloads.
     *
     * @param string $vendor The vendor
     *
     * @return int The vendors total downloads.
     */
    public static function getVendorsTotalDownloads($vendor = null)
    {
        if (!$vendor) {
            $vendor = config('laravelpackagist.vendor.default');
        }

        $totalDownloads = 0;
        $vendorProjects = self::getVendorsPackagesDetails($vendor);

        foreach ($vendorProjects as $vendorProject) {
            $totalDownloads += $vendorProject->downloads->total;
        }

        return $totalDownloads;
    }

    /**
     * Gets the vendors total stars.
     *
     * @param string $vendor The vendor
     *
     * @return int The vendors total stars.
     */
    public static function getVendorsTotalStars($vendor = null)
    {
        if (!$vendor) {
            $vendor = config('laravelpackagist.vendor.default');
        }

        $totalStars = 0;
        $vendorProjects = self::getVendorsPackagesDetails($vendor);

        foreach ($vendorProjects as $vendorProject) {
            $totalStars += $vendorProject->favers;
        }

        return $totalStars;
    }
}
