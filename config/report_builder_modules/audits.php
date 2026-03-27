<?php

use App\Models\SiteAudit;

return [
    'audits' => [
        'label' => 'Audits',
        'model' => SiteAudit::class,
        'table' => 'site_audits',
        'permissions' => ['audits.view', 'view_audit', 'reports.view'],
        'joins' => [
            ['table' => 'users as audit_creators', 'first' => 'audit_creators.id', 'operator' => '=', 'second' => 'site_audits.created_by'],
        ],
        'fields' => [
            'id' => ['label' => 'ID', 'column' => 'site_audits.id', 'type' => 'number'],
            'reference' => ['label' => 'Reference', 'column' => 'site_audits.reference_no', 'type' => 'string'],
            'site_name' => ['label' => 'Site Name', 'column' => 'site_audits.site_name', 'type' => 'string'],
            'audit_type' => ['label' => 'Audit Type', 'column' => 'site_audits.audit_type', 'type' => 'string'],
            'status' => [
                'label' => 'Status',
                'column' => 'site_audits.status',
                'type' => 'enum',
                'options' => SiteAudit::STATUSES,
            ],
            'scheduled_for' => ['label' => 'Scheduled For', 'column' => 'site_audits.scheduled_for', 'type' => 'date'],
            'kpi_score' => ['label' => 'KPI Score', 'column' => 'site_audits.kpi_score', 'type' => 'number'],
            'assigned_to' => ['label' => 'Assigned To', 'column' => 'audit_creators.name', 'type' => 'string'],
            'created_at' => ['label' => 'Created At', 'column' => 'site_audits.created_at', 'type' => 'datetime'],
        ],
    ],
];
