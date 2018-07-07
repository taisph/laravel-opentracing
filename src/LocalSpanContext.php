<?php
/**
 * Copyright 2018 Tais P. Hansen
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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

    /**
     * Create a root span context.
     *
     * @param bool $sampled
     * @param array $items
     * @return LocalSpanContext
     */
    public static function createAsRoot($sampled = true, array $items = [])
    {
        return new self(self::generateId(16), self::generateId(8), $sampled, $items);
    }

    /**
     * Create a span context as a child of an existing span context.
     *
     * @param LocalSpanContext $spanContext
     * @return LocalSpanContext
     */
    public static function createAsChildOf(LocalSpanContext $spanContext)
    {
        return new self($spanContext->traceId, self::generateId(8), $spanContext->isSampled, $spanContext->items);
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->items);
    }

    public function getBaggageItem($key)
    {
        return array_key_exists($key, $this->items) ? $this->items[$key] : null;
    }

    public function withBaggageItem($key, $value)
    {
        return new self($this->traceId, $this->spanId, $this->isSampled, array_merge($this->items, [$key => $value]));
    }

    /**
     * Get trace id.
     *
     * @return string
     */
    public function getTraceId()
    {
        return bin2hex($this->traceId);
    }

    /**
     * Get span id.
     *
     * @return string
     */
    public function getSpanId()
    {
        return bin2hex($this->spanId);
    }

    /**
     * Generate random identifier of specified length.
     *
     * @param int $length Number of bytes to generate
     * @return int
     */
    private static function generateId($length)
    {
        return random_bytes($length);
    }
}
