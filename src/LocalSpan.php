<?php
/**
 * Copyright 2018 Tais P. Hansen
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LaravelOpenTracing;

use Illuminate\Support\Facades\Log;
use OpenTracing\Span;

final class LocalSpan implements Span
{
    /**
     * @var string
     */
    private $operationName;

    /**
     * @var float Unix timestamp
     */
    private $startTime;

    /**
     * @var float Unix timestamp
     */
    private $finishTime;

    /**
     * @var LocalSpanContext
     */
    private $context;

    /**
     * @var (bool|float|int|string)[]
     */
    private $tags = [];

    /**
     * @var array
     */
    private $logs = [];

    /**
     * @param string $operationName
     * @param LocalSpanContext $context
     * @param int|float|\DateTimeInterface|null $startTime
     */
    public function __construct($operationName, LocalSpanContext $context, $startTime = null)
    {
        $this->startTime = $this->getUnixTimestamp($startTime);
        $this->operationName = $operationName;
        $this->context = $context;
    }

    public function getOperationName()
    {
        return $this->operationName;
    }

    public function getContext()
    {
        return $this->context;
    }

    public function finish($finishTime = null)
    {
        $this->warnIfFinished();

        $this->finishTime = $this->getUnixTimestamp($finishTime);
    }

    public function overwriteOperationName($newOperationName)
    {
        $this->warnIfFinished();

        $this->operationName = (string)$newOperationName;
    }

    public function setTag($key, $value)
    {
        $this->warnIfFinished();

        $this->tags[$key] = $value;
    }

    public function log(array $fields = [], $timestamp = null)
    {
        $this->warnIfFinished();

        $this->logs[] = [
            'timestamp' => $this->getUnixTimestamp($timestamp),
            'fields' => $fields,
        ];
    }

    public function addBaggageItem($key, $value)
    {
        $this->warnIfFinished();

        $this->context = $this->context->withBaggageItem($key, $value);
    }

    public function getBaggageItem($key)
    {
        return $this->context->getBaggageItem($key);
    }

    private function warnIfFinished()
    {
        if ($this->finishTime) {
            Log::warning('Span already finished');
        }
    }

    private function getUnixTimestamp($timestamp = null)
    {
        if ($timestamp instanceof \DateTimeInterface) {
            return (float)$timestamp->format('U.u');
        }
        return is_numeric($timestamp) ? (float)$timestamp : microtime(true);
    }
}
