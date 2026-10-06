<?php

namespace App\Controllers;

use App\Core\Response;
use OpenApi\Attributes as OA;
use OpenApi\Generator;

class DocsController
{
    #[OA\Get(
        path: "/api/v1/openapi.json",
        operationId: "getOpenApiSpec",
        summary: "Obtener esquema OpenAPI 3.0 / Swagger JSON",
        description: "Retorna la especificación OpenAPI generada dinámicamente a partir de los atributos de PHP.",
        tags: ["Documentación"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Especificación OpenAPI en formato JSON"
            )
        ]
    )]
    public function openApiJson(): void
    {
        error_reporting(E_ALL & ~E_DEPRECATED);

        $openapi = Generator::scan([
            __DIR__ . '/..',
        ]);

        Response::applyCorsHeaders();
        header('Content-Type: application/json; charset=utf-8');
        echo $openapi->toJson();
        exit;
    }

    public function swaggerUi(): void
    {
        Response::applyCorsHeaders();
        header('Content-Type: text/html; charset=utf-8');
        ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PauloBot Store API Docs - Swagger UI</title>
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5/swagger-ui.css" />
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Crect width='100' height='100' rx='20' fill='%236366f1'/%3E%3Ctext x='50' y='67' font-size='48' text-anchor='middle' fill='white' font-weight='bold'%3EPB%3C/text%3E%3C/svg%3E">
    <style>
        html { box-sizing: border-box; overflow-y: scroll; }
        *, *:before, *:after { box-sizing: inherit; }
        body { margin: 0; background: #0f172a; color: #f8fafc; font-family: system-ui, -apple-system, sans-serif; }
        .swagger-ui .topbar { display: none; }
        .swagger-ui { filter: invert(88%) hue-rotate(180deg); }
        .swagger-ui .wrapper { max-width: 1200px; margin: 0 auto; padding: 20px; }
    </style>
</head>
<body>
    <div id="swagger-ui"></div>
    <script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5/swagger-ui-standalone-preset.js"></script>
    <script>
        window.onload = function() {
            window.ui = SwaggerUIBundle({
                url: "/api/v1/openapi.json",
                dom_id: '#swagger-ui',
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIStandalonePreset
                ],
                plugins: [
                    SwaggerUIBundle.plugins.DownloadUrl
                ],
                layout: "StandaloneLayout"
            });
        };
    </script>
</body>
</html>
        <?php
        exit;
    }
}
