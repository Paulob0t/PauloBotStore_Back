<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>PauloBot Store | Plataforma Inteligente Vending & Ecommerce</title>
    <meta name="description" content="Bienvenido a PauloBot Store. Tu plataforma inteligente de automatización, ventas 24/7 y gestión de máquinas expendedoras.">
    <meta name="keywords" content="PauloBot Store, vending box, máquinas expendedoras, automatización, negocio pasivo">
    <meta name="author" content="PauloBot Store">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Crect width='100' height='100' rx='20' fill='%236366f1'/%3E%3Ctext x='50' y='67' font-size='48' text-anchor='middle' fill='white' font-weight='bold'%3EPB%3C/text%3E%3C/svg%3E">
    
    <!-- Fuentes modernas -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary: #6366f1;
            --primary-hover: #4f46e5;
            --secondary: #0f172a;
            --accent: #f59e0b;
            --bg-light: #f8fafc;
            --card-bg: #ffffff;
            --text-dark: #1e293b;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.05);
            --shadow-md: 0 10px 30px -5px rgba(0,0,0,0.08);
            --shadow-lg: 0 20px 40px -10px rgba(99, 102, 241, 0.15);
            --radius-lg: 24px;
            --radius-md: 16px;
            --radius-pill: 50px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-light);
            color: var(--text-dark);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 24px;
        }

        /* Navbar elegante */
        .navbar {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(226, 232, 240, 0.8);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .navbar-inner {
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 80px;
        }

        .brand-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--secondary);
            letter-spacing: -0.5px;
        }

        .brand-logo .logo-icon {
            width: 42px;
            height: 42px;
            background: linear-gradient(135deg, var(--primary), #818cf8);
            color: white;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        }

        .brand-logo span {
            color: var(--primary);
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        /* Botones de acción */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 22px;
            border-radius: var(--radius-pill);
            font-weight: 600;
            font-size: 0.92rem;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            border: none;
        }

        .btn-client {
            background: var(--primary);
            color: white;
            box-shadow: 0 4px 14px rgba(99, 102, 241, 0.35);
        }

        .btn-client:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.45);
        }

        .btn-admin {
            background: #ffffff;
            color: var(--secondary);
            border: 1.5px solid var(--border);
            box-shadow: var(--shadow-sm);
        }

        .btn-admin:hover {
            border-color: var(--secondary);
            background: var(--secondary);
            color: white;
            transform: translateY(-2px);
        }

        /* Hero Section */
        .hero {
            padding: 80px 0 60px;
            position: relative;
            overflow: hidden;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 16px;
            background: #e0e7ff;
            color: var(--primary);
            border-radius: var(--radius-pill);
            font-size: 0.85rem;
            font-weight: 700;
            margin-bottom: 24px;
        }

        .hero-title {
            font-size: 3.2rem;
            font-weight: 800;
            line-height: 1.15;
            color: var(--secondary);
            margin-bottom: 20px;
            max-width: 800px;
            letter-spacing: -1px;
        }

        .hero-title span {
            background: linear-gradient(135deg, var(--primary), #a855f7);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .hero-subtitle {
            font-size: 1.2rem;
            color: var(--text-muted);
            max-width: 620px;
            margin-bottom: 40px;
        }

        /* Portales Destacados (Clientes vs Admin) */
        .portal-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 28px;
            margin-top: 20px;
        }

        .portal-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 36px 32px;
            transition: all 0.3s ease;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-shadow: var(--shadow-sm);
        }

        .portal-card:hover {
            transform: translateY(-6px);
            box-shadow: var(--shadow-lg);
            border-color: rgba(99, 102, 241, 0.4);
        }

        .portal-card.admin-card:hover {
            border-color: rgba(15, 23, 42, 0.4);
        }

        .portal-icon {
            width: 64px;
            height: 64px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            margin-bottom: 24px;
        }

        .portal-card.client-card .portal-icon {
            background: #e0e7ff;
            color: var(--primary);
        }

        .portal-card.admin-card .portal-icon {
            background: #f1f5f9;
            color: var(--secondary);
        }

        .portal-card h3 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--secondary);
            margin-bottom: 12px;
        }

        .portal-card p {
            color: var(--text-muted);
            font-size: 0.98rem;
            margin-bottom: 28px;
            flex-grow: 1;
        }

        .portal-btn {
            width: 100%;
            justify-content: center;
            padding: 14px 24px;
            font-size: 1rem;
        }

        /* Características / Módulos */
        .features-section {
            padding: 80px 0;
            border-top: 1px solid var(--border);
        }

        .section-header {
            text-align: center;
            max-width: 600px;
            margin: 0 auto 56px;
        }

        .section-header h2 {
            font-size: 2rem;
            font-weight: 800;
            color: var(--secondary);
            margin-bottom: 12px;
        }

        .section-header p {
            color: var(--text-muted);
            font-size: 1.05rem;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 24px;
        }

        .feature-item {
            background: var(--card-bg);
            padding: 28px 24px;
            border-radius: var(--radius-md);
            border: 1px solid var(--border);
            transition: all 0.25s ease;
        }

        .feature-item:hover {
            border-color: var(--primary);
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
        }

        .feature-icon {
            font-size: 1.75rem;
            color: var(--primary);
            margin-bottom: 16px;
        }

        .feature-item h4 {
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--secondary);
            margin-bottom: 8px;
        }

        .feature-item p {
            font-size: 0.9rem;
            color: var(--text-muted);
        }

        /* Footer */
        .footer {
            background: var(--secondary);
            color: #94a3b8;
            padding: 40px 0;
            margin-top: 60px;
            font-size: 0.9rem;
        }

        .footer-inner {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .footer-brand {
            color: white;
            font-weight: 700;
            font-size: 1.1rem;
        }

        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.2rem;
            }
            .navbar-inner {
                flex-direction: column;
                height: auto;
                padding: 16px 0;
                gap: 16px;
            }
            .portal-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

    <!-- Navegación Superior -->
    <nav class="navbar">
        <div class="container">
            <div class="navbar-inner">
                <a href="./" class="brand-logo">
                    <div class="logo-icon"><i class="fas fa-robot"></i></div>
                    <div>PauloBot <span>Store</span></div>
                </a>
                <div class="nav-actions">
                    <a href="./store/" class="btn btn-client">
                        <i class="fas fa-shopping-bag"></i> Sistema de Clientes
                    </a>
                    <a href="./admin/login.php" class="btn btn-admin">
                        <i class="fas fa-user-shield"></i> Administrador
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-badge">
                <i class="fas fa-sparkles"></i> Ecosistema Inteligente de Ventas 24/7
            </div>
            <h1 class="hero-title">
                Bienvenido a <span>PauloBot Store</span>
            </h1>
            <p class="hero-subtitle">
                Plataforma de vending y comercio automatizado. Accede directamente al sistema de compras para usuarios o administra el inventario, ventas y caja.
            </p>

            <!-- Portales Principales -->
            <div class="portal-grid">
                
                <!-- Tarjeta 1: Sistema de Clientes -->
                <div class="portal-card client-card">
                    <div>
                        <div class="portal-icon">
                            <i class="fas fa-store"></i>
                        </div>
                        <h3>Sistema de Clientes</h3>
                        <p>Catálogo interactivo de productos, snacks, bebidas y terminal de compra automatizada para usuarios finales.</p>
                    </div>
                    <a href="./store/" class="btn btn-client portal-btn">
                        <i class="fas fa-shopping-cart"></i> Acceder a Tienda / Clientes
                    </a>
                </div>

                <!-- Tarjeta 2: Panel Administrador -->
                <div class="portal-card admin-card">
                    <div>
                        <div class="portal-icon">
                            <i class="fas fa-chart-pie"></i>
                        </div>
                        <h3>Panel Administrador</h3>
                        <p>Gestión completa de inventario, cortes de caja, reporte de movimientos, configuración de empresa y monedero MDB.</p>
                    </div>
                    <a href="./admin/login.php" class="btn btn-admin portal-btn">
                        <i class="fas fa-key"></i> Acceso Administrador
                    </a>
                </div>

            </div>
        </div>
    </section>

    <!-- Módulos del Sistema -->
    <section class="features-section">
        <div class="container">
            <div class="section-header">
                <h2>Capacidades del Sistema</h2>
                <p>Estructurado para máxima eficiencia en punto de venta y administración remota.</p>
            </div>

            <div class="features-grid">
                <div class="feature-item">
                    <div class="feature-icon"><i class="fas fa-cash-register"></i></div>
                    <h4>Venta Automática</h4>
                    <p>Procesamiento de pagos y emisión instantánea de despachos.</p>
                </div>
                <div class="feature-item">
                    <div class="feature-icon"><i class="fas fa-coins"></i></div>
                    <h4>Monedero MDB</h4>
                    <p>Control exacto de cambio, validación de monedas y billetes.</p>
                </div>
                <div class="feature-item">
                    <div class="feature-icon"><i class="fas fa-boxes-stacked"></i></div>
                    <h4>Gestión de Productos</h4>
                    <p>Categorías, subcategorías y destacado de productos en tiempo real.</p>
                </div>
                <div class="feature-item">
                    <div class="feature-icon"><i class="fas fa-cloud-arrow-up"></i></div>
                    <h4>Sync Híbrida</h4>
                    <p>Sincronización continua de transacciones entre servidor local y nube.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-inner">
                <div class="footer-brand">
                    <i class="fas fa-robot"></i> PauloBot Store
                </div>
                <div>
                    © 2026 PauloBot Store – Todos los derechos reservados.
                </div>
            </div>
        </div>
    </footer>

</body>
</html>