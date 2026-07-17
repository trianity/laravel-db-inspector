<?php

declare(strict_types=1);

namespace Trianity\LaravelDbInspector\Tests;

use Illuminate\Foundation\Application;
use Orchestra\Testbench\TestCase as Orchestra;
use Trianity\LaravelDbInspector\LaravelDbInspectorServiceProvider;

abstract class TestCase extends Orchestra
{
    /**
     * @param  Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            LaravelDbInspectorServiceProvider::class,
        ];
    }
}
