<?php

namespace jeremykenedy\LaravelPackagist\Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Filesystem\Filesystem;
use jeremykenedy\LaravelPackagist\LaravelPackagistServiceProvider;

class CommandTest extends TestCase
{
    private function withInstallation($test)
    {
        $files = new Filesystem;
        $directory = sys_get_temp_dir().'/laravel-packagist-'.bin2hex(random_bytes(8));
        $originalBase = $this->app->basePath();
        $originalLang = $this->app->langPath();
        $this->app->setBasePath($directory);

        if (method_exists($this->app, 'useLangPath')) {
            $this->app->useLangPath($directory.'/lang');
        }

        (new LaravelPackagistServiceProvider($this->app))->boot();

        try {
            $test($files, $this->app['Illuminate\Contracts\Console\Kernel']);
        } finally {
            $files->deleteDirectory($directory);
            $this->app->setBasePath($originalBase);

            if (method_exists($this->app, 'useLangPath')) {
                $this->app->useLangPath($originalLang);
            }
        }
    }

    public function test_install_publishes_configuration_and_translations()
    {
        $this->withInstallation(function ($files, Kernel $kernel) {
            $this->assertSame(0, $kernel->call('packagist:install', ['--no-interaction' => true]));
            $this->assertSame(file_get_contents(__DIR__.'/../src/config/laravelpackagist.php'), $files->get($this->app->configPath('laravelpackagist.php')));
            $this->assertSame(file_get_contents(__DIR__.'/../src/resources/lang/en/laravelpackagist.php'), $files->get($this->app->langPath().'/vendor/laravelpackagist/en/laravelpackagist.php'));
        });
    }

    public function test_install_detects_existing_configuration_without_changing_it()
    {
        $this->withInstallation(function ($files, Kernel $kernel) {
            $files->makeDirectory($this->app->configPath(), 0755, true);
            $path = $this->app->configPath('laravelpackagist.php');
            $files->put($path, '<?php return ["vendor" => ["default" => "custom"]];');
            $before = $files->get($path);

            $this->assertSame(0, $kernel->call('packagist:install', ['--no-interaction' => true]));
            $this->assertTrue(strpos($kernel->output(), 'already installed') !== false);
            $this->assertSame($before, $files->get($path));
            $this->assertFalse($files->exists($this->app->langPath().'/vendor/laravelpackagist'));
        });
    }

    public function test_update_and_forced_install_preserve_custom_files_and_publish_missing_ones()
    {
        $this->withInstallation(function ($files, Kernel $kernel) {
            $kernel->call('packagist:install', ['--no-interaction' => true]);
            $config = $this->app->configPath('laravelpackagist.php');
            $translation = $this->app->langPath().'/vendor/laravelpackagist/en/laravelpackagist.php';
            $customConfig = '<?php return ["vendor" => ["default" => "custom"]];';
            $customTranslation = '<?php return ["package-not-found" => "Custom message."];';
            $files->put($config, $customConfig);
            $files->put($translation, $customTranslation);

            foreach (['packagist:update' => [], 'packagist:install' => ['--force' => true]] as $command => $options) {
                $this->assertSame(0, $kernel->call($command, $options + ['--no-interaction' => true]));
                $this->assertSame($customConfig, $files->get($config));
                $this->assertSame($customTranslation, $files->get($translation));
            }

            $files->delete($translation);
            $this->assertSame(0, $kernel->call('packagist:update', ['--no-interaction' => true]));
            $this->assertTrue($files->exists($translation));
            $this->assertSame($customConfig, $files->get($config));
        });
    }

    public function test_legacy_publish_tags_remain_available()
    {
        $this->withInstallation(function ($files, Kernel $kernel) {
            foreach (['laravelpackagist-config', 'laravelpackagist-lang'] as $tag) {
                $this->assertSame(0, $kernel->call('vendor:publish', ['--tag' => $tag, '--no-interaction' => true]));
            }

            $this->assertTrue($files->exists($this->app->configPath('laravelpackagist.php')));
            $this->assertTrue($files->exists($this->app->langPath().'/vendor/laravelpackagist/en/laravelpackagist.php'));
        });
    }

    public function test_provider_registration_does_not_publish_files()
    {
        $this->withInstallation(function ($files) {
            $provider = new LaravelPackagistServiceProvider($this->app);
            $provider->register();
            $provider->boot();

            $this->assertFalse($files->exists($this->app->configPath('laravelpackagist.php')));
            $this->assertFalse($files->exists($this->app->langPath().'/vendor/laravelpackagist'));
            $this->assertSame(['laravelpackagist'], $provider->provides());
        });
    }
}
