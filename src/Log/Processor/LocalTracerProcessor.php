<?php

namespace LaravelOpenTracing\Log\Processor;

use OpenTracing\Span;
use OpenTracing\Tracer;

class LocalTracerProcessor
{
    public function __invoke(array $record)
    {
        /** @var \LaravelOpenTracing\LocalSpan $span */
        $span = null;
        if ($tracer = app(Tracer::class)) {
            $span = $tracer->getActiveSpan() ?: app(Span::class);
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
