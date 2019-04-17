<?php
/**
 * Copyright 2019 Tais P. Hansen
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LaravelOpenTracing\Clients;

use LaravelOpenTracing\LocalTracer;

class LocalClient extends Client
{
    public function getTracer()
    {
        return new LocalTracer();
    }
}
