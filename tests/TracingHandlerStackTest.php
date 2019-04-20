<?php
/**
 * Copyright 2019 Tais P. Hansen
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LaravelOpenTracing\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Container\Container;
use Illuminate\Contracts\Foundation\Application;
use LaravelOpenTracing\TracingHandlerStack;
use LaravelOpenTracing\TracingService;
use Mockery;

class TracingHandlerStackTest extends \PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        Mockery::close();

        parent::tearDown();
    }

    public function testTraceContextHeadersAreSentInRequests()
    {
        /** @var Application|Mockery\Mock $app */
        $app = Container::setInstance(Mockery::mock(Application::class, \ArrayAccess::class)->makePartial());

        $service = Mockery::mock(TracingService::class)->makePartial();
        $service->shouldReceive('getInjectHeaders')->once()
            ->andReturn(['test-trace-id' => 'deadbeefdeadbeef:beefdeadbeefdead:0:1']);

        $app->shouldReceive('make')->with(TracingService::class)->once()->andReturn($service);

        $container = [];
        $history = Middleware::history($container);

        $handler = new TracingHandlerStack(new MockHandler([
            new Response(200),
        ]));
        $handler->push($history);

        $client = new Client(['handler' => $handler]);

        $client->request('GET', 'http://localhost');

        $this->assertEquals(
            ['deadbeefdeadbeef:beefdeadbeefdead:0:1'],
            $container[0]['request']->getHeader('test-trace-id')
        );
    }
}
