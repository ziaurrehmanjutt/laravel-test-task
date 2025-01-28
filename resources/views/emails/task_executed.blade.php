<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Execution Result</title>
</head>
<body>
    <h1>{{ $taskName }}</h1>
    
    <p><strong>Status:</strong> {{ $taskStatus }}</p>

    @if($taskStatus == 'completed')
        <p><strong>Response:</strong></p>
        <pre>{{ $taskResponse }}</pre>
    @elseif($taskStatus == 'failed')
        <p><strong>Error:</strong></p>
        <pre>{{ $taskError }}</pre>
    @endif

    <p>Thank you!</p>
</body>
</html>
