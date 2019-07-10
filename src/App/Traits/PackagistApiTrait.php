<?php

namespace jeremykenedy\LaravelPackagist\App\Traits;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

trait PackagistApiTrait
{
    /**
     * Curl the Packagist API.
     *
     * @param string $baseUrl The base url
     *
     * @return object || string || null description_of_the_return_value
     */
    private static function curlPackagist($baseUrl)
    {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => $baseUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => config('laravelpackagist.curl.maxredirects'),
            CURLOPT_TIMEOUT        => config('laravelpackagist.curl.timeout'),
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'GET',
            CURLOPT_HTTPHEADER     => [
                'Accept: application/json',
                'cache-control: no-cache',
            ],
        ]);
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        if ($err) {
            if (config('laravelpackagist.logging.curlErrors')) {
                Log::error($err);
            }

            return;
        }

        return $response;
    }

    /**
     * Check if packagist vendor list exists in the cache.
     *
     * @return bool
     */
    private static function checkIfItemIsCached($key = null)
    {
        $cachingEnabled = config('laravelpackagist.caching.enabled');

        if (!$cachingEnabled) {
            return false;
        }

        if (Cache::has($key)) {
            return true;
        }

        return false;
    }

    /**
     * Set the vendor cache key.
     *
     * @param string $key The key
     */
    private static function assignVendorCacheKey($key)
    {
        $keyPlug = 'packagistVendorKey';

        return $key.$keyPlug;
    }

    /**
     * Gets the specific package detail.
     *
     * @param string $vendorAndPackage The vendor and package
     * @param string $detail           The detail
     *
     * @return string The specific package detail.
     */
    private static function getSpecificPackageDetail($vendorAndPackage, $detail = null)
    {
        $packageDetails = self::getVendorsPackageDetails($vendorAndPackage);

        if (!is_array($packageDetails)) {
            return $packageDetails;
        }

        return $packageDetails[$detail];
    }

    /**
     * Gets the vendor list cache time.
     *
     * @param int $minutes The Minutes
     *
     * @return dateTime The vendor list cache time.
     */
    private static function getVendorListCacheTime($minutes = null)
    {
        if ($minutes === null) {
            $minutes = config('laravelpackagist.caching.vendorListCacheTime');
        }

        return now()->addMinutes($minutes);
    }
}
