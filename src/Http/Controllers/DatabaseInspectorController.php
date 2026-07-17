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
        $result = $preflightError === null
            ? $analyzer->inspect()
            : null;

        return View::make('laravel_db_inspector::database_inspector_issue_list', [
            'result' => $result,
            'preflightError' => $preflightError,
        ]);
    }
}
