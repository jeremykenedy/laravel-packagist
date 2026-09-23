<?php

namespace jeremykenedy\LaravelPackagist\Http;

use Illuminate\Support\Facades\Facade;
use jeremykenedy\LaravelPackagist\Contracts\PackagistClient;

class PackagistHttp extends Facade
{
    public static function get($url)
    {
        return static::getFacadeRoot()->get($url);
    }

    protected static function getFacadeAccessor()
    {
        return PackagistClient::class;
    }
}
