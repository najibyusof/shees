<?php

return [
    'default' => 'default',
    'documentations' => [
        'default' => [
            'api' => [
                'title' => 'SHEES API',
                'version' => 'v1',
                'description' => 'Safety, Health, Environment & Emergency System API Documentation',
            ],
            'routes' => [
                /*
                 * Route for accessing api documentation interface, e.g. JSON
                 * false to disable this route
                 */
                'api' => 'api/documentation/json',

                /*
                 * Route for accessing parsed swagger yaml, for swagger-ui, e.g. JSON
                 * false to disable this route
                 */
                'docs' => 'api/documentation',

                /*
                 * Route for Oauth2 redirect, necessary for swagger-ui functioning
                 */
                'oauth2_callback' => 'api/oauth2-callback',
            ],
            'paths' => [
                /*
                 * Absolute paths to directories containing OpenAPI annotations.
                 */
                'annotations' => [
                    base_path('app/OpenAPI'),
                ],

                /*
                 * Absolute path to where parsed OpenAPI docs are stored.
                 */
                'docs' => storage_path('api-docs'),

                /*
                 * Base path used by swagger-php scanner.
                 */
                'base' => base_path(),

                /*
                 * Absolute paths excluded from scanning.
                 */
                'excludes' => [],

                /*
                 * Absolute file path to location where parsed swagger will be stored
                 */
                'docs_json' => 'api-docs.json',

                /*
                 * Absolute file path to location where parsed swagger yaml will be stored
                 */
                'docs_yaml' => 'api-docs.yaml',

                /*
                 * File path from `public` folder(s) to use as js, css, and swagger-ui required files.
                 * This directory must be under your project public path.
                 * You may want to change this:1. publically available site
                 * 2. only csama user has access to the swagger-ui
                 * 3. dedicated domain/subdomain for swagger-ui
                 */
                'swagger_ui_assets_path' => 'vendor/swagger-ui/dist',

                /*
                 * Absolute path to directory where to export views
                 */
                'views' => base_path('resources/views/vendor/l5-swagger'),

                /*
                 * Edit to set the api's base path
                 * Example: Set full URL in individual swagger annotations (in controllers)
                 * `#[OA\Info(title="My API")]`
                 * `servers` parameter under #[OA\OpenApi()] or individual operations will override this value
                 */
                'base_path' => '/api',
            ],
            'scanOptions' => [
                /**
                 * analyser: defaults to StaticAnalyser
                 *
                 * Other valid values:
                 * - ReflectionAnalyser
                 */
                'analyser' => new \OpenApi\Analysers\ReflectionAnalyser([
                    new \OpenApi\Analysers\AttributeAnnotationFactory(),
                    new \OpenApi\Analysers\DocBlockAnnotationFactory(),
                ]),

                /**
                 * Controllers & methods to include in swagger documentation
                 * Set to [] empty array to include all files from app/Http/Controllers
                 *
                 * Examples:
                 * exclude: ['Api/V1/PassportAuthController', 'Api/V2/PassportAuthController']
                 */
                'exclude' => [],

                /**
                 * Controllers & methods to include in swagger documentation
                 * Set to [] empty array to disable restriction
                 *
                 * Examples:
                 * include: ['Api/V1/PassportAuthController', 'Api/V2/PassportAuthController']
                 */
                'include' => [],
            ],
            'securitySchemes' => [
                'bearer_token' => [
                    'type' => 'http',
                    'description' => 'Login with email and password to get the authentication token',
                    'name' => 'Token',
                    'in' => 'header',
                    'scheme' => 'bearer',
                    'bearerFormat' => 'Sanctum',
                ],
            ],
            'info' => [
                'description' => 'Safety, Health, Environment & Emergency System - RESTful API for incident management, training, inspections, and worker tracking.',
                'x-logo' => [
                    'url' => 'https://via.placeholder.com/190x90?text=SHEES+API',
                ],
            ],
            'servers' => [
                [
                    'url' => env('APP_URL', 'http://localhost'),
                    'description' => 'Current Environment',
                ],
            ],
            'security' => [
                /*
                 * Examples of Securities used by this API
                 */
                [
                    'bearer_token' => [],
                ],
            ],
        ],
    ],
    'defaults' => [
        'routes' => [
            /*
             * Route for accessing api documentation interface, e.g. JSON
             * false to disable this route
             */
            'docs' => 'api/documentation',

            /*
             * Route for accessing parsed swagger yaml, for swagger-ui, e.g. JSON
             * false to disable this route
             */
            'docs_json' => 'api/documentation/json',

            /*
             * Route for Oauth2 redirect, necessary for swagger-ui functioning
             */
            'oauth2_callback' => 'api/oauth2-callback',

            /*
             * Middleware allowing these routes to be ran
             */
            'middleware' => [
                'api',
            ],

            /*
             * Route Group middleware
             */
            'group_middleware' => [],
        ],
        'paths' => [
            /*
             * Absolute paths to directories containing OpenAPI annotations.
             */
            'annotations' => [
                base_path('app/OpenAPI'),
            ],

            /*
             * Absolute path to where parsed OpenAPI docs are stored.
             */
            'docs' => storage_path('api-docs'),

            /*
             * Base path used by swagger-php scanner.
             */
            'base' => base_path(),

            /*
             * Absolute paths excluded from scanning.
             */
            'excludes' => [],

            /*
             * Absolute file path to location where parsed swagger will be stored
             */
            'docs_json' => 'api-docs.json',

            /*
             * Absolute file path to location where parsed swagger yaml will be stored
             */
            'docs_yaml' => 'api-docs.yaml',

            /*
             * File path from `public` folder(s) to use as js, css, and swagger-ui required files.
             * This directory must be under your project public path.
             * You may want to change this:1. publically available site
             * 2. only certain user has access to the swagger-ui
             * 3. dedicated domain/subdomain for swagger-ui
             */
            'swagger_ui_assets_path' => 'vendor/swagger-ui/dist',

            /*
             * Absolute path to directory where to export views
             */
            'views' => base_path('resources/views/vendor/l5-swagger'),

            /*
             * Edit to set the api's base path
             * Example: Set full URL in individual swagger annotations (in controllers)
             * `#[OA\Info(title="My API")]`
             * `servers` parameter under #[OA\OpenApi()] or individual operations will override this value
             */
            'base_path' => '/api',
        ],

        /*
         * Constants which can be used in annotations.
         */
        'constants' => [
            'L5_SWAGGER_CONST_HOST' => env('L5_SWAGGER_CONST_HOST', env('APP_URL', 'http://localhost')),
        ],
    ],
];
