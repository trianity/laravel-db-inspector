<?php

declare(strict_types=1);

namespace Trianity\LaravelDbInspector\Http\Controllers;

use Illuminate\Routing\Controller;
use Trianity\LaravelDbInspector\DatabaseInspector;

class DatabaseInspectorController extends Controller
{
    public function index()
    {
        $analyzer = new DatabaseInspector;
        $preflightError = $analyzer->getPreflightError();

        $groupedIssues = $preflightError === null
            ? $analyzer->analyze()
            : [
                'structure' => [],
                'integrity' => [],
                'performance' => [],
                'architecture' => [],
            ];

        return view('laravel-db-inspector::database-inspector-issue-list', compact('groupedIssues', 'preflightError'));
    }
}
