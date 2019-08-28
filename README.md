
# Laravel Packagist

[![Latest Stable Version](https://poser.pugx.org/jeremykenedy/laravel-packagist/v/stable.svg)](https://packagist.org/packages/jeremykenedy/laravel-packagist)
[![Total Downloads](https://poser.pugx.org/jeremykenedy/laravel-packagist/d/total.svg)](https://packagist.org/packages/jeremykenedy/laravel-packagist)
[![Travis-CI Build](https://travis-ci.org/jeremykenedy/laravel-packagist.svg?branch=master)](https://travis-ci.org/jeremykenedy/laravel-packagist)
[![StyleCI](https://github.styleci.io/repos/194171634/shield?branch=master)](https://github.styleci.io/repos/194171634)
[![Scrutinizer Build Status](https://scrutinizer-ci.com/g/jeremykenedy/laravel-packagist/badges/build.png?b=master)](https://scrutinizer-ci.com/g/jeremykenedy/laravel-packagist/build-status/master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/jeremykenedy/laravel-packagist/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/jeremykenedy/laravel-packagist/?branch=master)
[![MadeWithLaravel.com shield](https://madewithlaravel.com/storage/repo-shields/1573-shield.svg)](https://madewithlaravel.com/p/laravel-packagist/shield-link)
[![License](https://poser.pugx.org/jeremykenedy/laravel-packagist/license)](https://packagist.org/packages/jeremykenedy/laravel-packagist)

#### Table of contents
- [About](#about)
- [Features](#features)
- [Requirements](#requirements)
- [Installation Instructions](#installation-instructions)
    - [Publish All Assets](#publish-all-assets)
    - [Publish Specific Assets](#publish-specific-assets)
- [Usage](#usage)
- [Configuration](#configuration)
    - [Environment File](#environment-file)
- [File Tree](#file-tree)
- [License](#license)

### About
Laravel Packagist (LaravelPackagist) is a package for Laravel 5 to interact with the packagist api quickly and easily.

### Features
| Laravel Packagist Features  |
| :------------ |
|Quicky start pulling vendor data from packagist via the API|
|Quicky start pulling package data from packagist via the API|
|Can use laravel built in cache to make it even faster|
|Config options extend to `.env` file|
|Uses [localization](https://laravel.com/docs/5.8/localization) language files|

### Requirements
* [Laravel 5.4, 5.5, 5.6, 5.7, or 5.8+](https://laravel.com/docs/installation)

### Installation Instructions
1. From your projects root folder in terminal run:

    ```bash
        composer require jeremykenedy/laravel-packagist
    ```

2. Register the package

* Laravel 5.5 and up
Uses package auto discovery feature, no need to edit the `config/app.php` file.

* Laravel 5.4 and below
Register the package with laravel in `config/app.php` under `providers` with the following:

```php
    'providers' => [
        jeremykenedy\LaravelPackagist\LaravelPackagistServiceProvider::class,
    ];
```

3. Optionally publish the packages views, config file, assets, and language files by running the following from your projects root folder:

#### Publish All Assets
```bash
    php artisan vendor:publish --provider="jeremykenedy\LaravelPackagist\LaravelPackagistServiceProvider"
```

#### Publish Specific Assets
```bash
    php artisan vendor:publish --tag=laravelpackagist-config
    php artisan vendor:publish --tag=laravelpackagist-lang
```

### Usage
1. Add the following to the head of the file you are calling the methods from:
```
use jeremykenedy\LaravelPackagist\App\Services\PackagistApiServices;
```

File Example:
```
<?php

namespace App\Services\Sections;

use jeremykenedy\LaravelPackagist\App\Services\PackagistApiServices;
```

2. Call the methods with the following:
```php
// Vendors
PackagistApiServices::getPackagistVendorRepositoriesList('VENDOR-NAME-HERE');
PackagistApiServices::getVendorPackagesCount('VENDOR-NAME-HERE');
PackagistApiServices::getVendorsPackagesDetails('VENDOR-NAME-HERE');
PackagistApiServices::getVendorsTotalDownloads('VENDOR-NAME-HERE');
PackagistApiServices::getVendorsTotalStars('VENDOR-NAME-HERE');

// Individual Packages
PackagistApiServices::getPackageDownloads('VENDOR-NAME-HERE/PACKAGE-NAME-HERE');
PackagistApiServices::getPackageDailyDownloads('VENDOR-NAME-HERE/PACKAGE-NAME-HERE');
PackagistApiServices::getPackageMonthlyDownloads('VENDOR-NAME-HERE/PACKAGE-NAME-HERE');
PackagistApiServices::getPackageTotalDownloads('VENDOR-NAME-HERE/PACKAGE-NAME-HERE');
PackagistApiServices::getPackageTotalForks('VENDOR-NAME-HERE/PACKAGE-NAME-HERE');
PackagistApiServices::getPackageTotalOpenIssues('VENDOR-NAME-HERE/PACKAGE-NAME-HERE');
PackagistApiServices::getPackageTotalRepo('VENDOR-NAME-HERE/PACKAGE-NAME-HERE');
PackagistApiServices::getPackageTotalStars('VENDOR-NAME-HERE/PACKAGE-NAME-HERE');
PackagistApiServices::getPackageTotalWatchers('VENDOR-NAME-HERE/PACKAGE-NAME-HERE');
PackagistApiServices::getVendorsPackageDetails('VENDOR-NAME-HERE/PACKAGE-NAME-HERE');
```

### Configuration
There are many configurable options which have all been extended to be able to configured via `.env` file variables. Editing the configuration file directly is not needed becuase of this.

* See config file: [laravelpackagist.php]().

```php

<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Packagist Caching Settings
    |--------------------------------------------------------------------------
    */
    'caching' => [
        'enabled'               => env('PACKAGIST_CACHE_ENABLED', TRUE),
        'vendorListCacheTime'   => env('PACKAGIST_VENDOR_LIST_CACHE_TIME_MINUTES', 100),
        'vendorItemCacheTime'   => env('PACKAGIST_VENDOR_ITEM_CACHE_TIME_MINUTES', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | Packagist CURL Settings
    |--------------------------------------------------------------------------
    */
    'curl' => [
        'timeout'       => env('PACKAGIST_CURL_TIMEOUT', 30),
        'maxredirects'  => env('PACKAGIST_CURL_MAX_REDIRECTS', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Packagist API URLS
    |--------------------------------------------------------------------------
    */
    'urls' => [
        'vendorBase' => env('PACKAGIST_API_VENDOR_URL_BASE', 'https://packagist.org/packages/list.json?vendor='),
        'projectPreFix' => env('PACKAGIST_API_VENDOR_PROJECT_BASE_PREFIX', 'https://packagist.org/packages/'),
        'projectPostFix' => env('PACKAGIST_API_VENDOR_PROJECT_BASE_POSTFIX', '.json'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Packagist default vendor
    |--------------------------------------------------------------------------
    */
    'vendor' => [
        'default' => env('PACKAGIST_DEFAULT_VENDOR', 'jeremykenedy'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Packagist logging
    |--------------------------------------------------------------------------
    */
    'logging' => [
        'curlErrors' => env('PACKAGIST_LOG_CURL_ERROR', TRUE),
    ],
```

##### Environment File
```dotenv
PACKAGIST_CACHE_ENABLED=TRUE
PACKAGIST_VENDOR_LIST_CACHE_TIME_MINUTES=100
PACKAGIST_VENDOR_ITEM_CACHE_TIME_MINUTES=100
PACKAGIST_CURL_TIMEOUT=30
PACKAGIST_CURL_MAX_REDIRECTS=10
PACKAGIST_API_VENDOR_URL_BASE='https://packagist.org/packages/list.json?vendor='
PACKAGIST_API_VENDOR_PROJECT_BASE_PREFIX='https://packagist.org/packages/'
PACKAGIST_API_VENDOR_PROJECT_BASE_POSTFIX='.json'
PACKAGIST_DEFAULT_VENDOR='jeremykenedy'
PACKAGIST_LOG_CURL_ERROR=TRUE
```

### File Tree
```bash
├── .gitignore..git
├── .travis.yml
├── LICENSE
├── README.md
├── composer.json
├── phpunit.xml
└── src
    ├── App
    │   ├── Services
    │   │   └── PackagistApiServices.php
    │   └── Traits
    │       └── PackagistApiTrait.php
    ├── LaravelPackagistFacade.php
    ├── LaravelPackagistServiceProvider.php
    ├── config
    │   └── laravelpackagist.php
    └── resources
        └── lang
            └── en
                └── laravelpackagist.php
```

* Tree command can be installed using brew: `brew install tree`
* File tree generated using command `tree -a -I '.git|node_modules|vendor|storage|tests'`

### License
Laravel Packagist is licensed under the [MIT license](https://opensource.org/licenses/MIT). Enjoy!
