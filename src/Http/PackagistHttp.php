<?php

namespace jeremykenedy\LaravelPackagist\Http;

use Illuminate\Support\Facades\Facade;
use jeremykenedy\LaravelPackagist\Contracts\PackagistClient;

class PackagistHttp extends Facade
{
    protected static function getFacadeAccessor()
    {
        return PackagistClient::class;
    }
}
