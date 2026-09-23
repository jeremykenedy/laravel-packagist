<?php

namespace jeremykenedy\LaravelPackagist\Tests;

use Illuminate\Config\Repository;
use jeremykenedy\LaravelPackagist\Http\CurlPackagistClient;
use Psr\Log\LoggerInterface;
use RuntimeException;

class CurlPackagistClientTest extends TestCase
{
    private function withServer($test)
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0');
        $address = stream_socket_get_name($socket, false);
        fclose($socket);
        $process = proc_open(escapeshellarg(PHP_BINARY).' -S '.escapeshellarg($address).' '.escapeshellarg(__DIR__.'/Fixtures/router.php'), [0 => ['pipe', 'r'], 1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']], $pipes);
        fclose($pipes[0]);

        try {
            for ($attempt = 0; $attempt < 100; $attempt++) {
                $connection = @stream_socket_client('tcp://'.$address, $code, $error, 0.1);

                if ($connection !== false) {
                    fclose($connection);
                    $test('http://'.$address);

                    return;
                }

                usleep(10000);
            }

            throw new RuntimeException('The local HTTP fixture server did not start.');
        } finally {
            proc_terminate($process);
            proc_close($process);
        }
    }

    private function client($options = [], $logging = true)
    {
        $logger = \Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('error')->never();

        return new CurlPackagistClient(new Repository(['laravelpackagist' => ['curl' => $options, 'logging' => ['curlErrors' => $logging]]]), $logger);
    }

    public function test_successful_requests_use_json_headers_and_return_the_body()
    {
        $this->withServer(function ($url) {
            $this->assertSame(['ok' => true, 'accept' => 'application/json'], json_decode($this->client()->get($url.'/success'), true));
        });
    }

    public function test_http_errors_and_redirects_are_not_treated_as_package_data()
    {
        $this->withServer(function ($url) {
            foreach ([404, 429, 500, 503] as $status) {
                $this->assertNull($this->client()->get($url.'/status?code='.$status));
            }

            $this->assertNull($this->client()->get($url.'/redirect'));
        });
    }

    public function test_transient_failures_are_retried_when_enabled()
    {
        $counter = tempnam(sys_get_temp_dir(), 'packagist-retry-');

        try {
            $this->withServer(function ($url) use ($counter) {
                $response = $this->client(['retries' => 2])->get($url.'/retry?counter='.rawurlencode($counter));
                $this->assertSame(['attempt' => 3], json_decode($response, true));
                $this->assertSame('3', file_get_contents($counter));
            });
        } finally {
            unlink($counter);
        }
    }

    public function test_retries_are_disabled_by_default()
    {
        $counter = tempnam(sys_get_temp_dir(), 'packagist-retry-');

        try {
            $this->withServer(function ($url) use ($counter) {
                $this->assertNull($this->client()->get($url.'/retry?counter='.rawurlencode($counter)));
                $this->assertSame('1', file_get_contents($counter));
            });
        } finally {
            unlink($counter);
        }
    }

    public function test_retries_stop_at_the_limit_and_skip_permanent_errors()
    {
        foreach ([404 => 1, 429 => 2, 503 => 2] as $status => $attempts) {
            $counter = tempnam(sys_get_temp_dir(), 'packagist-retry-');

            try {
                $this->withServer(function ($url) use ($counter, $status, $attempts) {
                    $response = $this->client(['retries' => 1])->get($url.'/retry?counter='.rawurlencode($counter).'&code='.$status);

                    $this->assertNull($response);
                    $this->assertSame((string) $attempts, file_get_contents($counter));
                });
            } finally {
                unlink($counter);
            }
        }
    }

    public function test_connection_errors_are_logged_once()
    {
        $logger = \Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('error')->once()->with(\Mockery::type('string'));
        $client = new CurlPackagistClient(new Repository, $logger);

        $this->assertNull($client->get('http://127.0.0.1:0'));
    }

    public function test_connection_error_logging_can_be_disabled()
    {
        $this->assertNull($this->client([], false)->get('http://127.0.0.1:0'));
    }

    public function test_response_timeout_is_enforced()
    {
        $this->withServer(function ($url) {
            $started = microtime(true);
            $this->assertNull($this->client(['timeout' => 1], false)->get($url.'/slow'));
            $this->assertLessThan(1.8, microtime(true) - $started);
        });
    }

    public function test_zero_timeout_keeps_the_existing_unlimited_response_timeout()
    {
        $this->withServer(function ($url) {
            $response = $this->client(['timeout' => 0])->get($url.'/slow');

            $this->assertTrue(json_decode($response, true)['ok']);
        });
    }
}
