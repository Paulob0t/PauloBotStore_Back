<?php
// ⚡ NO cargar datos aquí - dejar que JS lo haga con caché
// Esto ahorra ~200-500ms en el servidor
$productos = [];
$categorias = [];
$servicios = [];

// Cache bust automático: en Ctrl+F5 siempre jala el CSS más reciente
$cssShopV = @filemtime(__DIR__ . '/css/shop-carousel.css') ?: time();
$cssCartV = @filemtime(__DIR__ . '/css/cart-premium.css') ?: time();
$cssMonV  = @filemtime(__DIR__ . '/css/monedero.css') ?: time();
?>

<!DOCTYPE html>
<html lang="es" class="vb-ui-loading">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>VendingBox - Tienda Online</title>
    <link rel="icon" type="image/png" href="images/favicon.png">
    
    <!-- Preconnect a dominios externos PRIMERO -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    
    <!-- CSS crítico inline: evita el "flash" feo antes de cargar shop-carousel.css -->
    <style id="vb-critical-css">
        html.vb-ui-loading .categories-visual-section #categories-container,
        html.vb-ui-loading .featured-products-section #featured-products-container,
        html.vb-ui-loading .featured-products-section.services-section #services-container {
            min-height: 380px;
            opacity: 0;
            pointer-events: none;
        }
        html.vb-ui-ready .categories-visual-section #categories-container,
        html.vb-ui-ready .featured-products-section #featured-products-container,
        html.vb-ui-ready .featured-products-section.services-section #services-container {
            opacity: 1;
            transition: opacity .25s ease;
        }
        .categories-visual-section {
            background: linear-gradient(180deg, #f8f8f8 0%, #ffffff 100%);
            padding: 80px 20px 100px;
        }
        .categories-visual-section .featured-header h2,
        .featured-products-section .featured-header h2 {
            font-family: 'Outfit', 'Poppins', sans-serif;
            font-size: clamp(2.5rem, 6vw, 4rem);
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: -2px;
        }
        .category-card {
            position: relative;
            overflow: hidden;
            border-radius: 20px;
            height: 350px;
            width: 340px;
            flex-shrink: 0;
            box-shadow: 0 15px 40px rgba(0,0,0,.15);
            border-top: 5px solid #F6DA01;
            background: #fff;
            cursor: pointer;
        }
        .category-card.all-products-card {
            border-top-color: #0066ff;
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2ff 100%);
        }
        .category-image { width: 100%; height: 100%; object-fit: cover; }
        .category-overlay {
            position: absolute; left: 0; right: 0; bottom: 0;
            padding: 35px 25px 25px;
            background: linear-gradient(to top, rgba(0,0,0,.95) 0%, rgba(0,0,0,.7) 50%, transparent 100%);
            color: #fff;
        }
        .category-title {
            margin: 0 0 15px;
            font-size: 1.8rem;
            font-weight: 900;
            text-transform: uppercase;
        }
        .category-btn {
            border: none;
            border-radius: 25px;
            padding: 12px 25px;
            font-weight: 800;
            text-transform: uppercase;
            background: #fff;
            color: #0a0a0a;
        }
        .featured-products-carousel-wrapper {
            position: relative;
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 80px;
        }
        .featured-products-carousel {
            display: flex;
            gap: 30px;
            padding: 20px 0;
        }
    </style>
    
    <!-- ⚡ CSS CRÍTICOS - Bootstrap desde CDN (mejor caché) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preload" href="./css/shop-carousel.css?v=<?php echo $cssShopV; ?>" as="style">
    <link rel="stylesheet" href="./css/shop-carousel.css?v=<?php echo $cssShopV; ?>">
    <link rel="stylesheet" href="./css/cart-premium.css?v=<?php echo $cssCartV; ?>">
    <link rel="stylesheet" href="./css/monedero.css?v=<?php echo $cssMonV; ?>">
    
    <!-- ⚡ Fonts modernas para e-commerce -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&family=Poppins:wght@400;500;600;700;800;900&family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
    
    <!-- SweetAlert2 para notificaciones -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" media="print" onload="this.media='all'">
    <style>.swal2-container{z-index:2147483647!important}</style>
    
    <script>
        window.products = <?php echo !empty($productos) ? json_encode($productos, JSON_UNESCAPED_UNICODE) : '[]'; ?>;
        window.categories = <?php echo !empty($categorias) ? json_encode($categorias, JSON_UNESCAPED_UNICODE) : '[]'; ?>;
        window.servicios = [];
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
            }catch(e){}
        }();
    </script>

    <script>
        // 🚀 Servicios de ProntiPagos - Caché local con 15 min de TTL
        window.ProntiServices = {
            NAMES: 'telcel,movistar,bait,nextel,iusacell,unefon,netflix,spotify,shein,cfe,megacable,total play,izzi,amigo,att,totalplay,telmex',
            CACHE_KEY: 'servicios',
            CACHE_TTL: 15 * 60 * 1000,
            data: null,

            async load() {
                // Intentar desde caché primero
                try {
                    const cached = JSON.parse(localStorage.getItem(this.CACHE_KEY) || 'null');
                    if (cached && cached.ts && (Date.now() - cached.ts) < this.CACHE_TTL && cached.data) {
                        this.data = cached.data;
                        return this.data;
                    }
                } catch {}
                // No hay caché válido, cargar desde proxy
                try {
                    const r = await fetch(`prontipagos_proxy.php?servicio=${this.NAMES}`, {
                        headers: { 'Accept': 'application/json' },
                        cache: 'no-store'
                    });
                    if (!r.ok) throw new Error('Error al cargar servicios');
                    const data = await r.json();
                    if (!Array.isArray(data)) throw new Error('Respuesta inválida');
                    this.data = data;
                    try {
                        localStorage.setItem(this.CACHE_KEY, JSON.stringify({ ts: Date.now(), data }));
                    } catch {}
                } catch {
                    this.data = [];
                }
                return this.data;
            },

            getProducts(name) {
                if (!this.data || !Array.isArray(this.data)) return [];
                const upper = name.toUpperCase();
                return this.data.filter(p => (p.name || '').toUpperCase().includes(upper));
            },

            getServices(name) {
                return this.getProducts(name).filter(p => !(p.name || '').toUpperCase().includes('SALDO'));
            },

            getSaldo(name) {
                return this.getProducts(name).filter(p => (p.name || '').toUpperCase().includes('SALDO'));
            },

            getBySkuPrefix(name, prefix) {
                return this.getServices(name).filter(p => p.sku && p.sku.startsWith(prefix));
            }
        };

        // Iniciar carga asíncrona para poblar localStorage['servicios']
        (async () => { try { await window.ProntiServices?.load(); } catch {} })();
    </script>

    <script>
        // Evita pantalla en blanco al volver con boton "atras" (bfcache).
        (function () {
            function ensurePageVisible() {
                document.documentElement.style.opacity = '1';
                document.documentElement.style.visibility = 'visible';
                if (document.body) {
                    document.body.style.opacity = '1';
                    document.body.style.visibility = 'visible';
                }
            }

            document.addEventListener('DOMContentLoaded', ensurePageVisible);
            window.addEventListener('pageshow', ensurePageVisible);
        })();
    </script>

    <style>
        /* Animación de entrada suave */
        body {
            opacity: 1;
            animation: fadeIn 0.3s ease-in-out;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }
    </style>

