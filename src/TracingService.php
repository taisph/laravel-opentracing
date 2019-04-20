<?php
/**
 * Copyright 2019 Tais P. Hansen
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LaravelOpenTracing;

use Illuminate\Http\Request;
use OpenTracing\Scope;
use OpenTracing\StartSpanOptions;
use OpenTracing\Tracer;

class TracingService
{
    /**
     * @var Tracer
     */
    private $tracer;

    /**
     * @var Scope[]
     */
    private $scopes = [];

    public function __construct(Tracer $tracer)
    {
        $this->tracer = $tracer;
    }

    /**
     * Wraps a call in a trace span.
     *
     * @param \Closure $callable
     * @param $operationName
     * @param array|StartSpanOptions $options
     * @return mixed
     * @throws \Exception
     */
    public function trace($operationName, \Closure $callable, $options = null)
    {
        $scope = $this->beginTrace($operationName, $options);
        try {
            return $callable();
        } finally {
            $this->endTrace($scope);
        }
    }

    /**
     * Starts a new trace span.
     *
     * @param $operationName
     * @param array|StartSpanOptions $options
     * @return \OpenTracing\Scope
     */
    public function beginTrace($operationName, $options = null)
    {
        $scope = $this->tracer->startActiveSpan($operationName, $options ?: []);
        $this->scopes[] = $scope;
        return $scope;
    }

    /**
     * Ends the specified or last started trace span.
     *
     * @param Scope|null $scope
     */
    public function endTrace($scope = null)
    {
        if ($scope === null) {
            $scope = end($this->scopes);
        }
        $scope->close();

        $keys = array_keys($this->scopes, $scope, true);
        foreach ($keys as $key) {
            unset($this->scopes[$key]);
        }
    }

    /**
     * Injects active span context into carrier.
     *
     * @return array
     */
    public function getInjectHeaders()
    {
        $carrier = [];
        if (($span = $this->tracer->getActiveSpan()) !== null) {
            $this->tracer->inject(
                $span->getContext(),
                \OpenTracing\Formats\HTTP_HEADERS,
                $carrier
            );
        }
        return $carrier;
    }

    /**
     * Extract span context from request.
     *
     * @param Request $request
     * @return \OpenTracing\SpanContext|null
     */
    public function extractFromHttpRequest(Request $request)
    {
        return $this->tracer->extract(
            \OpenTracing\Formats\HTTP_HEADERS,
            array_map(
                static function ($v) {
                    if (is_array($v) && count($v) === 1) {
                        return $v[0];
                    }
                    return $v;
                },
                $request->header()
            )
        );
    }
}
