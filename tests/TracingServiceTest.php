<?php
/**
 * Copyright 2019 Tais P. Hansen
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LaravelOpenTracing\Tests;

use LaravelOpenTracing\TracingService;
use Mockery;
use OpenTracing\Scope;
use OpenTracing\Tracer;

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
}
