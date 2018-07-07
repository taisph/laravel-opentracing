<?php
/**
 * Copyright 2018 Tais P. Hansen
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LaravelOpenTracing;

use OpenTracing\Scope;
use OpenTracing\Span;

final class LocalScope implements Scope
{
    /** @var LocalScopeManager */
    private $scopeManager;

    /** @var LocalSpan */
    private $span;

    /** @var bool */
    private $finishSpanOnClose;

    public function __construct(LocalScopeManager $scopeManager, Span $span, $finishSpanOnClose)
    {
        $this->scopeManager = $scopeManager;
        $this->span = $span;
        $this->finishSpanOnClose = $finishSpanOnClose;
    }

    public function close()
    {
        if ($this->finishSpanOnClose) {
            $this->span->finish();
        }

        $this->scopeManager->deactivate($this);
    }

    public function getSpan()
    {
        return $this->span;
    }
}
