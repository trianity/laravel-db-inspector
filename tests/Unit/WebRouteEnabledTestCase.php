<?php

declare(strict_types=1);

namespace Trianity\LaravelDbInspector\Tests\Unit;

use Trianity\LaravelDbInspector\Tests\TestCase;

abstract class WebRouteEnabledTestCase extends TestCase
{
    protected function defineEnvironment($app): void
    {
        $app['config']->set('laravel-db-inspector.enabled', true);
        $app['config']->set('laravel-db-inspector.web.enabled', true);
    }
}
