<?php

declare(strict_types=1);

namespace App\Controllers;

use OpenApi\Attributes as OA;
use OpenApi\Generator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Swagger/OpenAPI Documentation Controller
 * Dynamically generates API documentation from controller annotations
 */
class SwaggerController
{
    /**
     * GET /api/docs
     * Returns OpenAPI JSON specification
     */
    public function json(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        // Scan controllers directory for OpenAPI annotations
        $openapi = Generator::scan([__DIR__]);

        $response->getBody()->write($openapi->toJson());
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Access-Control-Allow-Origin', '*');
    }

    /**
     * GET /api/docs/yaml
     * Returns OpenAPI YAML specification
     */
    public function yaml(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        // Scan controllers directory for OpenAPI annotations
        $openapi = Generator::scan([__DIR__]);

        $response->getBody()->write($openapi->toYaml());
        return $response
            ->withHeader('Content-Type', 'text/yaml')
            ->withHeader('Access-Control-Allow-Origin', '*');
    }

    /**
     * GET /api-docs
     * Swagger UI interface
     */
    public function ui(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $html = <<<'HTML'
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documentation API Calculateur IJ CARMF</title>
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5/swagger-ui.css">
    <style>
        html {
            box-sizing: border-box;
            overflow: -moz-scrollbars-vertical;
            overflow-y: scroll;
        }
        *, *:before, *:after {
            box-sizing: inherit;
        }
        body {
            margin: 0;
            padding: 0;
        }
        .swagger-ui .topbar {
            background-color: #1b5e20;
        }
        .swagger-ui .info .title {
            color: #1b5e20;
        }
    </style>
</head>
<body>
    <div id="swagger-ui"></div>
    <script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5/swagger-ui-standalone-preset.js"></script>
    <script>
        window.onload = function() {
            const ui = SwaggerUIBundle({
                url: "/api/docs",
                dom_id: '#swagger-ui',
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIStandalonePreset
                ],
                plugins: [
                    SwaggerUIBundle.plugins.DownloadUrl
                ],
                layout: "StandaloneLayout",
                validatorUrl: null,
                displayRequestDuration: true,
                filter: true,
                tryItOutEnabled: true,
                requestSnippetsEnabled: true,
                requestSnippets: {
                    generators: {
                        curl_bash: {
                            title: "cURL (bash)",
                            syntax: "bash"
                        },
                        curl_powershell: {
                            title: "cURL (PowerShell)",
                            syntax: "powershell"
                        },
                        curl_cmd: {
                            title: "cURL (CMD)",
                            syntax: "bash"
                        }
                    },
                    defaultExpanded: true,
                    languages: null
                }
            });
            window.ui = ui;
        };
    </script>
</body>
</html>
HTML;

        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    }
}
