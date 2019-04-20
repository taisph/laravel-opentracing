<?php
/**
 * Copyright 2019 Tais P. Hansen
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LaravelOpenTracing\Http\Middleware;

use Illuminate\Http\Request;
use LaravelOpenTracing\TracingService;

class Tracing
{
    /**
     * Handle incoming request and start a trace if the request contains a trace context.
     *
     * @param Request $request
     * @param \Closure $next
     * @return mixed
     * @throws \Exception
     */
    public function handle(Request $request, \Closure $next)
    {
        /** @var TracingService $service */
        $service = app(TracingService::class);

        $context = null;
        try {
            $context = $service->extractFromHttpRequest($request);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning(
                'Failed getting trace context from request',
                ['exception' => $e, 'header' => $request->header()]
            );
        }
        if ($context === null) {
            return $next($request);
        }

        return $service->trace(
            'http.' . strtolower($request->getMethod()) . '.' . $request->decodedPath(),
            static function () use ($next, $request) {
                return $next($request);
            },
            ['child_of' => $context]
        );
    }
}
