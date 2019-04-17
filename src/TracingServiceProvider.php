<?php
/**
 * Copyright 2019 Tais P. Hansen
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LaravelOpenTracing;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use LaravelOpenTracing\Clients\ClientInterface;
use LaravelOpenTracing\Clients\JaegerClient;
use LaravelOpenTracing\Clients\LocalClient;
use OpenTracing\GlobalTracer;
use OpenTracing\Span;
use OpenTracing\Tracer;

class TracingServiceProvider extends ServiceProvider
{
    private static $client = [
        'local' => LocalClient::class,
        'jaeger' => JaegerClient::class,
    ];

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(
            Tracer::class,
            function (Application $app) {
                $cfgFile = dirname(__DIR__) . '/config/tracing.php';
                $this->mergeConfigFrom($cfgFile, 'tracing');

                $clientType = $app['config']['tracing.type'] ?: 'local';
                $clientClass = array_get(self::$client, $clientType, self::$client['local']);

                /** @var ClientInterface $client */
                $client = new $clientClass($app['config']['tracing.clients.' . $clientType]);
                $tracer = $client->getTracer();

                GlobalTracer::set($tracer);

                if ($app['config']['tracing.autostart'] === true) {
                    $span = $tracer->startSpan('root');
                    $this->app->instance(Span::class, $span);
                }

                return $tracer;
            }
        );

        $this->app->singleton(
            TracingService::class,
            static function (Application $app) {
                return new TracingService($app->make(Tracer::class));
            }
        );

        $this->app->terminating(
            function () {
                try {
                    $this->app->make(Span::class)->finish();
                } catch (\Exception $e) {
                    // Passthrough.
                }
                $this->app->make(Tracer::class)->flush();
            }
        );
    }

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([dirname(__DIR__) . '/config/tracing.php' => base_path('config/tracing.php')]);
    }
}
