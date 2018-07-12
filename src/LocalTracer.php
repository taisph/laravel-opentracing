<?php
/**
 * Copyright 2018 Tais P. Hansen
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LaravelOpenTracing;

use OpenTracing\SpanContext;
use OpenTracing\StartSpanOptions;
use OpenTracing\Tracer;

final class LocalTracer implements Tracer
{
    /**
     * @var LocalScopeManager
     */
    private $scopeManager;

    /**
     * @var LocalSpan[]
     */
    private $spans;

    public function __construct()
    {
        $this->scopeManager = new LocalScopeManager();
    }

    public function getActiveSpan()
    {
        $activeScope = $this->scopeManager->getActive();
        return $activeScope ? $activeScope->getSpan() : null;
    }

    public function getScopeManager()
    {
        return $this->scopeManager;
    }

    public function startSpan($operationName, $options = [])
    {
        $options = $this->asStartSpanOptions($options);

        $references = $options->getReferences();
        $spanContext = empty($references)
            ? LocalSpanContext::createAsRoot()
            : LocalSpanContext::createAsChildOf($references[0]->getContext());

        $span = new LocalSpan($operationName, $spanContext);
        $this->spans[] = $span;
        return $span;
    }

    public function startActiveSpan($operationName, $options = [])
    {
        $options = $this->asStartSpanOptions($options);
        if (($activeSpan = $this->getActiveSpan()) !== null) {
            $options = $options->withParent($activeSpan);
        }

        $span = $this->startSpan($operationName, $options);
        return $this->scopeManager->activate($span, $options->shouldFinishSpanOnClose());
    }

    public function inject(SpanContext $spanContext, $format, &$carrier)
    {
        // TODO
    }

    public function extract($format, $carrier)
    {
        // TODO
        return LocalSpanContext::create();
    }

    public function flush()
    {
        $this->spans = [];
    }

    /**
     * Converts array to options class if necessary.
     *
     * @param array|StartSpanOptions $options
     * @return StartSpanOptions
     */
    private function asStartSpanOptions($options)
    {
        return $options instanceof StartSpanOptions ? $options : StartSpanOptions::create($options);
    }
}
