<?php

use App\Models\User;

return [
    'users' => [
        'label' => 'Users',
        'model' => User::class,
        'table' => 'users',
        'permissions' => ['users.view', 'view_user_management', 'reports.view'],
        'joins' => [],
        'fields' => [
            'id' => ['label' => 'ID', 'column' => 'users.id', 'type' => 'number'],
            'name' => ['label' => 'Name', 'column' => 'users.name', 'type' => 'string'],
            'email' => ['label' => 'Email', 'column' => 'users.email', 'type' => 'string'],
            'email_verified_at' => ['label' => 'Verified At', 'column' => 'users.email_verified_at', 'type' => 'datetime'],
            'created_at' => ['label' => 'Created At', 'column' => 'users.created_at', 'type' => 'datetime'],
        ],
    ],
];
