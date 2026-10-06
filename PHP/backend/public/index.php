<?php

declare(strict_types=1);

// Manejo global para garantizar SIEMPRE respuestas JSON (Cero HTML)
set_exception_handler(function (\Throwable $e) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
});

require_once __DIR__ . '/../vendor/autoload.php';

use App\Controllers\AuthController;
use App\Controllers\CashRegisterController;
use App\Controllers\CategoryController;
use App\Controllers\ConfigController;
use App\Controllers\DashboardController;
use App\Controllers\DocsController;
use App\Controllers\MovementController;
use App\Controllers\ProductController;
use App\Controllers\SaleController;
use App\Controllers\ServiceController;
use App\Controllers\UserController;
use App\Core\Response;
use App\Core\Router;

// Aplicar cabeceras CORS
Response::applyCorsHeaders();

$router = new Router();

// Rutas de Documentación Swagger / OpenAPI
$router->get('/api/v1/openapi.json', [DocsController::class, 'openApiJson']);
$router->get('/api/docs', [DocsController::class, 'swaggerUi']);
$router->get('/api/docs/swagger.json', [DocsController::class, 'openApiJson']);

// Rutas de Autenticación
$router->post('/api/v1/auth/login', [AuthController::class, 'login']);
$router->post('/api/v1/auth/register', [AuthController::class, 'register']);
$router->get('/api/v1/auth/me', [AuthController::class, 'me']);
$router->post('/api/v1/auth/logout', [AuthController::class, 'logout']);

// Rutas del Dashboard
$router->get('/api/v1/dashboard', [DashboardController::class, 'getMetrics']);

// Rutas de Categorías & Subcategorías
$router->get('/api/v1/categories', [CategoryController::class, 'getCategories']);
$router->get('/api/v1/categories/{id}/image', [CategoryController::class, 'getImage']);
$router->get('/api/v1/subcategories', [CategoryController::class, 'getSubcategories']);
$router->post('/api/v1/categories', [CategoryController::class, 'create']);
$router->put('/api/v1/categories/{id}', [CategoryController::class, 'update']);
$router->delete('/api/v1/categories/{id}', [CategoryController::class, 'delete']);
$router->post('/api/v1/categories/{id}/subcategories', [CategoryController::class, 'addSubcategory']);
$router->put('/api/v1/subcategories/{id}', [CategoryController::class, 'updateSubcategory']);
$router->delete('/api/v1/subcategories/{id}', [CategoryController::class, 'deleteSubcategory']);

// Rutas de Productos
$router->get('/api/v1/products', [ProductController::class, 'getAll']);
$router->get('/api/v1/products/featured', [ProductController::class, 'getFeatured']);
$router->post('/api/v1/products', [ProductController::class, 'create']);
$router->get('/api/v1/products/featured-order/{order}', [ProductController::class, 'checkFeaturedOrder']);
$router->get('/api/v1/products/{id}/image', [ProductController::class, 'getImage']);
$router->get('/api/v1/products/{id}', [ProductController::class, 'getById']);
$router->delete('/api/v1/products/{id}', [ProductController::class, 'delete']);

// Rutas de Movimientos / Ventas
$router->get('/api/v1/movements', [MovementController::class, 'getAll']);
$router->get('/api/v1/movements/summary', [MovementController::class, 'getSummary']);
$router->get('/api/v1/movements/{id}', [MovementController::class, 'getById']);

// Rutas de Cortes de Caja
$router->get('/api/v1/cash-register/status', [CashRegisterController::class, 'getStatus']);
$router->post('/api/v1/cash-register/open', [CashRegisterController::class, 'open']);
$router->post('/api/v1/cash-register/close', [CashRegisterController::class, 'close']);
$router->post('/api/v1/cash-register/movements', [CashRegisterController::class, 'addMovement']);
$router->get('/api/v1/cash-register/history', [CashRegisterController::class, 'getHistory']);
$router->get('/api/v1/cash-register/{id}', [CashRegisterController::class, 'getById']);
$router->put('/api/v1/cash-register/config', [CashRegisterController::class, 'updateConfig']);

// Rutas de Configuración
$router->get('/api/v1/config/company', [ConfigController::class, 'getCompany']);
$router->put('/api/v1/config/company', [ConfigController::class, 'updateCompany']);

// Rutas de Usuarios
$router->get('/api/v1/users', [UserController::class, 'getAll']);
$router->post('/api/v1/users', [UserController::class, 'create']);
$router->put('/api/v1/users/{id}/status', [UserController::class, 'updateStatus']);

// Rutas de Servicios Digitales (CFE, Movistar, Recargas, Pines)
$router->get('/api/v1/services', [ServiceController::class, 'getProviders']);
$router->post('/api/v1/services/cfe/check-balance', [ServiceController::class, 'checkCfe']);
$router->post('/api/v1/services/cfe/pay', [ServiceController::class, 'payCfe']);
$router->get('/api/v1/services/movistar/packages', [ServiceController::class, 'getMovistarPackages']);
$router->post('/api/v1/services/movistar/recharge', [ServiceController::class, 'rechargeMovistar']);

// Rutas de Ventas & Checkout (Carrito Kiosco)
$router->post('/api/v1/sales/checkout', [SaleController::class, 'checkout']);
$router->get('/api/v1/sales/{folio}/ticket', [SaleController::class, 'getTicket']);

// Dispatch de la solicitud
$router->dispatch();
