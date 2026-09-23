<?php

namespace jeremykenedy\LaravelPackagist\Tests\Fakes;

use jeremykenedy\LaravelPackagist\Contracts\PackagistClient as ClientContract;
use RuntimeException;

class PackagistClient implements ClientContract
{
    public $responses = [];

    public $requests = [];

    public function get($url)
    {
        $this->requests[] = $url;

        if (! array_key_exists($url, $this->responses)) {
            throw new RuntimeException('Unexpected HTTP request: '.$url);
        }

        return $this->responses[$url];
    }
}
