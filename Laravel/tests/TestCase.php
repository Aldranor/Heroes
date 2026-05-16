<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Fortify\Features;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $this->forceInMemoryTestingDatabaseEnvironment();

        $app = parent::createApplication();

        $this->preventPersistentTestingDatabase($app);

        return $app;
    }

    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }

    private function forceInMemoryTestingDatabaseEnvironment(): void
    {
        foreach ([
            'APP_ENV' => 'testing',
            'DB_CONNECTION' => 'sqlite',
            'DB_DATABASE' => ':memory:',
            'DB_URL' => '',
        ] as $key => $value) {
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }

    private function preventPersistentTestingDatabase(Application $app): void
    {
        $defaultConnection = $app['config']->get('database.default');
        $database = $app['config']->get("database.connections.{$defaultConnection}.database");

        if ($app->environment() !== 'testing' || $defaultConnection !== 'sqlite' || $database !== ':memory:') {
            throw new RuntimeException(sprintf(
                'Refusing to run tests against [%s] database [%s]. Tests must use sqlite :memory:.',
                $defaultConnection,
                is_scalar($database) ? (string) $database : gettype($database),
            ));
        }
    }
}
