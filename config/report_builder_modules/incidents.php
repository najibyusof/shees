<?php

use App\Models\Incident;

return [
    'incidents' => [
        'label' => 'Incidents',
        'model' => Incident::class,
        'table' => 'incidents',
        'permissions' => ['incidents.submit', 'view_incident', 'reports.view'],
        'joins' => [
            ['table' => 'users as incident_reporters', 'first' => 'incident_reporters.id', 'operator' => '=', 'second' => 'incidents.reported_by'],
        ],
        'fields' => [
            'id' => ['label' => 'ID', 'column' => 'incidents.id', 'type' => 'number'],
            'title' => ['label' => 'Title', 'column' => 'incidents.title', 'type' => 'string'],
            'status' => [
                'label' => 'Status',
                'column' => 'incidents.status',
                'type' => 'enum',
                'options' => Incident::STATUSES,
            ],
            'classification' => [
                'label' => 'Classification',
                'column' => 'incidents.classification',
                'type' => 'enum',
                'options' => Incident::CLASSIFICATIONS,
            ],
            'location' => ['label' => 'Location', 'column' => 'incidents.location', 'type' => 'string'],
            'incident_datetime' => ['label' => 'Incident DateTime', 'column' => 'incidents.datetime', 'type' => 'datetime'],
            'assigned_to' => ['label' => 'Assigned To', 'column' => 'incident_reporters.name', 'type' => 'string'],
            'created_at' => ['label' => 'Created At', 'column' => 'incidents.created_at', 'type' => 'datetime'],
        ],
    ],
];
