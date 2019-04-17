<?php
/**
 * Copyright 2019 Tais P. Hansen
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LaravelOpenTracing\Clients;

abstract class Client implements ClientInterface
{
    /**
     * Client configuration options.
     *
     * @var array
     */
    protected $config;

    public function __construct(array $config = null)
    {
        $this->config = $config ?: [];
    }
}
