<?php

namespace jeremykenedy\LaravelPackagist\Console;

class InstallCommand extends UpdateCommand
{
    protected $signature = 'packagist:install {--force : Skip the existing installation confirmation; preserve existing files}';

    protected $description = 'Install Packagist configuration and translations';

    public function handle()
    {
        if (is_file($this->laravel->configPath('laravelpackagist.php')) && ! $this->option('force')) {
            $this->info('Laravel Packagist is already installed. Existing files will be preserved.');

            if (! $this->confirm('Publish any missing files?', false)) {
                return 0;
            }
        }

        return $this->publishFiles();
    }
}
