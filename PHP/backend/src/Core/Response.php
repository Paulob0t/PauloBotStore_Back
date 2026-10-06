<?php

namespace App\Core;

class Response
{
    public static function json(mixed $data, int $status = 200, array $headers = []): void
    {
        // Enviar cabeceras CORS por defecto para permitir consumo desde el SPA (Angular)
        self::applyCorsHeaders();

        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');

        foreach ($headers as $key => $value) {
            header("$key: $value");
        }

        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function success(string $message, mixed $data = null, int $status = 200): void
    {
        $payload = [
            'success' => true,
            'message' => $message
        ];

        if ($data !== null) {
            $payload['data'] = $data;
        }

        self::json($payload, $status);
    }

    public static function error(string $message, mixed $errors = null, int $status = 400): void
    {
        $payload = [
            'success' => false,
            'message' => $message
        ];

        if ($errors !== null) {
            $payload['errors'] = $errors;
        }

        self::json($payload, $status);
    }

    public static function applyCorsHeaders(): void
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
        
        header("Access-Control-Allow-Origin: $origin");
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin');
    }
}
