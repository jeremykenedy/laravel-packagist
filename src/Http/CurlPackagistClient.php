<?php

namespace jeremykenedy\LaravelPackagist\Http;

use Illuminate\Contracts\Config\Repository;
use jeremykenedy\LaravelPackagist\Contracts\PackagistClient;
use Psr\Log\LoggerInterface;

class CurlPackagistClient implements PackagistClient
{
    private $config;

    private $logger;

    public function __construct(Repository $config, LoggerInterface $logger)
    {
        $this->config = $config;
        $this->logger = $logger;
    }

    public function get($url)
    {
        $retries = max(0, min(5, (int) $this->config->get('laravelpackagist.curl.retries', 0)));
        $error = '';

        for ($attempt = 0; $attempt <= $retries; $attempt++) {
            [$body, $status, $error] = $this->request($url);

            if ($this->isSuccessful($status, $error)) {
                return $body;
            }

            if ($attempt === $retries || ! $this->shouldRetry($status, $error)) {
                break;
            }

            usleep(100000 * (2 ** $attempt));
        }

        $this->logError($error);

        return null;
    }

    private function isSuccessful($status, $error)
    {
        return $error === '' && $status >= 200 && $status < 300;
    }

    private function shouldRetry($status, $error)
    {
        return $error !== '' || $status === 429 || $status >= 500;
    }

    private function logError($error)
    {
        if ($error !== '' && $this->config->get('laravelpackagist.logging.curlErrors', true)) {
            $this->logger->error($error);
        }
    }

    private function request($url)
    {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => (int) $this->config->get('laravelpackagist.curl.maxredirects', 10),
            CURLOPT_TIMEOUT => max(0, (int) $this->config->get('laravelpackagist.curl.timeout', 30)),
            CURLOPT_CONNECTTIMEOUT => max(0, (int) $this->config->get('laravelpackagist.curl.connectTimeout', 300)),
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_HTTPGET => true,
            CURLOPT_HTTPHEADER => ['Accept: application/json', 'cache-control: no-cache'],
        ]);
        $body = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        unset($curl);

        return [$body, $status, $error];
    }
}
