<?php
/**
 * Copyright 2019 Tais P. Hansen
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LaravelOpenTracing\Clients;

use Illuminate\Support\Arr;
use Jaeger\Reporter\RemoteReporter;
use Jaeger\Sampler\ConstSampler;
use Jaeger\Sampler\ProbabilisticSampler;
use Jaeger\Sampler\RateLimitingSampler;
use Jaeger\Sender\UdpSender;
use Jaeger\Thrift\Agent\AgentClient;
use Jaeger\ThriftUdpTransport;
use Jaeger\Tracer;
use Jaeger\Util\RateLimiter;
use LaravelOpenTracing\Cache\CacheItemPool;
use Thrift\Exception\TTransportException;
use Thrift\Protocol\TCompactProtocol;
use Thrift\Transport\TBufferedTransport;
use const Jaeger\SAMPLER_TYPE_CONST;
use const Jaeger\SAMPLER_TYPE_PROBABILISTIC;
use const Jaeger\SAMPLER_TYPE_RATE_LIMITING;

class JaegerClient extends Client
{
    /**
     * @return \OpenTracing\Tracer
     * @throws \Exception
     */
    public function getTracer()
    {
        $transport = $this->getSender(array_get($this->config, 'agent.host'), array_get($this->config, 'agent.port'));

        return new Tracer(
            array_get($this->config, 'service_name'),
            new RemoteReporter($transport),
            $this->getSampler(),
            true,
            app('log'),
            null,
            'uber-trace-id',
            'uberctx-',
            'jaeger-debug-id'
        );
    }

    private function getSampler()
    {
        $type = Arr::get($this->config, 'sampler.type');
        $param = Arr::get($this->config, 'sampler.param');

        if ($type === null) {
            $type = SAMPLER_TYPE_CONST;
            $param = true;
        }

        if ($type === SAMPLER_TYPE_CONST) {
            return new ConstSampler((int)$param === 1);
        }

        if ($type === SAMPLER_TYPE_PROBABILISTIC) {
            return new ProbabilisticSampler((float)$param);
        }

        if ($type === SAMPLER_TYPE_RATE_LIMITING) {
            return new RateLimitingSampler(
                $param ?: 0,
                new RateLimiter(
                    new CacheItemPool(app('cache')->store()),
                    'opentracing.rate.current_balance',
                    'opentracing.rate.last_tick'
                )
            );
        }

        throw new \RuntimeException('Unknown sampler type: ' . $type);
    }

    private function getSender($hostname, $port = null)
    {
        $udp = new ThriftUdpTransport(
            $hostname ?: 'localhost',
            $port ?: 5775
        );

        $transport = new TBufferedTransport($udp, 8192, 8192);
        try {
            $transport->open();
        } catch (TTransportException $e) {
            // Ignored.
        }

        $protocol = new TCompactProtocol($transport);
        $client = new AgentClient($protocol);

        return new UdpSender($client, 8192);
    }
}
