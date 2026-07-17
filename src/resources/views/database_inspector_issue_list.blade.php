<!DOCTYPE html>
<html>
<head>
    <title>Laravel DB Inspector Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4 bg-light">
<div class="container">
    <h2 class="mb-4 fw-bold">Database Inspector Analysis</h2>

    @if(!empty($preflightError))
        <div class="alert alert-danger shadow-sm">{{ $preflightError }}</div>
    @elseif($result === null)
        <div class="alert alert-warning shadow-sm">No analysis result available.</div>
    @elseif($result->findings === [])
        <div class="alert alert-success shadow-sm">No issues found. Great job!</div>
    @else
        @php
            $grouped = [];
            foreach ($result->findings as $finding) {
                $grouped[$finding->category][$finding->checkName][] = $finding;
            }
        @endphp

        @foreach($grouped as $category => $checks)
            @php
                $categoryCount = 0;
                foreach ($checks as $findings) {
                    $categoryCount += count($findings);
                }
            @endphp

            <div class="card mb-3 shadow-sm">
                <div class="card-header">
                    <strong>{{ ucfirst(str_replace('_', ' ', $category)) }}</strong>
                    <span class="badge bg-danger">{{ $categoryCount }}</span>
                </div>
                <div class="card-body">
                    @foreach($checks as $checkName => $findings)
                        <div class="mb-3">
                            <h5 class="mb-2">{{ $checkName }} ({{ count($findings) }})</h5>

                            @foreach($findings as $finding)
                                <div class="border rounded p-3 mb-2 bg-white">
                                    <div><strong>Rule:</strong> {{ $finding->ruleId }}</div>
                                    <div><strong>Severity:</strong> {{ $finding->severity->value }}</div>
                                    <div><strong>Message:</strong> {{ $finding->message }}</div>

                                    @if($finding->table !== null)
                                        <div><strong>Table:</strong> {{ $finding->table }}</div>
                                    @endif

                                    @if($finding->column !== null)
                                        <div><strong>Column:</strong> {{ $finding->column }}</div>
                                    @endif

                                    @if($finding->recommendation !== null)
                                        <div><strong>Recommendation:</strong> {{ $finding->recommendation }}</div>
                                    @endif

                                    @if($finding->metadata !== [])
                                        <div><strong>Metadata:</strong>
                                            <ul class="mb-0">
                                                @foreach($finding->metadata as $key => $value)
                                                    <li>{{ $key }}: {{ $value }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    @endif
</div>
</body>
</html>
