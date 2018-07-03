<?php

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

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        if ($this->finishSpanOnClose) {
            $this->span->finish();
        }

        $this->scopeManager->deactivate($this);
    }

    /**
     * {@inheritdoc}
     */
    public function getSpan()
    {
        return $this->span;
    }
}
