<?php
/**
 * Copyright 2019 Tais P. Hansen
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LaravelOpenTracing\Log\Processor;

use Illuminate\Contracts\Container\BindingResolutionException;
use OpenTracing\Span;
use OpenTracing\Tracer;

/**
 * Monolog log processor for the local tracer for use within a Laravel
 * application.
 *
 * Adds current tracing span information as context to your application log
 * messages, such as the log entry below:
 *
 * ```
 * [2018-07-06 16:57:55] dev.INFO: Doing important work {"queue":"test"} {"trace_id":"1e55d771a3b186004d66b53c0d825b61","span_id":"f3ff957173dbf652","span_name":"app"}
 * ```
 *
 * Add the following to your `bootstrap/app.php` to use it:
 * ```php
 * $app->configureMonologUsing(function (Logger $logger) {
 *     $logger->pushProcessor(new \LaravelOpenTracing\Log\Processor\LocalTracerProcessor());
 * });
 * ```
 */
class LocalTracerProcessor
{
    public function __invoke(array $record)
    {
        /** @var \LaravelOpenTracing\LocalSpan $span */
        $span = null;
        if ($tracer = app(Tracer::class)) {
            /** @var Tracer $tracer */
            try {
                $span = $tracer->getActiveSpan() ?: app(Span::class);
            } catch (BindingResolutionException $e) {
                // Ignore.
            }
        }

        if ($span) {
            $context = $span->getContext();
            $record['extra']['trace_id'] = $context->getTraceId();
            $record['extra']['span_id'] = $context->getSpanId();
            $record['extra']['span_name'] = $span->getOperationName();
        }

        return $record;
    }
}
