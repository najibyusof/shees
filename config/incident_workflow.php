<?php

return [
    // Toggle enforcement globally. When disabled, transitions ignore unresolved critical comments.
    'unresolved_critical_comments' => [
        'enabled' => env('INCIDENT_BLOCK_ON_UNRESOLVED_CRITICAL', true),

        // Comment types treated as critical when explicit is_critical flag is not set.
        'critical_comment_types' => ['action_required', 'review'],

        // Per-role enforcement for the optional gate.
        'role_rules' => [
            'Manager' => [
                'enforce' => false,
            ],
            'HOD HSSE' => [
                'enforce' => true,
            ],
            'APSB PD' => [
                'enforce' => true,
            ],
            'MRTS' => [
                'enforce' => true,
            ],
        ],
    ],
];
