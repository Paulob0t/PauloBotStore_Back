<?php

namespace App;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: "1.0.0",
    title: "PauloBot Store REST API",
    description: "API REST moderna para la plataforma de vending y comercio automatizado PauloBot Store. Migración desacoplada con arquitectura SPA.",
    contact: new OA\Contact(name: "PauloBot Store Team", email: "admin@paulobot.com")
)]
#[OA\Server(
    url: "http://localhost:8000",
    description: "Servidor de Desarrollo Local (FrankenPHP)"
)]
#[OA\Tag(
    name: "Autenticación",
    description: "Endpoints de inicio de sesión, registro, sesión activa y cierre de sesión"
)]
#[OA\Tag(
    name: "Dashboard",
    description: "Endpoints de métricas, estadísticas e información operativa en tiempo real"
)]
#[OA\Tag(
    name: "Productos",
    description: "Endpoints de gestión de catálogo, creación, consulta e inventario de productos"
)]
#[OA\Tag(
    name: "Categorías",
    description: "Endpoints de consulta y administración de categorías y subcategorías"
)]
#[OA\Tag(
    name: "Movimientos",
    description: "Endpoints de consulta de ventas, historial de transacciones y desglose de tickets"
)]
#[OA\Tag(
    name: "Cortes de Caja",
    description: "Endpoints de gestión de caja, apertura de jornada, cierre de turno, arqueo y corte de caja"
)]
#[OA\Tag(
    name: "Configuración",
    description: "Endpoints de configuración general de la empresa, datos fiscales y formato de tickets"
)]
#[OA\Tag(
    name: "Usuarios",
    description: "Endpoints de consulta, creación y gestión de usuarios del sistema"
)]
#[OA\Tag(
    name: "Documentación",
    description: "Endpoints de especificación Swagger y OpenAPI"
)]
#[OA\SecurityScheme(
    securityScheme: "bearerAuth",
    type: "http",
    scheme: "bearer",
    bearerFormat: "JWT",
    description: "Introduce el token JWT para acceder a los endpoints protegidos"
)]
class OpenApi
{
}
