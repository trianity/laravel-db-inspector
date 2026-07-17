<?php

namespace Trianity\LaravelDbInspector\Checks;

use Trianity\LaravelDbInspector\Contracts\CheckInterface;

abstract class BaseCheck implements CheckInterface
{
    protected array $config;

    public function __construct()
    {
        $this->config = (array) config('laravel-db-inspector', []);
    }
}
