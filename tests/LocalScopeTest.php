<?php
/**
 * Copyright 2019 Tais P. Hansen
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LaravelOpenTracing\Tests;

use LaravelOpenTracing\LocalScope;
use LaravelOpenTracing\LocalScopeManager;
use LaravelOpenTracing\LocalSpan;
use LaravelOpenTracing\LocalSpanContext;

class LocalScopeTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateScopeSuccess()
    {
        $manager = new LocalScopeManager();
        $span = new LocalSpan('test', LocalSpanContext::createAsRoot());
        $scope = new LocalScope($manager, $span, true);
        $this->assertEquals($span, $scope->getSpan());
    }
}