</head>

<body class="js">
    <!-- Header -->
    <div class="middle-inner">
        <div class="header-container">
            <div class="row">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center py-1">
                        <div class="logo">
                            <a href="index.php">
                                <span class="logo-text">VendingBox</span>
                            </a>
                        </div>
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

    <!-- Sección de Productos Destacados (Carrusel) -->
    <section class="featured-products-section products-section">
        <div class="featured-header">
            <h2>Productos Destacados</h2>
        </div>
        <div id="featured-products-container">
            <!-- Los productos destacados se cargarán aquí dinámicamente -->
        </div>
    </section>

    <!-- Sección de Servicios (Carrusel) -->
    <section class="featured-products-section services-section">
        <div class="featured-header">
            <h2>Servicios</h2>
        </div>
        <div id="services-container">
            <!-- Los servicios se cargarán aquí dinámicamente -->
        </div>
    </section>

    <!-- Sección de Categorías -->
    <section class="categories-visual-section">
        <div class="featured-header">
            <h2 id="categories-header">Categorías</h2>
        </div>
        <div id="categories-container">
            <!-- Las categorías se cargarán aquí dinámicamente -->
        </div>
    </section>

    <!-- Contenedor para subcategorías (oculto por defecto) -->
    <section class="subcategories-section" id="subcategories-section" style="display: none;">
        <div class="featured-header" id="cats-subs-header">
            <h2 id="subcategories-header">Subcategorías</h2>
        </div>
        <div id="subcategories-container" class="featured-products-grid">
            <!-- Las subcategorías se cargarán aquí dinámicamente -->
        </div>
    </section>

    <!-- Contenedor para productos (oculto por defecto) -->
    <section class="products-section" id="products-display-section" style="display: none;">
        <div class="featured-header" id="products-header-section">
            <h2 id="products-header">Productos</h2>
        </div>
        <div id="products-container" class="featured-products-grid">
            <!-- Los productos se cargarán aquí dinámicamente -->
        </div>
    </section>

    <!-- Botón flotante para regresar -->
    <button class="back-button hidden" id="back-button" title="Regresar">
        <i class="fa fa-arrow-left"></i>
        <span>Regresar</span>
    </button>

    <!-- Templates -->
    <template id="category-template">
        <div class="category-card">
            <img class="category-image" src="" alt="" decoding="async">
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
            <img class="subcategory-image" src="" alt="" decoding="async">
            <div class="no-image-placeholder">
                <i class="bi bi-collection"></i>
            </div>
            <div class="subcategory-overlay">
                <h3 class="subcategory-title"></h3>
            </div>
        </div>
    </template>

    <!-- Template para productos -->
    <template id="product-template">
        <div class="product-card">
            <div class="product-img-wrapper">
                <img class="product-img" src="" alt="" decoding="async">
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

    <!-- Template para productos destacados (mismo diseño que product-template) -->
    <template id="featured-product-template">
        <div class="featured-product-card">
            <div class="product-img-wrapper">
                <img class="product-img" src="" alt="" decoding="async">
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

    <!-- Template para servicios -->
    <template id="service-template">
        <div class="service-card service-menu-btn">
            <div class="service-img-wrapper">
                <img class="service-img" src="" alt="" decoding="async">
            </div>
            <div class="no-image-placeholder">
                <i class="bi bi-gear-fill"></i>
            </div>
            <div class="service-overlay">
                <h3 class="service-title"></h3>
                <button type="button" class="service-btn">
                    <i class="bi bi-arrow-right"></i>
                    Ver más
                </button>
            </div>
        </div>
    </template>

    <!-- Modal para Servicios (Bootstrap) -->
    <div id="service-modal" class="service-modal-overlay" style="display:none;">
        <div class="service-modal-dialog">
            <div class="service-modal-content">
                <div class="service-modal-header">
                    <h4 id="service-modal-title">Servicio</h4>
                    <button id="service-modal-close" class="service-modal-close">&times;</button>
                </div>
                <div class="service-modal-body" id="service-modal-body">
                </div>
                <div class="service-modal-footer" id="service-modal-footer">
                    <button class="btn btn-secondary" onclick="window.ServiceModal.close()">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        window.ServiceModal = {
            open({ title = 'Servicio', bodyHTML = '', footerHTML = '' } = {}) {
                const el = document.getElementById('service-modal');
                if (!el) return;
                if (el.parentElement !== document.body) {
                    document.body.appendChild(el);
                }
                document.getElementById('service-modal-title').textContent = title;
                document.getElementById('service-modal-body').innerHTML =
                    `<div class="service-kiosk-wrapper">${bodyHTML}</div>`;
                const footer = document.getElementById('service-modal-footer');
                if (footer) footer.innerHTML = footerHTML;
                el.style.display = 'flex';
                el.scrollTop = 0;
                document.body.style.overflow = 'hidden';
            },
            close() {
                const el = document.getElementById('service-modal');
                if (!el) return;
                el.style.display = 'none';
                document.getElementById('service-modal-body').innerHTML = '';
                document.body.style.overflow = '';
            }
        };
        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('service-modal-close')?.addEventListener('click', () => window.ServiceModal.close());
            document.getElementById('service-modal')?.addEventListener('click', (e) => {
                if (e.target.id === 'service-modal') window.ServiceModal.close();
            });
        });
    </script>

    <!-- Shopping cart sidebar - OCULTO, se usa el dropdown premium -->
    <div class="shopping-cart-sidebar" id="cart-sidebar" style="display: none !important; visibility: hidden !important;">
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
    <script type="module" src="./js/modulos/service-cache.js"></script>
    <script src="js/modulos/service-payment-handler.js" defer></script>
    <script src="./js/modulos/services-menu.js"></script>
    <script src="js/modulos/precio-utils.js" defer></script>
    <script src="js/modulos/carrito.js" defer></script>
    <script src="js/modulos/categories-display.js" defer></script>

    <!-- Inicializar el carrito -->
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

    <!-- 🚀 CARGADOR OPTIMIZADO - Caché agresivo para BD en nube -->
    <script defer>
        (async function() {
            const CACHE_KEY = 'vb_all_data';
            const TTL = 30 * 60 * 1000; // 30 minutos (BD en nube es lenta)
            const BG_REFRESH_TIME = 20 * 60 * 1000; // Referencia (se mantiene para compatibilidad)
            
            // 1️⃣ Si ya cargamos del caché inline, render inmediato + refresh en background.
            if (window.cacheLoaded) {
                fetchFreshData(true);
                return; // Ya tenemos datos, no bloquear
            }
            
            // 2️⃣ No hay caché, cargar datos frescos (primera vez)
            fetchFreshData(false);
            
            async function fetchFreshData(isBackground = false) {
                try {
                    const controller = new AbortController();
                    const timeoutId = setTimeout(() => controller.abort(), 8000); // 8s timeout
                    
                    const response = await fetch(`get_all_data.php?ts=${Date.now()}`, {
                        headers: { 'Accept': 'application/json' },
                        signal: controller.signal,
                        priority: isBackground ? 'low' : 'high',
                        cache: 'no-store'
                    });
                    
                    clearTimeout(timeoutId);
                    
                    if (!response.ok) throw new Error('Network error');
                    
                    const result = await response.json();
                    
                    if (result.success && result.data) {
                        // Actualizar variables globales
                        window.categories = result.data.categories || [];
                        window.products = result.data.products || [];
                        window.featuredProducts = result.data.featured_products || [];

                        [...window.products, ...window.featuredProducts].forEach(p => {
                            if (p?.id_producto != null && window.Cart?.registerProduct) {
                                window.Cart.registerProduct(p);
                            }
                        });

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
                        
                        // Siempre disparar evento para que los módulos se actualicen
                        window.dispatchEvent(new CustomEvent('dataLoaded', { 
                            detail: result.data 
                        }));
                    }
                } catch (error) {
                    // Si falla y NO hay caché, intentar fallback
                    if (!isBackground && (!window.products || window.products.length === 0)) {
                        // Intentar APIs individuales como último recurso
                        await Promise.allSettled([
                            fetch(`get_products.php?ts=${Date.now()}`, { cache: 'no-store' }).then(r => r.json()).then(d => window.products = d),
                            fetch(`get_categories.php?ts=${Date.now()}`, { cache: 'no-store' }).then(r => r.json()).then(d => window.categories = d)
                        ]);
                    }
                }
            }
        })();
    </script>

    <!-- 🎠 MÓDULO DE CARRUSELES - Productos Destacados, Servicios y Categorías -->
    <script defer>
        /**
         * Módulo unificado para gestionar carruseles
         * Soporta: Productos Destacados, Servicios y Categorías
         */
        const CarouselManager = {
            carousels: {},

            /**
             * Inicializa un carrusel
             */
            init(config) {
                const {
                    containerId,
                    dataSource, // función o array de datos
                    renderCard, // función para renderizar cada card
                    itemsPerSlideConfig, // configuración de items por slide según ancho de pantalla
                    autoSlide = true,
                    slideInterval = 5000,
                    infinite = false,
                    infiniteSpeed = 0.55
                } = config;

                const carousel = {
                    container: document.getElementById(containerId),
                    data: [],
                    currentSlide: 0,
                    autoSlideInterval: null,
                    itemsPerSlide: 3,
                    totalSlides: 0,
                    itemsPerSlideConfig: itemsPerSlideConfig || {
                        480: 1,
                        768: 2,
                        1200: 3,
                        default: 3
                    },
                    renderCard: renderCard,
                    autoSlide: autoSlide,
                    slideInterval: slideInterval,
                    infinite: infinite,
                    infiniteSpeed: infiniteSpeed,
                    infinitePosition: 0,
                    infiniteLoopWidth: 0,
                    infiniteDuplicateSets: 2,
                    infiniteRafId: null,
                    infinitePaused: false,
                    infiniteHoverBound: false
                };

                this.carousels[containerId] = carousel;
                this.updateItemsPerSlide(containerId);
                this.loadData(containerId, dataSource);

                window.addEventListener('resize', () => this.onResize(containerId));

                return carousel;
            },

            updateItemsPerSlide(containerId) {
                const carousel = this.carousels[containerId];
                if (!carousel) return;

                const width = window.innerWidth;
                const config = carousel.itemsPerSlideConfig;
                
                let items = config.default;
                if (width <= 480) items = config[480];
                else if (width <= 768) items = config[768];
                else if (width <= 1200) items = config[1200];

                carousel.itemsPerSlide = Math.max(1, items);
            },

            async loadData(containerId, dataSource) {
                const carousel = this.carousels[containerId];
                if (!carousel) return;

                let loadingTimer = null;
                try {
                    // Solo mostrar spinner si tarda más de 150ms (evita flash feo en refresh con caché)
                    loadingTimer = setTimeout(() => this.showLoading(containerId), 150);

                    let data = [];
                    if (typeof dataSource === 'function') {
                        data = await dataSource();
                    } else if (Array.isArray(dataSource)) {
                        data = dataSource;
                    }

                    clearTimeout(loadingTimer);
                    carousel.data = data;
                    if (Array.isArray(data)) {
                        data.forEach(item => {
                            if (item && item.id_producto != null && window.Cart?.registerProduct) {
                                window.Cart.registerProduct(item);
                            } else if (item && item.id_producto != null) {
                                if (!window._productIndex) window._productIndex = {};
                                window._productIndex[String(item.id_producto)] = item;
                            }
                        });
                    }
                    this.render(containerId);
                } catch (error) {
                    clearTimeout(loadingTimer);
                    this.showError(containerId);
                }
            },

            showLoading(containerId) {
                const carousel = this.carousels[containerId];
                if (!carousel || !carousel.container) return;
                
                carousel.container.innerHTML = `
                    <div class="featured-loading">
                        <i class="fa fa-spinner fa-spin"></i>
                        <p>Cargando...</p>
                    </div>
                `;
            },

            showError(containerId) {
                const carousel = this.carousels[containerId];
                if (!carousel || !carousel.container) return;
                
                carousel.container.innerHTML = `
                    <div class="featured-loading">
                        <i class="fa fa-exclamation-circle"></i>
                        <p>Error al cargar contenido</p>
                    </div>
                `;
            },

            render(containerId) {
                const carousel = this.carousels[containerId];
                if (!carousel || !carousel.container || !carousel.data || carousel.data.length === 0) {
                    console.warn(`No hay datos para renderizar en ${containerId}`, carousel?.data);
                    // Ocultar la sección si no hay datos
                    if (carousel?.container) {
                        const section = carousel.container.closest('section');
                        if (section) {
                            section.style.display = 'none';
                        }
                    }
                    return;
                }

                // Mostrar la sección
                const section = carousel.container.closest('section');
                if (section) {
                    section.style.display = 'block';
                }

                this.stopAutoSlide(containerId);
                this.stopInfiniteScroll(containerId);
                this.updateItemsPerSlide(containerId);

                carousel.currentSlide = 0;
                const cardsHTML = carousel.data.map((item, index) => carousel.renderCard(item, index)).join('');

                if (containerId === 'categories-container' || containerId === 'featured-products-container' || containerId === 'services-container') {
                    document.documentElement.classList.add('vb-ui-ready');
                    document.documentElement.classList.remove('vb-ui-loading');
                }

                if (carousel.infinite) {
                    carousel.infiniteDuplicateSets = this.getInfiniteDuplicateSets(carousel.data.length);
                    const infiniteTrackHTML = Array(carousel.infiniteDuplicateSets).fill(cardsHTML).join('');

                    carousel.container.innerHTML = `
                        <div class="featured-products-carousel-wrapper infinite-carousel-mode">
                            <button class="featured-carousel-btn prev" data-carousel="${containerId}" aria-label="Anterior">
                                <i class="fa fa-chevron-left"></i>
                            </button>
                            <div class="featured-products-grid infinite-carousel-viewport">
                                <div class="featured-products-carousel infinite-carousel-track" id="${containerId}-carousel">
                                    ${infiniteTrackHTML}
                                </div>
                            </div>
                            <button class="featured-carousel-btn next" data-carousel="${containerId}" aria-label="Siguiente">
                                <i class="fa fa-chevron-right"></i>
                            </button>
                        </div>
                    `;

                    this.attachEventListeners(containerId);
                    this.initInfiniteCarousel(containerId);
                    return;
                }

                carousel.container.innerHTML = `
                    <div class="featured-products-carousel-wrapper">
                        <button class="featured-carousel-btn prev" data-carousel="${containerId}">
                            <i class="fa fa-chevron-left"></i>
                        </button>
                        <div class="featured-products-grid">
                            <div class="featured-products-carousel" id="${containerId}-carousel">
                                ${cardsHTML}
                            </div>
                        </div>
                        <button class="featured-carousel-btn next" data-carousel="${containerId}">
                            <i class="fa fa-chevron-right"></i>
                        </button>
                    </div>
                    <div class="featured-carousel-dots" id="${containerId}-dots"></div>
                `;

                this.attachEventListeners(containerId);
                this.initCarousel(containerId);
            },

            attachEventListeners(containerId) {
                const carousel = this.carousels[containerId];
                if (!carousel) return;

                // Eventos de botones
                const prevBtn = carousel.container.querySelector('.featured-carousel-btn.prev');
                const nextBtn = carousel.container.querySelector('.featured-carousel-btn.next');

                if (prevBtn) prevBtn.addEventListener('click', () => this.prevSlide(containerId));
                if (nextBtn) nextBtn.addEventListener('click', () => this.nextSlide(containerId));

                // Eventos de hover para pausar auto-slide (modo paginado)
                const carouselEl = document.getElementById(`${containerId}-carousel`);
                if (carouselEl && carousel.autoSlide && !carousel.infinite) {
                    carouselEl.addEventListener('mouseenter', () => this.stopAutoSlide(containerId));
                    carouselEl.addEventListener('mouseleave', () => this.startAutoSlide(containerId));
                }
            },

            initCarousel(containerId) {
                const carousel = this.carousels[containerId];
                if (!carousel) return;

                const dotsContainer = document.getElementById(`${containerId}-dots`);
                const totalSlides = Math.ceil(carousel.data.length / carousel.itemsPerSlide);
                carousel.totalSlides = totalSlides;

                // Crear dots
                if (dotsContainer) {
                    dotsContainer.innerHTML = '';
                    for (let i = 0; i < totalSlides; i++) {
                        const dot = document.createElement('button');
                        dot.className = `featured-carousel-dot ${i === 0 ? 'active' : ''}`;
                        dot.addEventListener('click', () => this.goToSlide(containerId, i));
                        dotsContainer.appendChild(dot);
                    }
                    dotsContainer.style.display = totalSlides > 1 ? 'flex' : 'none';
                }

                // Mostrar/ocultar botones
                const prevBtn = carousel.container.querySelector('.featured-carousel-btn.prev');
                const nextBtn = carousel.container.querySelector('.featured-carousel-btn.next');
                if (prevBtn) prevBtn.style.display = totalSlides > 1 ? 'flex' : 'none';
                if (nextBtn) nextBtn.style.display = totalSlides > 1 ? 'flex' : 'none';

                // Iniciar auto-slide
                if (carousel.autoSlide && totalSlides > 1) {
                    this.startAutoSlide(containerId);
                }

                requestAnimationFrame(() => this.goToSlide(containerId, 0));
            },

            goToSlide(containerId, slideIndex) {
                const carousel = this.carousels[containerId];
                if (!carousel) return;

                carousel.currentSlide = slideIndex;
                const carouselEl = document.getElementById(`${containerId}-carousel`);
                const dotsContainer = document.getElementById(`${containerId}-dots`);

                if (carouselEl) {
                    const cardWidth = carouselEl.children[0]?.offsetWidth || 320;
                    const gap = 30; // Gap actualizado a 30px
                    const offset = -(slideIndex * carousel.itemsPerSlide * (cardWidth + gap));
                    carouselEl.style.transform = `translateX(${offset}px)`;
                }

                // Actualizar dots
                if (dotsContainer) {
                    const dots = dotsContainer.querySelectorAll('.featured-carousel-dot');
                    dots.forEach((dot, index) => {
                        dot.classList.toggle('active', index === slideIndex);
                    });
                }
            },

            nextSlide(containerId) {
                const carousel = this.carousels[containerId];
                if (!carousel) return;

                if (carousel.infinite) {
                    this.nudgeInfinite(containerId, 1);
                    return;
                }

                const nextSlide = (carousel.currentSlide + 1) % carousel.totalSlides;
                this.goToSlide(containerId, nextSlide);
            },

            prevSlide(containerId) {
                const carousel = this.carousels[containerId];
                if (!carousel) return;

                if (carousel.infinite) {
                    this.nudgeInfinite(containerId, -1);
                    return;
                }

                const prevSlide = (carousel.currentSlide - 1 + carousel.totalSlides) % carousel.totalSlides;
                this.goToSlide(containerId, prevSlide);
            },

            getInfiniteDuplicateSets(itemCount) {
                if (itemCount <= 2) return 4;
                if (itemCount <= 4) return 3;
                return 2;
            },

            getCardStep(containerId) {
                const carousel = this.carousels[containerId];
                const carouselEl = document.getElementById(`${containerId}-carousel`);
                if (!carousel || !carouselEl || !carouselEl.children.length) return 370;

                const firstCard = carouselEl.children[0];
                const styles = window.getComputedStyle(carouselEl);
                const gap = parseFloat(styles.columnGap || styles.gap) || 30;
                return firstCard.offsetWidth + gap;
            },

            measureInfiniteLoop(containerId) {
                const carousel = this.carousels[containerId];
                const carouselEl = document.getElementById(`${containerId}-carousel`);
                if (!carousel || !carouselEl) return;

                const sets = carousel.infiniteDuplicateSets || 2;
                carousel.infiniteLoopWidth = carouselEl.scrollWidth / sets;

                if (carousel.infinitePosition >= carousel.infiniteLoopWidth) {
                    carousel.infinitePosition = carousel.infinitePosition % carousel.infiniteLoopWidth;
                }
            },

            applyInfiniteTransform(containerId, animate = false) {
                const carousel = this.carousels[containerId];
                const carouselEl = document.getElementById(`${containerId}-carousel`);
                if (!carousel || !carouselEl) return;

                carouselEl.style.transition = animate ? 'transform 0.5s cubic-bezier(0.4, 0, 0.2, 1)' : 'none';
                carouselEl.style.transform = `translateX(-${carousel.infinitePosition}px)`;
            },

            initInfiniteCarousel(containerId) {
                const carousel = this.carousels[containerId];
                if (!carousel) return;

                const carouselEl = document.getElementById(`${containerId}-carousel`);
                const wrapper = carousel.container.querySelector('.infinite-carousel-mode');
                if (!carouselEl) return;

                carousel.infinitePosition = 0;

                if (!carousel.infiniteHoverBound && wrapper) {
                    wrapper.addEventListener('mouseenter', () => {
                        carousel.infinitePaused = true;
                    });
                    wrapper.addEventListener('mouseleave', () => {
                        carousel.infinitePaused = false;
                    });
                    carousel.infiniteHoverBound = true;
                }

                requestAnimationFrame(() => {
                    requestAnimationFrame(() => {
                        this.measureInfiniteLoop(containerId);
                        this.applyInfiniteTransform(containerId);
                        if (carousel.autoSlide) {
                            this.startInfiniteScroll(containerId);
                        }
                    });
                });
            },

            tickInfinite(containerId) {
                const carousel = this.carousels[containerId];
                if (!carousel) return;

                if (!carousel.infinitePaused && carousel.infiniteLoopWidth > 0) {
                    carousel.infinitePosition += carousel.infiniteSpeed;
                    if (carousel.infinitePosition >= carousel.infiniteLoopWidth) {
                        carousel.infinitePosition -= carousel.infiniteLoopWidth;
                    }
                    this.applyInfiniteTransform(containerId);
                }

                carousel.infiniteRafId = requestAnimationFrame(() => this.tickInfinite(containerId));
            },

            startInfiniteScroll(containerId) {
                const carousel = this.carousels[containerId];
                if (!carousel) return;

                this.stopInfiniteScroll(containerId);
                carousel.infiniteRafId = requestAnimationFrame(() => this.tickInfinite(containerId));
            },

            stopInfiniteScroll(containerId) {
                const carousel = this.carousels[containerId];
                if (!carousel) return;

                if (carousel.infiniteRafId) {
                    cancelAnimationFrame(carousel.infiniteRafId);
                    carousel.infiniteRafId = null;
                }
            },

            nudgeInfinite(containerId, direction) {
                const carousel = this.carousels[containerId];
                if (!carousel || !carousel.infiniteLoopWidth) return;

                const step = this.getCardStep(containerId);
                carousel.infinitePaused = true;
                carousel.infinitePosition += direction * step;

                while (carousel.infinitePosition < 0) {
                    carousel.infinitePosition += carousel.infiniteLoopWidth;
                }
                while (carousel.infinitePosition >= carousel.infiniteLoopWidth) {
                    carousel.infinitePosition -= carousel.infiniteLoopWidth;
                }

                this.applyInfiniteTransform(containerId, true);

                clearTimeout(carousel.infiniteNudgeTimeout);
                carousel.infiniteNudgeTimeout = setTimeout(() => {
                    carousel.infinitePaused = false;
                }, 800);
            },

            startAutoSlide(containerId) {
                const carousel = this.carousels[containerId];
                if (!carousel || !carousel.autoSlide) return;

                this.stopAutoSlide(containerId);
                carousel.autoSlideInterval = setInterval(() => {
                    this.nextSlide(containerId);
                }, carousel.slideInterval);
            },

            stopAutoSlide(containerId) {
                const carousel = this.carousels[containerId];
                if (!carousel) return;

                if (carousel.autoSlideInterval) {
                    clearInterval(carousel.autoSlideInterval);
                    carousel.autoSlideInterval = null;
                }

                this.stopInfiniteScroll(containerId);
            },

            onResize(containerId) {
                const carousel = this.carousels[containerId];
                if (!carousel) return;

                if (carousel.infinite) {
                    this.measureInfiniteLoop(containerId);
                    this.applyInfiniteTransform(containerId);
                    if (carousel.autoSlide && !carousel.infiniteRafId) {
                        this.startInfiniteScroll(containerId);
                    }
                    return;
                }

                const oldItemsPerSlide = carousel.itemsPerSlide;
                this.updateItemsPerSlide(containerId);

                if (oldItemsPerSlide !== carousel.itemsPerSlide) {
                    this.initCarousel(containerId);
                    this.goToSlide(containerId, 0);
                }
            }
        };

        // Inicializar carruseles cuando se carguen los datos
        document.addEventListener('DOMContentLoaded', () => {
            // Esperar a que se carguen los datos
            const initCarousels = () => {
                // Carrusel de Productos Destacados
                CarouselManager.init({
                    containerId: 'featured-products-container',
                    infinite: true,
                    infiniteSpeed: 0.55,
                    dataSource: async () => {
                        // Intentar desde caché primero
                        if (window.featuredProducts && window.featuredProducts.length > 0) {
                            // Ordenar por orden_destacado si existe
                            const sorted = [...window.featuredProducts].sort((a, b) => {
                                const orderA = parseInt(a.orden_destacado) || 999;
                                const orderB = parseInt(b.orden_destacado) || 999;
                                return orderA - orderB;
                            });
                            return sorted;
                        }

                        // Si no hay en caché, cargar desde servidor
                        try {
                            const response = await fetch('get_featured_products.php');
                            const data = await response.json();
                            if (data && data.length > 0) {
                                window.featuredProducts = data;
                                // Ya vienen ordenados del servidor, pero asegurar
                                const sorted = [...data].sort((a, b) => {
                                    const orderA = parseInt(a.orden_destacado) || 999;
                                    const orderB = parseInt(b.orden_destacado) || 999;
                                    return orderA - orderB;
                                });
                                return sorted;
                            }
                        } catch (error) {
                            // Silencioso
                        }

                        // Fallback: usar productos regulares si no hay destacados
                        if (window.products && window.products.length > 0) {
                            return window.products.slice(0, 8);
                        }
                        return [];
                    },
                    renderCard: (product) => {
                        const price = parseFloat(product.precio) || 0;
                        const desc = parseFloat(product.descuento) || 0;
                        const info = (window.VBPrecio && window.VBPrecio.calcular)
                            ? window.VBPrecio.calcular(price, desc)
                            : { original: price, descuento: desc, final: Math.max(0, price - desc), tieneDescuento: desc > 0 };
                        const ordenDestacado = product.orden_destacado;

                        return `
                            <div class="featured-product-card" data-product-id="${product.id_producto}">
                                ${ordenDestacado ? `<div class="featured-badge">#${ordenDestacado}</div>` : '<div class="featured-badge">TOP</div>'}
                                <div class="product-img-wrapper">
                                    <img class="product-img" 
                                         src="${product.imagen_principal || 'images/default-product.png'}" 
                                         alt="${product.nombre_producto || 'Producto'}" 
                                         onerror="this.src='images/default-product.png'">
                                </div>
                                <div class="product-content">
                                    <h3 class="product-title">${product.nombre_producto || 'Producto sin nombre'}</h3>
                                    <div class="price-container">
                                        <span class="product-price">$${info.final.toFixed(2)}</span>
                                        ${info.tieneDescuento ? `
                                            <span class="product-original-price">$${info.original.toFixed(2)}</span>
                                            <span class="product-discount">-$${info.descuento.toFixed(2)}</span>
                                        ` : ''}
                                    </div>
                                    <button type="button" class="add-to-cart-btn"
                                        data-product-id="${product.id_producto}"
                                        data-nombre="${(product.nombre_producto || '').replace(/"/g, '&quot;')}"
                                        data-precio="${price}"
                                        data-descuento="${desc}"
                                        data-imagen="${(product.imagen_principal || 'images/default-product.png').replace(/"/g, '&quot;')}">
                                        <i class="bi bi-cart-plus"></i>
                                        Agregar
                                    </button>
                                </div>
                            </div>
                        `;
                    },
                    autoSlide: true,
                    slideInterval: 5000
                });

                // Carrusel de Categorías (mismo modo infinito que Productos Destacados)
                CarouselManager.init({
                    containerId: 'categories-container',
                    infinite: true,
                    infiniteSpeed: 0.55,
                    dataSource: async () => {
                        let categories = [];
                        if (window.categories && window.categories.length > 0) {
                            categories = window.categories;
                        } else {
                            const response = await fetch('get_categories.php');
                            categories = await response.json();
                        }
                        
                        // Agregar categoría especial "Todos los productos" al inicio
                        const allProductsCategory = {
                            id_categoria: 'all',
                            nombre_categoria: 'Todos los Productos',
                            imagen_categoria: '',
                            isSpecial: true
                        };
                        
                        return [allProductsCategory, ...categories];
                    },
                    renderCard: (category) => {
                        const isAllProducts = category.id_categoria === 'all';
                        const hasImage = category.imagen_categoria && category.imagen_categoria !== '';
                        const clickHandler = isAllProducts ? 'openAllProducts()' : `openCategory(${category.id_categoria})`;
                        
                        return `
                            <div class="category-card ${isAllProducts ? 'all-products-card' : ''}" data-category-id="${category.id_categoria}" onclick="${clickHandler}">
                                ${hasImage ? `
                                    <img class="category-image" 
                                         src="${category.imagen_categoria}" 
                                         alt="${category.nombre_categoria || 'Categoría'}" 

                                         onerror="this.parentElement.innerHTML='<div style=\\'width:100%;height:100%;background:linear-gradient(135deg,#F6DA01,#FFD700);display:flex;align-items:center;justify-content:center\\'><i class=\\'bi bi-grid\\' style=\\'font-size:4rem;color:#222\\'></i></div>'">
                                ` : `
                                    <div style="width:100%;height:100%;background:linear-gradient(135deg, ${isAllProducts ? '#00c8ff, #0066ff' : '#F6DA01, #FFD700'});display:flex;align-items:center;justify-content:center">
                                        <i class="${isAllProducts ? 'bi bi-grid-3x3-gap-fill' : 'bi bi-grid'}" style="font-size:4rem;color:#222"></i>
                                    </div>
                                `}
                                <div class="category-overlay">
                                    <h3 class="category-title">${category.nombre_categoria || 'Categoría'}</h3>
                                    <button class="category-btn">
                                        <i class="bi bi-arrow-right"></i>
                                        ${isAllProducts ? 'Ver Todos' : 'Ver Productos'}
                                    </button>
                                </div>
                            </div>
                        `;
                    },
                    autoSlide: true,
                    slideInterval: 6000
                });

                // Carrusel de Servicios
                ensureButtons();
                // CarouselManager.init({
                //     containerId: 'services-container',
                //     dataSource: () => {
                //         // Servicios estáticos de ejemplo
                //         return [
                //             { id: 1, nombre: 'Netflix', imagen: 'images/services/netflix.png', tipo: 'streaming' },
                //             { id: 2, nombre: 'Spotify', imagen: 'images/services/spotify.png', tipo: 'streaming' },
                //             { id: 3, nombre: 'CFE', imagen: 'images/services/cfe.png', tipo: 'servicios' },
                //             { id: 4, nombre: 'Megacable', imagen: 'images/services/megacable.png', tipo: 'servicios' },
                //             { id: 5, nombre: 'Movistar', imagen: 'images/services/movistar.png', tipo: 'recargas' }
                //         ];
                //     },
                //     renderCard: (service) => {
                //         const hasImage = service.imagen && service.imagen !== '';
                //         return `
                //             <div class="service-card" data-service-id="${service.id}" onclick="openService('${service.tipo}', ${service.id})">
                //                 <div class="service-img-wrapper">
                //                     ${hasImage ? `
                //                         <img class="service-img" 
                //                              src="${service.imagen}" 
                //                              alt="${service.nombre || 'Servicio'}" 

                //                              onerror="this.parentElement.innerHTML='<div style=\\'width:100%;height:100%;background:linear-gradient(135deg,#222,#444);display:flex;align-items:center;justify-content:center\\'><i class=\\'bi bi-gear-fill\\' style=\\'font-size:4rem;color:#F6DA01\\'></i></div>'">
                //                     ` : `
                //                         <div style="width:100%;height:100%;background:linear-gradient(135deg,#222,#444);display:flex;align-items:center;justify-content:center">
                //                             <i class="bi bi-gear-fill" style="font-size:4rem;color:#F6DA01"></i>
                //                         </div>
                //                     `}
                //                 </div>
                //                 <div class="service-overlay">
                //                     <h3 class="service-title">${service.nombre || 'Servicio'}</h3>
                //                     <button class="service-btn">
                //                         <i class="bi bi-arrow-right-circle"></i>
                //                         Ver más
                //                     </button>
                //                 </div>
                //             </div>
                //         `;
                //     },
                //     autoSlide: true,
                //     slideInterval: 7000
                // });
            };

            window.initCarousels = initCarousels;
            window.CarouselManager = CarouselManager;

            // Primero, registrar el listener para cuando se carguen los datos
            window.addEventListener('dataLoaded', (e) => {
                initCarousels();
            });

            // Si ya hay datos en caché, inicializar inmediatamente
            if (window.cacheLoaded || (window.products && window.products.length > 0)) {
                setTimeout(initCarousels, 100); // Small delay para asegurar que DOM está listo
            }

            // Fallback: Intentar inicializar después de 2 segundos si no se ha hecho
            setTimeout(() => {
                const needsInit = !document.querySelector('#featured-products-container .infinite-carousel-mode')
                    || !document.querySelector('#categories-container .infinite-carousel-mode')
                    || !document.querySelector('#services-container .infinite-carousel-mode');
                if (needsInit) {
                    initCarousels();
                }
                document.documentElement.classList.add('vb-ui-ready');
                document.documentElement.classList.remove('vb-ui-loading');
            }, 2000);
        });

        // Funciones auxiliares para interacción
        function addToCart(productId) {
            if (typeof Cart !== 'undefined' && Cart.addItem) {
                const product = window.products?.find(p => p.id_producto == productId)
                             || window.featuredProducts?.find(p => p.id_producto == productId);
                if (product) {
                    Cart.addItem(product);
                    
                    // Mostrar notificación de éxito
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Agregado!',
                            text: `${product.nombre_producto} se agregó al carrito`,
                            timer: 2000,
                            showConfirmButton: false,
                            toast: true,
                            position: 'top-end'
                        });
                    }
                }
            }
        }
        
        // Los botones .add-to-cart y .add-to-cart-btn los maneja carrito.js

        function openCategory(categoryId) {
            // 🚀 Transición suave antes de redireccionar
            document.body.style.transition = 'opacity 0.3s ease-in-out';
            document.body.style.opacity = '0';
            setTimeout(() => {
                window.location.href = `categoria.php?id=${categoryId}`;
            }, 300);
        }

        function openAllProducts() {
            // 🚀 Transición suave antes de redireccionar
            document.body.style.transition = 'opacity 0.3s ease-in-out';
            document.body.style.opacity = '0';
            setTimeout(() => {
                window.location.href = 'categoria.php?id=all';
            }, 300);
        }

        function openService(type, serviceId) {
            // Aquí implementar la lógica para abrir servicios
        }
    </script>

</body>

</html>
