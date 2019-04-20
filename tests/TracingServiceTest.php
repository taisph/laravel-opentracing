<?php
/**
 * Copyright 2019 Tais P. Hansen
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LaravelOpenTracing\Tests;

use Illuminate\Http\Request;
use LaravelOpenTracing\TracingService;
use Mockery;
use OpenTracing\Scope;
use OpenTracing\Span;
use OpenTracing\SpanContext;
use OpenTracing\Tracer;
use const OpenTracing\Formats\HTTP_HEADERS;

class TracingServiceTest extends \PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        Mockery::close();

        parent::tearDown();
    }

    public function testTrace()
    {
        $scope = Mockery::mock(Scope::class);
        $scope->shouldReceive('close')->once();

        $tracer = Mockery::mock(Tracer::class);
        $tracer->shouldReceive('startActiveSpan')->once()->with('test.trace', [])->andReturn($scope);

        $this->assertEquals(
            42,
            (new TracingService($tracer))->trace(
                'test.trace',
                static function () {
                    return 42;
                }
            )
        );
    }

    public function testEndTraceOnUnspecifiedScope()
    {
        $scope = Mockery::mock(Scope::class);
        $scope->shouldReceive('close')->once();

        $tracer = Mockery::mock(Tracer::class);
        $tracer->shouldReceive('startActiveSpan')->once()->with('test.trace', [])->andReturn($scope);

        $service = new TracingService($tracer);
        $service->beginTrace('test.trace');
        $service->endTrace();
    }

    public function testGettingHeadersForInjection()
    {
        $spanContext = Mockery::mock(SpanContext::class);

        $span = Mockery::mock(Span::class);
        $span->shouldReceive('getContext')->withNoArgs()->once()->andReturn($spanContext);

        $tracer = Mockery::mock(Tracer::class);
        $tracer->shouldReceive('getActiveSpan')->withNoArgs()->once()->andReturn($span);
        $tracer->shouldReceive('inject')
            ->with($spanContext, HTTP_HEADERS, Mockery::on(function (&$array) {
                $array['test-trace-id'] = 'beefdeadbeefdead:deadbeefdeadbeef:0:1';
                return true;
            }))->once();

        $service = new TracingService($tracer);

        $this->assertEquals(['test-trace-id' => 'beefdeadbeefdead:deadbeefdeadbeef:0:1'], $service->getInjectHeaders());
    }

    public function testExtractingHeadersFromRequest()
    {
        $spanContext = Mockery::mock(SpanContext::class);

        $tracer = Mockery::mock(Tracer::class);
        $tracer->shouldReceive('extract')
            ->with(HTTP_HEADERS, ['test-trace-id' => 'deadbeefdeadbeef:beefdeadbeefdead:0:1'])
            ->once()->andReturn($spanContext);

        $service = new TracingService($tracer);

        $request = new Request([], [], [], [], [], ['HTTP_TEST_TRACE_ID' => 'deadbeefdeadbeef:beefdeadbeefdead:0:1']);

        $this->assertEquals($spanContext, $service->extractFromHttpRequest($request));
    }
}
