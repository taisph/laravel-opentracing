<?php

namespace LaravelOpenTracing;

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
     * @param \Closure $callable
     * @param $operationName
     * @param array|StartSpanOptions $options
     * @return mixed
     * @throws \Exception
     */
    public function trace(\Closure $callable, $operationName, $options = [])
    {
        $scope = $this->beginTrace($operationName, $options);
        try {
            return $callable();
        } catch (\Exception $e) {
            throw $e;
        } finally {
            $this->endTrace($scope);
        }
    }

    /**
     * @param $operationName
     * @param array|StartSpanOptions $options
     * @return \OpenTracing\Scope
     */
    public function beginTrace($operationName, $options = [])
    {
        $scope = $this->tracer->startActiveSpan($operationName, $options);
        $this->scopes[] = $scope;
        return $scope;
    }

    /**
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
}
