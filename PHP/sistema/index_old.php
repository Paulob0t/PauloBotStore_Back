<?php
// ⚡ NO cargar datos aquí - dejar que JS lo haga con caché
// Esto ahorra ~200-500ms en el servidor
$productos = [];
$categorias = [];
$servicios = [];
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>VendingBox - Tienda Online</title>
    <link rel="icon" type="image/png" href="images/favicon.png">
    
    <!-- Preconnect a dominios externos PRIMERO -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    
    <!-- ⚡ CSS CRÍTICOS - Bootstrap desde CDN (mejor caché) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="./css/main-styles.css">
    <link rel="stylesheet" href="./css/modern-clean-theme.css">
    <link rel="stylesheet" href="./css/modern-clean-overrides.css">
    
    <!-- ⚡ Fonts optimizadas -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
    
    <!-- ⚡ CSS Secundarios - Carga diferida (no críticos) -->
    <link rel="stylesheet" href="./css/image-optimization.css" media="print" onload="this.media='all'">
    <link rel="stylesheet" href="./css/visual-fixes.css" media="print" onload="this.media='all'">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" media="print" onload="this.media='all'">
    <noscript>
        <link rel="stylesheet" href="./css/image-optimization.css">
        <link rel="stylesheet" href="./css/visual-fixes.css">
    </noscript>
    
    <script>
        window.products = <?php echo !empty($productos) ? json_encode($productos, JSON_UNESCAPED_UNICODE) : '[]'; ?>;
        window.categories = <?php echo !empty($categorias) ? json_encode($categorias, JSON_UNESCAPED_UNICODE) : '[]'; ?>;
        window.servicios = [];
    </script>
    </script>
    
    <script>
        !function(){
            const TTL=18e5; // 30 minutos
            try{
                const c=JSON.parse(localStorage.getItem('vb_all_data')||'null');
                if(c&&c.timestamp&&(Date.now()-c.timestamp)<TTL){
                    window.categories=c.categories||[];
                    window.products=c.products||[];
                    window.featuredProducts=c.featured||[];
                    window.cacheLoaded=true;
                }
            }catch{}
        }();
    </script>

</head>

