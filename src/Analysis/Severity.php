<?php

declare(strict_types=1);

namespace Trianity\LaravelDbInspector\Analysis;

enum Severity: string
{
    case Info = 'info';
    case Warning = 'warning';
    case Error = 'error';
}
