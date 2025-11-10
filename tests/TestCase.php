<?php

namespace TheJano\MultiLang\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as BaseTestCase;
use TheJano\MultiLang\MultiLangServiceProvider;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app)
    {
        return [
            MultiLangServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app)
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('app.locale', 'en');
        $app['config']->set('app.fallback_locale', 'en');
        $app['config']->set('app.supported_locales', ['en', 'ckb', 'ar']);
        $app['config']->set('multi-lang.translations_table', 'translations');
    }

    protected function defineDatabaseMigrations()
    {
        // Load package migrations (translations table)
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Load test migrations (test_posts table) - never published
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
    }
}
