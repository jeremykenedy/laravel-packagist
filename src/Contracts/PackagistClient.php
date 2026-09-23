<?php

namespace jeremykenedy\LaravelPackagist\Contracts;

interface PackagistClient
{
    public function get($url);
}
