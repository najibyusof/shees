<?php

return [
    'trends' => [
        // Default comparison period for widgets without explicit overrides.
        'default_window_days' => 7,

        // Cache TTL for trend calculations (seconds).
        'cache_seconds' => 120,

        // Per-widget trend settings. Widgets not listed use defaults above.
        'widgets' => [
            'total_users' => ['higher_is_better' => true],
            'kpi_overview' => ['higher_is_better' => true],
            'compliance_metrics' => ['higher_is_better' => true],
            'compliance_status' => ['higher_is_better' => true],
            'training_status' => ['higher_is_better' => true],

            // Operational queues and SLA widgets are more reactive on 1-day windows.
            'draft_pending_submission' => ['window_days' => 1],
            'review_sla_breach' => ['window_days' => 1],
            'pending_draft_review' => ['window_days' => 1],
            'closure_requests' => ['window_days' => 1],
            'closure_sla_breach' => ['window_days' => 1],
            'final_approval_queue' => ['window_days' => 1],
            'closure_approvals' => ['window_days' => 1],
            'final_approval_sla_breach' => ['window_days' => 1],

            // Broader management/compliance signals use 30-day windows.
            'team_incidents' => ['window_days' => 30],
            'project_incident_overview' => ['window_days' => 30],
            'high_level_analytics' => ['window_days' => 30],
        ],
    ],
];
