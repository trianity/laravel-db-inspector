<?php

declare(strict_types=1);

namespace Trianity\LaravelDbInspector\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\View;
use Trianity\LaravelDbInspector\DatabaseInspector;

class DatabaseInspectorController extends Controller
{
    public function index()
    {
        $analyzer = app(DatabaseInspector::class);
        $preflightError = $analyzer->getPreflightError();

        $groupedIssues = $preflightError === null
            ? $analyzer->inspect()->groupedIssues()
            : [
                'structure' => [],
                'integrity' => [],
                'performance' => [],
                'architecture' => [],
            ];

        return View::make('laravel_db_inspector::database_inspector_issue_list', compact('groupedIssues', 'preflightError'));
    }
}
