<?php

use App\Models\Training;

return [
    'trainings' => [
        'label' => 'Trainings',
        'model' => Training::class,
        'table' => 'trainings',
        'permissions' => ['trainings.view', 'view_training', 'reports.view'],
        'joins' => [],
        'fields' => [
            'id' => ['label' => 'ID', 'column' => 'trainings.id', 'type' => 'number'],
            'title' => ['label' => 'Title', 'column' => 'trainings.title', 'type' => 'string'],
            'description' => ['label' => 'Description', 'column' => 'trainings.description', 'type' => 'string'],
            'status' => [
                'label' => 'Status',
                'column' => 'trainings.is_active',
                'type' => 'enum',
                'options' => ['active', 'inactive'],
                'value_map' => ['active' => 1, 'inactive' => 0],
                'display_map' => ['1' => 'Active', '0' => 'Inactive'],
            ],
            'start_date' => ['label' => 'Start Date', 'column' => 'trainings.starts_at', 'type' => 'date'],
            'end_date' => ['label' => 'End Date', 'column' => 'trainings.ends_at', 'type' => 'date'],
            'created_at' => ['label' => 'Created At', 'column' => 'trainings.created_at', 'type' => 'datetime'],
        ],
    ],
];
