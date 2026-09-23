<?php

namespace jeremykenedy\LaravelPackagist\Console;

use Illuminate\Console\Command;

class UpdateCommand extends Command
{
    protected $signature = 'packagist:update';

    protected $description = 'Publish missing Packagist configuration and translations without overwriting existing files';

    public function handle()
    {
        return $this->publishFiles();
    }

    protected function publishFiles()
    {
        foreach (['laravelpackagist-config', 'laravelpackagist-lang'] as $tag) {
            $status = $this->call('vendor:publish', ['--tag' => $tag]);

            if ($status !== 0) {
                return $status;
            }
        }

        $this->info('Packagist files are ready. Existing configuration and translations were preserved.');

        return 0;
    }
}
