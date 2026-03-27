<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Audit Logs Export</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #1f2937;
        }

        h1 {
            margin-bottom: 4px;
            font-size: 18px;
        }

        .meta {
            margin-bottom: 12px;
            color: #4b5563;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #d1d5db;
            padding: 6px;
            vertical-align: top;
            text-align: left;
        }

        th {
            background: #f3f4f6;
            font-size: 10px;
            text-transform: uppercase;
        }
    </style>
</head>

<body>
    <h1>Audit Logs</h1>
    <p class="meta">Generated: {{ $generatedAt->format('Y-m-d H:i:s') }}</p>
    <p class="meta">
        Filters:
        action={{ $filters['action'] ?? 'all' }},
        module={{ $filters['module'] ?? 'all' }},
        user_id={{ $filters['user_id'] ?? 'all' }},
        from={{ $filters['from'] ?? '-' }},
        to={{ $filters['to'] ?? '-' }}
    </p>

    <table>
        <thead>
            <tr>
                <th>Timestamp</th>
                <th>User</th>
                <th>Action</th>
                <th>Module</th>
                <th>Record</th>
                <th>Request ID</th>
                <th>IP</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($logs as $log)
                @php $metadata = is_array($log->metadata) ? $log->metadata : []; @endphp
                <tr>
                    <td>{{ $log->created_at?->format('Y-m-d H:i:s') }}</td>
                    <td>{{ $log->user?->name ?? 'System' }}</td>
                    <td>{{ strtoupper($log->action) }}</td>
                    <td>{{ $log->module }}</td>
                    <td>{{ class_basename($log->auditable_type ?? 'N/A') }}#{{ $log->auditable_id ?? '-' }}</td>
                    <td>{{ $metadata['request_id'] ?? '-' }}</td>
                    <td>{{ $metadata['ip_address'] ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>
