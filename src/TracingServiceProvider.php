<?php

namespace LaravelOpenTracing;

use Illuminate\Support\ServiceProvider;
use OpenTracing\Span;
use OpenTracing\Tracer;

class TracingServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        // Register the tracer implementation.
        // TODO: Make the tracer configurable with fallback on the NoopTracer.
        $tracer = new LocalTracer();
        \OpenTracing\GlobalTracer::set($tracer);
        $this->app->instance(Tracer::class, $tracer);

        // Start root span.
        $span = $tracer->startSpan('app');
        $this->app->instance(Span::class, $span);

        $this->app->terminating(
            function () {
                $this->app->make(Span::class)->finish();
                $this->app->make(Tracer::class)->flush();
            }
        );

        $this->app->instance(TracingService::class, new TracingService($this->app->make(Tracer::class)));
    }
}
