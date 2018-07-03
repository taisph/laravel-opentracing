<?php

namespace LaravelOpenTracing;

use OpenTracing\SpanContext;

final class LocalSpanContext implements SpanContext
{
    /**
     * @var string
     */
    private $traceId;

    /**
     * @var string
     */
    private $spanId;

    /**
     * @var bool
     */
    private $isSampled;

    /**
     * @var array
     */
    private $items;

    public function __construct($traceId, $spanId, $isSampled, array $items)
    {
        $this->traceId = $traceId;
        $this->spanId = $spanId;
        $this->isSampled = $isSampled;
        $this->items = $items;
    }

    public static function createAsRoot($sampled = true, array $items = [])
    {
        return new self(self::generateId(16), self::generateId(8), $sampled, $items);
    }

    public static function createAsChildOf(LocalSpanContext $spanContext)
    {
        return new self($spanContext->traceId, self::generateId(8), $spanContext->isSampled, $spanContext->items);
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->items);
    }

    /**
     * {@inheritdoc}
     */
    public function getBaggageItem($key)
    {
        return array_key_exists($key, $this->items) ? $this->items[$key] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function withBaggageItem($key, $value)
    {
        return new self($this->traceId, $this->spanId, $this->isSampled, array_merge($this->items, [$key => $value]));
    }

    public function getTraceId()
    {
        return bin2hex($this->traceId);
    }

    public function getSpanId()
    {
        return bin2hex($this->spanId);
    }

    /**
     * Generate random identifier of specified length
     *
     * @param int $length Number of bytes to generate
     * @return int
     */
    private static function generateId($length)
    {
        return random_bytes($length);
    }
}
