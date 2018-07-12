<?php
/**
 * Copyright 2018 Tais P. Hansen
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LaravelOpenTracing;

use OpenTracing\StartSpanOptions;
use OpenTracing\Tracer;

/**
 * Class for wrapping Laravel job processing in a trace span.
 *
 * This enable automatic tracing of all processed jobs when using queue workers.
 *
 * @see https://laravel.com/docs/queues
 */
class TracingJobPipe
{
    /**
     * @var TracingService
     */
    private $service;

    /**
     * @var Tracer
     */
    private $tracer;

    /**
     * @var array
     */
    private $options;

    /**
     * @param TracingService $service
     * @param Tracer $tracer
     * @param array|StartSpanOptions $options
     */
    public function __construct(TracingService $service, Tracer $tracer, $options = [])
    {
        $this->service = $service;
        $this->tracer = $tracer;
        $this->options = $options;
    }

    /**
     * @param object $job
     * @param \Closure $next
     * @return mixed
     * @throws \Exception
     */
    public function handle($job, \Closure $next)
    {
        $res = $this->service->trace(
            function () use ($next, $job) {
                return $next($job);
            },
            get_class($job),
            $this->options
        );
        $this->tracer->flush();
        return $res;
    }
}
