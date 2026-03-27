<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Role Permissions Export</title>
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

        .section-title {
            font-size: 12px;
            font-weight: bold;
            background: #f3f4f6;
            padding: 6px;
            margin-top: 12px;
            margin-bottom: 6px;
            border-radius: 2px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
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

        .permission-name {
            font-family: monospace;
            font-size: 10px;
            background: #f9fafb;
            padding: 2px 4px;
            border-radius: 2px;
        }
    </style>
</head>

<body>
    <h1>{{ $role->name }} - Permission Matrix</h1>
    <p class="meta">Generated: {{ $generatedAt->format('Y-m-d H:i:s') }}</p>
    <p class="meta">Role Slug: <strong>{{ $role->slug }}</strong></p>
    <p class="meta">Total Permissions: <strong>{{ $role->permissions_count ?? 0 }}</strong></p>

    @forelse ($permissionGroups as $group)
        <div class="section-title">{{ $group['label'] }} ({{ count($group['permissions']) }})</div>

        <table>
            <thead>
                <tr>
                    <th style="width: 30%;">Action</th>
                    <th style="width: 35%;">Permission</th>
                    <th style="width: 35%;">Description</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($group['permissions'] as $permission)
                    <tr>
                        <td>{{ $permission['action_label'] }}</td>
                        <td><span class="permission-name">{{ $permission['name'] }}</span></td>
                        <td>{{ $permission['description'] ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @empty
        <p class="meta">No permissions assigned.</p>
    @endforelse
</body>

</html>