<body class="js">
        <div class="middle-inner">
            <div class="header-container">
                <div class="row">
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center py-1">
                            <div class="logo"><a href="index.php"><img src="./images/logo.png" alt="logo" width="150" height="auto" fetchpriority="high"></a></div>
                            <!-- <div class="header-categories-center">
                                <button class="header-scroll-btn left" id="header-scroll-left">
                                    <i class="fa fa-chevron-left"></i>
                                </button>
                                <div class="header-categories-scroll" id="header-categories-bar">
                                </div>
                                <button class="header-scroll-btn right" id="header-scroll-right">
                                    <i class="fa fa-chevron-right"></i>
                                </button>
                            </div> -->
                            <div class="sinlge-bar shopping">
                                <a href="#" class="single-icon">
                                    <i class="bi bi-cart"></i>
                                    <span class="total-count">
                                        <span id="cart-count-inner">0</span>
                                    </span>
                                </a>
                                <div class="shopping-item">
                                    <div class="dropdown-cart-header">
                                        <span>0 Productos</span>
                                        <a href="cart.php"><i class="fa fa-shopping-cart"></i> Ver carrito</a>
                                    </div>
                                    <ul class="shopping-list"></ul>
                                    <div class="bottom">
                                        <div class="total">
                                            <span>Total</span>
                                            <span class="total-amount">$0.00</span>
                                        </div>
                                        <a href="cart.php" class="btn animate">
                                            <i class="fa fa-credit-card"></i>
                                            Pagar
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    

    <!-- <div class="category-indicator" id="category-indicator">
        <div class="card-body">
            <div class="category-icon"></div>
            <div class="category-name" id="category-indicator-text">Todas las categorías</div>
        </div>
    </div> -->

    <!-- Botón flotante para regresar -->
    <button class="back-button hidden" id="back-button" title="Regresar">
        <i class="fa fa-arrow-left"></i>
        <span>Regresar</span>
    </button>

    <!-- Sección de Productos Destacados -->
    <section class="featured-products-section">
        <div class="featured-header">
            <h2>Productos Destacados</h2>
            <p>Descubre nuestras mejores ofertas y productos más populares</p>
        </div>
        <div id="featured-products-container">
            <!-- Los productos destacados se cargarán aquí dinámicamente -->
        </div>
    </section>

    <section class="featured-products-section">
        <div class="featured-header">
            <h2>Servicios</h2>
            <p>Recarga saldo, paga servicios y disfruta de contenido digital</p>
        </div>
        <div id="services-container">
            <!-- Los servicios se cargarán aquí dinámicamente -->
        </div>
    </section>

    <section class="featured-products-section">
        <div class="featured-header">
            <h2>Categorias</h2>
            <p>Explora nuestras categorías de productos</p>
        </div>
        <div id="categories-container">
            <!-- Las categorías se cargarán aquí dinámicamente -->
        </div>
    </section>

    <!-- <div class="product-service-switch-wrapper">
        <div class="product-service-switch">
            <div class="switch-slider"></div>
            <button class="switch-btn active" id="productos-btn" data-type="productos">
                <i class="fa fa-cube"></i>Productos
            </button>
            <button class="switch-btn" id="servicios-btn" data-type="servicios">
                <i class="fa fa-cogs"></i>Servicios
            </button>
        </div>
    </div> -->


    

    

    <script>
        window.ServiceModal = {
            open({ title = 'Servicio', bodyHTML = '', footerHTML = '' } = {}) {
                const el = document.getElementById('service-modal');
                if (!el) return;
                document.getElementById('service-modal-title').textContent = title;
                document.getElementById('service-modal-body').innerHTML = bodyHTML;
                document.getElementById('service-modal-footer').innerHTML = footerHTML;
                el.style.display = 'flex';
            },
            close() {
                const el = document.getElementById('service-modal');
                if (!el) return;
                el.style.display = 'none';
                document.getElementById('service-modal-body').innerHTML = '';
                document.getElementById('service-modal-footer').innerHTML = '';
            }
        };
        document.getElementById('service-modal-close')?.addEventListener('click', () => window.ServiceModal.close());
        document.getElementById('service-modal')?.addEventListener('click', (e) => {
            if (e.target.id === 'service-modal') window.ServiceModal.close();
        });
    </script>

    <template id="category-template">
        <div class="category-card">
            <img class="category-image" src="" alt="" loading="lazy" decoding="async">
            <div class="no-image-placeholder">
                <i class="bi bi-grid"></i>
            </div>
            <div class="category-overlay">
                <h3 class="category-title"></h3>
            </div>
        </div>
    </template>

    <!-- Template para subcategorías -->
    <template id="subcategory-template">
        <div class="subcategory-card">
            <img class="subcategory-image" src="" alt="" loading="lazy" decoding="async">
            <div class="no-image-placeholder">
                <i class="bi bi-collection"></i>
            </div>
            <div class="subcategory-overlay">
                <h3 class="subcategory-title"></h3>
            </div>
        </div>
    </template>

    <!-- Template para productos -->
    <!-- Template para productos -->
    <template id="product-template">
        <div class="product-card">
            <div class="product-img-wrapper">
                <img class="product-img" src="" alt="" loading="lazy" decoding="async">
            </div>
            <div class="product-content">
                <h3 class="product-title"></h3>
                <div class="price-container">
                    <span class="product-price"></span>
                    <span class="product-original-price"></span>
                    <span class="product-discount"></span>
                </div>
                <button class="add-to-cart">
                    <i class="bi bi-cart-plus"></i>
                    Agregar al carrito
                </button>
            </div>
        </div>
    </template>

    <!-- Template para productos con overlay (estilo subcategoría) -->
    <template id="product-overlay-template">
        <div class="product-card-overlay">
            <img class="product-img" src="" alt="" loading="lazy" decoding="async">
            <div class="product-overlay">
                <h3 class="product-title"></h3>
                <span class="product-price"></span>
                <button class="add-to-cart">
                    <i class="bi bi-cart-plus"></i>
                    Agregar
                </button>
            </div>
        </div>
    </template>

    <div class="shopping-cart-sidebar" id="cart-sidebar">
        <div class="cart-header">
            <h3>Carrito</h3>
            <button onclick="document.getElementById('cart-sidebar').style.display='none'">×</button>
        </div>
        <div id="cart-items"></div>
        <div class="cart-footer">
            <div class="cart-total-row">
                <strong>Total:</strong>
                <span id="cart-total">$0.00</span>
            </div>
            <button class="btn" onclick="window.location.href='cart.php'">
                Ver Carrito
            </button>
        </div>
    </div>

    <!-- ⚡ Scripts esenciales optimizados -->
    
    <!-- ⚡ jQuery y Bootstrap - Desde CDN para mejor caché y performance -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js" integrity="sha384-oBqDVmMz9ATKxIep9tiCxS/Z9fNfEXiDAYTujMAeBAsjFuCZSmKbSSUnQlmh/jp3" crossorigin="anonymous" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js" integrity="sha384-fbbOQedDUMZZ5KreZpsbe1LCZPVmfTnH7ois6mU1QK+m14rQ1l2bGBq41eYeM/fS" crossorigin="anonymous" defer></script>
    
    <!-- SweetAlert2 - Cargar async, no es crítico para First Paint -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11" defer></script>
    
    <!-- Scripts esenciales con defer (se cargan después del DOM) -->
    <script src="qz-tray.js" defer></script>
    <script src="js/modulos/print-ticket.js" defer></script>
    <script src="js/modulos/image-optimizer.js" defer></script>
    <script src="js/monedero-integration.js" defer></script>
    <script src="js/modulos/service-payment-handler.js" defer></script>
    <script src="js/modulos/carrito.js" defer></script>
    <script src="js/modulos/featured-products.js" defer></script>

    <!-- Inicializar el carrito en index.php -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (typeof Cart !== 'undefined') {
                Cart.init();
                
                // Configurar eventos del mini-carrito (hover)
                if (typeof setupCartEvents === 'function') {
                    setupCartEvents();
                }
            }
        });
    </script>

    <!-- Scripts de módulos principales - defer -->
    <!-- ⚡ Usando versión optimizada de service-cache -->
    <script type="module" src="./js/modulos/service-cache-optimized.js"></script>
    <script type="module" src="./js/modulos/main.js"></script>
    <!-- ⚡ Usando versión optimizada de services-menu -->
    <script type="module" src="./js/modulos/services-menu-optimized.js"></script>
    <script src="js/modulos/categories-display.js" defer></script>
    <script src="./js/modulos/category-scroll.js" defer></script>
    <script src="js/modulos/category-indicator.js" defer></script>
    <script type="module" src="js/modulos/view-switcher.js"></script>

    <!-- 🚀 CARGADOR OPTIMIZADO - Caché agresivo para BD en nube -->
    <script defer>
        (async function() {
            const CACHE_KEY = 'vb_all_data';
            const TTL = 30 * 60 * 1000; // 30 minutos (BD en nube es lenta)
            const BG_REFRESH_TIME = 20 * 60 * 1000; // Refrescar después de 20 min
            
            // 1️⃣ Si ya cargamos del caché inline, solo refrescar si es viejo
            if (window.cacheLoaded) {
                try {
                    const cached = JSON.parse(localStorage.getItem(CACHE_KEY) || 'null');
                    const age = Date.now() - (cached?.timestamp || 0);
                    
                    // Solo refrescar si tiene más de 20 minutos
                    if (age > BG_REFRESH_TIME) {
                        fetchFreshData(true); // Background, sin bloquear UI
                    }
                } catch {}
                return; // Ya tenemos datos, no bloquear
            }
            
            // 2️⃣ No hay caché, cargar datos frescos (primera vez)
            fetchFreshData(false);
            
            async function fetchFreshData(isBackground = false) {
                try {
                    const controller = new AbortController();
                    const timeoutId = setTimeout(() => controller.abort(), 8000); // 8s timeout
                    
                    const response = await fetch('get_all_data.php', {
                        headers: { 'Accept': 'application/json' },
                        signal: controller.signal,
                        priority: isBackground ? 'low' : 'high'
                    });
                    
                    clearTimeout(timeoutId);
                    
                    if (!response.ok) throw new Error('Network error');
                    
                    const result = await response.json();
                    
                    if (result.success && result.data) {
                        // Actualizar variables globales
                        window.categories = result.data.categories || [];
                        window.products = result.data.products || [];
                        window.featuredProducts = result.data.featured_products || [];
                        
                        // Guardar en caché con timestamp
                        try {
                            localStorage.setItem(CACHE_KEY, JSON.stringify({
                                timestamp: Date.now(),
                                categories: window.categories,
                                products: window.products,
                                featured: window.featuredProducts
                            }));
                        } catch (e) {
                            // Caché lleno, limpiar y reintentar
                            try {
                                localStorage.clear();
                                localStorage.setItem(CACHE_KEY, JSON.stringify({
                                    timestamp: Date.now(),
                                    categories: window.categories,
                                    products: window.products,
                                    featured: window.featuredProducts
                                }));
                            } catch {}
                        }
                        
                        if (!isBackground) {
                            // Disparar evento para que los módulos se actualicen
                            window.dispatchEvent(new CustomEvent('dataLoaded', { 
                                detail: result.data 
                            }));
                        }
                    }
                } catch (error) {
                    // Si falla y NO hay caché, intentar fallback
                    if (!isBackground && (!window.products || window.products.length === 0)) {
                        // Intentar APIs individuales como último recurso
                        await Promise.allSettled([
                            fetch('get_products.php').then(r => r.json()).then(d => window.products = d),
                            fetch('get_categories.php').then(r => r.json()).then(d => window.categories = d)
                        ]);
                    }
                }
            }
        })();
    </script>

    <!-- ⚡ Prefetch OPTIMIZADO de servicios - Solo cuando se necesita -->
    <script defer>
        (function(){
            let prefetching = false;
            let prefetched = false;
            
            // ⚡ IDs críticos - Se cargan primero (más usados)
            const criticalServices = [
                { url: 'netflix_functional.php', key: 'netflix_services' },
                { url: 'spotify_functional.php', key: 'spotify_services' }
            ];
            
            // 📦 IDs secundarios - Se cargan después
            const secondaryServices = [
                { url: 'cfe_luz_functional.php', key: 'cfe' },
                { url: 'megacable_saldo_functional.php', key: 'megacable_services' },
                { url: 'movistar_recargas_functional.php', key: 'movistar' }
            ];
            
            // Batch fetch - Más eficiente
            async function batchFetch(services) {
                const promises = services.map(({url, key}) => 
                    fetch(url, { 
                        credentials: 'same-origin',
                        priority: 'low' // No interferir con recursos críticos
                    })
                    .then(r => r.ok ? r.json() : null)
                    .then(d => {
                        const list = d?.services || (Array.isArray(d) ? d : null);
                        if (list) window.ServiceCache?.set(key, list);
                    })
                    .catch(() => {})
                );
                await Promise.allSettled(promises);
            }
            
            async function prefetchServices() {
                if (prefetching || prefetched) return;
                prefetching = true;
                
                try {
                    // Fase 1: Críticos primero
                    await batchFetch(criticalServices);
                    
                    // Fase 2: Secundarios después (con delay)
                    requestIdleCallback(() => {
                        batchFetch(secondaryServices);
                    }, { timeout: 3000 });
                    
                    prefetched = true;
                } catch (e) {
                    console.warn('Error prefetching services:', e);
                } finally {
                    prefetching = false;
                }
            }
            
            // Solo prefetch cuando el usuario muestre interés
            const serviciosBtn = document.getElementById('servicios-btn');
            if (serviciosBtn) {
                // Prefetch al hover (más probabilidad de clic)
                serviciosBtn.addEventListener('mouseenter', prefetchServices, { once: true });
                
                // Garantizar carga al click
                serviciosBtn.addEventListener('click', () => {
                    if (!prefetched && !prefetching) {
                        prefetchServices();
                    }
                }, { once: true });
            }
        })();
    </script>

    <!-- ⚠️ Scripts innecesarios ELIMINADOS (ahorro de ~450KB + 16 peticiones HTTP):
         jquery-migrate, jquery-ui, colors, slicknav, owl-carousel, 
         magnific-popup, fancybox, waypoints, finalcountdown, nicesellect,
         flex-slider, scrollup, onepage-nav, easing, active -->
    
    <!-- Scripts UX -->
    <script src="js/ux-improvements.js" defer></script>
    <script src="js/corte-automatico.js" defer></script>

</body>

</html>