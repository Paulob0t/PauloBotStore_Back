<?php

namespace App\Controllers;

use App\Models\Dashboard;

class DashboardController
{
    private Dashboard $dashboardModel;

    public function __construct()
    {
        $this->dashboardModel = new Dashboard();
    }

    /**
     * Verificar sesión y obtener todos los datos necesarios para renderizar el Dashboard.
     */
    public function getDashboardData(): array
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['login']) || $_SESSION['login'] === false) {
            header('Location: login.php');
            exit();
        }

        $nombreUsuario = $_SESSION['nombre_usuario'] ?? 'Administrador';

        $sales = $this->dashboardModel->getSalesMetrics();
        $inventory = $this->dashboardModel->getInventoryMetrics();
        $chart = $this->dashboardModel->getSalesLast7DaysChart();
        $topProducts = $this->dashboardModel->getTopProducts(5);
        $recentSales = $this->dashboardModel->getRecentSales(6);

        return [
            'nombreUsuario' => $nombreUsuario,
            'sales' => $sales,
            'inventory' => $inventory,
            'chart' => $chart,
            'topProducts' => $topProducts,
            'recentSales' => $recentSales
        ];
    }
}
