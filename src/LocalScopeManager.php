<?php
/**
 * Copyright 2018 Tais P. Hansen
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LaravelOpenTracing;

use OpenTracing\ScopeManager;
use OpenTracing\Span;

final class LocalScopeManager implements ScopeManager
{
    /**
     * @var LocalScope[]
     */
    private $scopes = [];

    public function activate(Span $span, $finishSpanOnClose)
    {
        $scope = new LocalScope($this, $span, $finishSpanOnClose);
        $this->scopes[] = $scope;
        return $scope;
    }

    public function getActive()
    {
        return end($this->scopes);
    }

    /**
     * Deactivate a span.
     *
     * @param LocalScope $scope
     */
    public function deactivate(LocalScope $scope)
    {
        $keys = array_keys($this->scopes, $scope, true);
        foreach ($keys as $key) {
            unset($this->scopes[$key]);
        }
    }
}
