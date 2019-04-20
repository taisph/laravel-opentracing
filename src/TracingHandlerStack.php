<?php
/**
 * Copyright 2019 Tais P. Hansen
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LaravelOpenTracing;

class TracingHandlerStack extends \GuzzleHttp\HandlerStack
{
    public function __construct(callable $handler = null)
    {
        parent::__construct($handler ?: \GuzzleHttp\choose_handler());

        $this->push(
            \GuzzleHttp\Middleware::mapRequest(
                function (\Psr\Http\Message\RequestInterface $request) {
                    foreach (app(TracingService::class)->getInjectHeaders() as $header => $value) {
                        $request = $request->withHeader($header, $value);
                    }
                    return $request;
                }
            )
        );
    }
}
