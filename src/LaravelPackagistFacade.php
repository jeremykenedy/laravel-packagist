<?php

namespace jeremykenedy\LaravelPackagist;

use Illuminate\Support\Facades\Facade;

class LaravelPackagistFacade extends Facade
{
    /**
     * Gets the facade accessor.
     *
     * @return string The facade accessor.
     */
    protected static function getFacadeAccessor()
    {
        return 'laravelpackagist';
    }
}
