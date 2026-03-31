<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>SHEES API Documentation</title>
    <link rel="stylesheet" href="{{ route('l5-swagger.default.asset', ['asset' => 'swagger-ui.css']) }}">
    <style>
        html {
            box-sizing: border-box;
            overflow: -moz-scrollbars-vertical;
            overflow-y: scroll;
        }

        *,
        *before,
        *after {
            box-sizing: inherit;
        }

        body {
            margin: 0;
            padding: 0;
        }

        .swagger-ui {
            font-family: sans-serif;
        }

        .topbar {
            background-color: #fafafa;
            padding: 10px 0;
        }

        .topbar-wrapper {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .topbar-wrapper a {
            text-decoration: none;
            color: #0066cc;
            font-weight: 700;
        }

        .topbar-wrapper a:hover {
            color: #0052a3;
        }

        .topbar-wrapper span {
            color: #666;
        }
    </style>
</head>

<body>
    <div class="topbar">
        <div class="topbar-wrapper">
            <div>
                <h2 style="margin: 0;">📋 SHEES API Documentation</h2>
                <p style="margin: 5px 0 0 0; color: #666; font-size: 14px;">Safety, Health, Environment & Emergency
                    System</p>
            </div>
            <div>
                <span>API Version: v1</span>
            </div>
        </div>
    </div>

    <div id="swagger-ui"></div>

    <script src="{{ route('l5-swagger.default.asset', ['asset' => 'swagger-ui-bundle.js']) }}"></script>
    <script src="{{ route('l5-swagger.default.asset', ['asset' => 'swagger-ui-standalone-preset.js']) }}"></script>
    <script>
        window.onload = function() {
            if (typeof SwaggerUIBundle === 'undefined') {
                document.getElementById('swagger-ui').innerHTML =
                    '<div style="padding:20px;color:#b00020;">Failed to load Swagger UI assets. Check internet/CDN access.</div>';
                return;
            }

            const ui = SwaggerUIBundle({
                url: "{{ route('api.documentation.json') }}",
                dom_id: '#swagger-ui',
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIStandalonePreset
                ],
                plugins: [
                    SwaggerUIBundle.plugins.DownloadUrl
                ],
                layout: "StandaloneLayout",
                deepLinking: true,
                defaultModelsExpandDepth: 1,
                defaultModelExpandDepth: 1,
                syntaxHighlight: {
                    activate: true,
                    theme: "monokai"
                },
                tryItOutEnabled: true,
                filter: true,
                persistAuthorization: true,
                displayRequestDuration: true,
                onComplete: function() {
                    const token = localStorage.getItem('api_token');
                    if (token) {
                        // Security scheme key must match components.securitySchemes key.
                        ui.preauthorizeApiKey('bearer_token', token);
                    }
                }
            });

            window.ui = ui;
        };
    </script>
</body>

</html>
