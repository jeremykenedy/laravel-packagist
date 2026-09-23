<?php

namespace jeremykenedy\LaravelPackagist;

use Illuminate\Support\Facades\Facade;

class LaravelPackagistFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'laravelpackagist';
    }
}
