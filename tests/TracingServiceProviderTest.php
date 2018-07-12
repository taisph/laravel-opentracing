<?php
/**
 * Copyright 2019 Tais P. Hansen
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LaravelOpenTracing\Tests;

use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Contracts\Foundation\Application;
use LaravelOpenTracing\TracingService;
use LaravelOpenTracing\TracingServiceProvider;
use Mockery;
use OpenTracing\NoopTracer;
use OpenTracing\Span;
use OpenTracing\Tracer;

class TracingServiceProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Application|Mockery\Mock
     */
    private $app;

    public function setUp()
    {
        parent::setUp();

        $this->app = Container::setInstance(Mockery::mock(Application::class, \ArrayAccess::class)->makePartial());
    }

    public function tearDown()
    {
        Mockery::close();

        parent::tearDown();
    }

    public function testBoot()
    {
        $this->app->shouldReceive('basePath')->with()->once();

        $provider = new TracingServiceProvider($this->app);
        $provider->boot();
    }

    public function testRegister()
    {
        $this->app->shouldReceive('singleton')->with(Tracer::class, Mockery::on(function ($closure) {
            $this->app->shouldReceive('offsetGet')->with('config')->atLeast()->times(3)
                ->andReturn(new Repository(['tracing' => ['autostart' => true]]));
            $this->app->shouldReceive('instance')->with(Span::class, Mockery::type(Span::class))->once();

            return $closure($this->app) instanceof Tracer;
        }))->once();

        $this->app->shouldReceive('singleton')->with(
            TracingService::class,
            Mockery::on(
                function ($closure) {
                    $this->app->shouldReceive('make')->with(Tracer::class)->once()
                        ->andReturn(new NoopTracer());

                    return $closure($this->app) instanceof TracingService;
                }
            )
        )->once();

        $this->app->shouldReceive('terminating')->with(Mockery::on(function ($closure) {
            $span = Mockery::mock(Span::class);
            $span->shouldReceive('finish')->once();

            $this->app->shouldReceive('make')->with(Span::class)->once()->andReturn($span);

            $tracer = Mockery::mock(Tracer::class);
            $tracer->shouldReceive('flush')->once();
            $this->app->shouldReceive('make')->with(Tracer::class)->once()->andReturn($tracer);

            $closure($this->app);

            return true;
        }))->once();

        $this->app->shouldReceive('make')->with(TracingService::class)->once()
            ->andReturn(new TracingService(new NoopTracer()));

        $provider = new TracingServiceProvider($this->app);
        $provider->register();

        $this->assertInstanceOf(TracingService::class, $this->app->make(TracingService::class));
    }
}
