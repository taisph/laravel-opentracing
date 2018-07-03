<?php

namespace LaravelOpenTracing;

use OpenTracing\ScopeManager;
use OpenTracing\Span;

final class LocalScopeManager implements ScopeManager
{
    /**
     * @var LocalScope[]
     */
    private $scopes = [];

    /**
     * {@inheritdoc}
     */
    public function activate(Span $span, $finishSpanOnClose)
    {
        $scope = new LocalScope($this, $span, $finishSpanOnClose);
        $this->scopes[] = $scope;
        return $scope;
    }

    /**
     * {@inheritdoc}
     */
    public function getActive()
    {
        return end($this->scopes);
    }

    /**
     * {@inheritdoc}
     */
    public function deactivate(LocalScope $scope)
    {
        $keys = array_keys($this->scopes, $scope, true);
        foreach ($keys as $key) {
            unset($this->scopes[$key]);
        }
    }
}
