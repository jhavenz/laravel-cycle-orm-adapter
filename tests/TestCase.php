<?php

declare(strict_types=1);

namespace WayOfDev\Cycle\Tests;

use Faker\Factory as FakerFactory;
use Faker\Generator;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Env;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\LaravelRay\RayServiceProvider;
use Spatie\Ray\Settings\Settings as RaySettings;
use Spatie\Ray\Settings\SettingsFactory;
use WayOfDev\Cycle\Bridge\Laravel\CycleServiceProvider;

use function array_key_exists;

abstract class TestCase extends Orchestra
{
    final protected static function faker(string $locale = 'en_US'): Generator
    {
        /** @var array<string, Generator> $fakers */
        static $fakers = [];

        if (! array_key_exists($locale, $fakers)) {
            $faker = FakerFactory::create($locale);

            $fakers[$locale] = $faker;
        }

        return $fakers[$locale];
    }

    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            static fn (string $modelName) => 'WayOfDev\\Laravel\\Cycle\\Database\\Factories\\' . class_basename($modelName) . 'Factory'
        );
    }

    public function getEnvironmentSetUp($app): void
    {
        config()->set('database.default', 'testing');

        $app->instance(
            RaySettings::class,
            SettingsFactory::createFromArray([
                'enable' => Env::get('RAY_ENABLED', true),
                'send_cache_to_ray' => Env::get('SEND_CACHE_TO_RAY', false),
                'send_dumps_to_ray' => Env::get('SEND_DUMPS_TO_RAY', true),
                'send_jobs_to_ray' => Env::get('SEND_JOBS_TO_RAY', false),
                'send_log_calls_to_ray' => Env::get('SEND_LOG_CALLS_TO_RAY', true),
                'send_queries_to_ray' => Env::get('SEND_QUERIES_TO_RAY', false),
                'send_duplicate_queries_to_ray' => Env::get('SEND_DUPLICATE_QUERIES_TO_RAY', false),
                'send_slow_queries_to_ray' => Env::get('SEND_SLOW_QUERIES_TO_RAY', false),
                'slow_query_threshold_in_ms' => Env::get('RAY_SLOW_QUERY_THRESHOLD_IN_MS', 500),
                'send_requests_to_ray' => Env::get('SEND_REQUESTS_TO_RAY', false),
                'send_http_client_requests_to_ray' => Env::get('SEND_HTTP_CLIENT_REQUESTS_TO_RAY', false),
                'send_views_to_ray' => Env::get('SEND_VIEWS_TO_RAY', false),
                'send_exceptions_to_ray' => Env::get('SEND_EXCEPTIONS_TO_RAY', true),
                'send_deprecated_notices_to_ray' => Env::get('SEND_DEPRECATED_NOTICES_TO_RAY', false),
                'host' => Env::get('RAY_HOST', 'localhost'),
                'port' => Env::get('RAY_PORT', 23517),
                'remote_path' => Env::get('RAY_REMOTE_PATH', null),
                'local_path' => Env::get('RAY_LOCAL_PATH', null),
                'always_send_raw_values' => false,
            ])
        );
    }

    protected function getPackageProviders($app): array
    {
        return [
            CycleServiceProvider::class,
            RayServiceProvider::class,
        ];
    }
}
