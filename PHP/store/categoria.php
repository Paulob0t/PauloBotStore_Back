<?php
// 🎯 PLANTILLA DE CATEGORÍA/SUBCATEGORÍA
// Carga rápida y optimizada para mostrar productos

// Obtener parámetros
$categoria_id = isset($_GET['id']) ? $_GET['id'] : null;
$subcategoria_id = isset($_GET['sub']) ? intval($_GET['sub']) : null;

// Validar que no estén vacíos (permitir 'all' como string)
if (empty($categoria_id) && empty($subcategoria_id)) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>VendingBox - Productos</title>
    <link rel="icon" type="image/png" href="images/favicon.png">
    
    <!-- CSS rápido -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="./css/cart-premium.css?v=2.4">
    <link rel="stylesheet" href="css/categoria.css?v=4.8">
    
    <script>
        // Configuración de carga
        window.loadConfig = {
            categoriaId: <?php 
                if ($categoria_id === null) {
                    echo 'null';
                } elseif ($categoria_id === 'all') {
                    echo "'all'";
                } else {
                    echo intval($categoria_id);
                }
            ?>,
            subcategoriaId: <?php echo $subcategoria_id ? intval($subcategoria_id) : 'null'; ?>
        };
    </script>
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
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Barra superior: regresar | categoría | carrito -->
    <nav class="category-nav-bar" id="category-nav-bar">
        <button class="back-button" id="back-button" type="button" title="Regresar" onclick="smoothNavigate('index.php')">
            <i class="fa fa-arrow-left"></i>
            <span>Regresar</span>
        </button>
        <h2 id="products-header" class="category-nav-title">Productos</h2>
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
    </nav>

    <!-- Productos sin subcategoría (arriba) -->
    <section class="products-section" id="products-display-section">
        <div id="products-container" class="featured-products-grid">
            <div style="text-align: center; padding: 40px; color: #F6DA01;">
                <i class="fa fa-spinner fa-spin" style="font-size: 3rem;"></i>
                <p style="margin-top: 15px; font-size: 1.2rem;">Cargando productos...</p>
            </div>
        </div>
    </section>

    <!-- Subcategorías (abajo) -->
    <section class="subcategories-section" id="subcategories-section" style="display: none;">
        <div class="featured-header subcategories-header-wrap">
            <h2 id="subcategories-header">Subcategorías</h2>
        </div>
        <div id="subcategories-container" class="featured-products-grid">
            <!-- Subcategorías se cargan aquí -->
        </div>
    </section>

    <!-- Templates -->
    <template id="subcategory-template">
        <div class="product-card subcategory-card">
            <div class="product-img-wrapper">
                <img class="product-img subcategory-image" src="" alt="" decoding="async">
                <div class="no-image-placeholder" style="display:none">
                    <i class="bi bi-collection"></i>
                </div>
            </div>
            <div class="product-content">
                <h3 class="product-title subcategory-title"></h3>
                <button type="button" class="add-to-cart subcategory-button">
                    <i class="bi bi-arrow-right"></i>
                    Ver productos
                </button>
            </div>
        </div>
    </template>

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

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11" defer></script>
    
    <script src="js/modulos/precio-utils.js" defer></script>
    <script src="js/modulos/carrito.js" defer></script>

    <script>
        // 🎨 Navegación suave entre páginas
        function smoothNavigate(url) {
            document.body.style.transition = 'opacity 0.3s ease-in-out';
            document.body.style.opacity = '0';
            setTimeout(() => {
                window.location.href = url;
            }, 300);
        }
        
        // Inicializar carrito
        document.addEventListener('DOMContentLoaded', () => {
            if (typeof Cart !== 'undefined') {
                Cart.init();
            }
        });

        // ⚡ CARGA RÁPIDA DE DATOS
        async function loadData() {
            const { categoriaId, subcategoriaId } = window.loadConfig;
            
            console.log('🔥 loadData() iniciado', { categoriaId, subcategoriaId });

            try {
                // CASO 1: Cargar subcategoría específica
                if (subcategoriaId) {
                    console.log('📦 Cargando subcategoría:', subcategoriaId);
                    await loadProductsBySubcategory(subcategoriaId);
                    return;
                }

                // CASO 2: Cargar TODOS los productos (categoría especial 'all')
                if (categoriaId === 'all') {
                    console.log('🛒 Cargando TODOS los productos');
                    document.getElementById('products-header').textContent = 'Todos los Productos';
                    await loadAllProducts();
                    return;
                }

                // CASO 3: Cargar categoría específica
                if (categoriaId) {
                    console.log('📂 Cargando categoría:', categoriaId);
                    // Obtener info de la categoría
                    const response = await fetch('get_categories.php');
                    const categories = await response.json();
                    console.log('✅ Categorías obtenidas:', categories.length);
                    
                    const category = categories.find(c => c.id_categoria == categoriaId);
                    console.log('📁 Categoría encontrada:', category);

                    if (!category) {
                        console.error('❌ Categoría no encontrada! Redirigiendo...');
                        smoothNavigate('index.php');
                        return;
                    }

                    // Actualizar título
                    document.getElementById('products-header').textContent = category.nombre_categoria;

                    // Si tiene subcategorías, mostrarlas
                    if (category.subcategorias && category.subcategorias.length > 0) {
                        console.log('📋 Tiene subcategorías:', category.subcategorias.length);
                        await loadSubcategories(category);
                    } else {
                        console.log('🎯 Sin subcategorías, cargando productos directamente');
                        // Cargar productos directamente
                        await loadProductsByCategory(categoriaId);
                    }
                }
            } catch (error) {
                console.error('💥 ERROR en loadData():', error);
                document.getElementById('products-container').innerHTML = 
                    '<div style="text-align: center; padding: 40px; color: #F6DA01;"><p>Error al cargar productos</p></div>';
            }
        }

        // Cargar subcategorías + productos sin subcategoría (secciones separadas)
        async function loadSubcategories(category) {
            const subsSection = document.getElementById('subcategories-section');
            const prodsSection = document.getElementById('products-display-section');
            const subsContainer = document.getElementById('subcategories-container');
            const subsHeader = document.getElementById('subcategories-header');
            const prodsHeader = document.getElementById('products-header');

            subsHeader.textContent = 'Subcategorías';
            const subsHeaderWrap = subsHeader.closest('.subcategories-header-wrap');
            if (subsHeaderWrap) {
                subsHeaderWrap.style.display = category.subcategorias.length > 0 ? 'block' : 'none';
            }

            // 1) Productos sin subcategoría → sección de arriba
            const noSubResponse = await fetch(`get_products.php?categoria=${category.id_categoria}&sin_subcategoria=1`);
            const noSubProducts = await noSubResponse.json();

            if (noSubProducts.length > 0) {
                prodsSection.style.display = 'block';
                prodsHeader.textContent = category.nombre_categoria;
                displayProducts(noSubProducts);
            } else {
                prodsSection.style.display = 'none';
            }

            // 2) Subcategorías → sección de abajo
            subsContainer.innerHTML = '';
            const template = document.getElementById('subcategory-template');

            category.subcategorias.forEach(sub => {
                const clone = template.content.cloneNode(true);
                const card = clone.querySelector('.subcategory-card');
                const img = clone.querySelector('.subcategory-image');
                const title = clone.querySelector('.subcategory-title');
                const placeholder = clone.querySelector('.no-image-placeholder');
                const btn = clone.querySelector('.subcategory-button');

                if (sub.imagen_subcategoria) {
                    img.src = sub.imagen_subcategoria;
                    img.alt = sub.nombre_subcategoria;
                    img.style.display = 'block';
                    if (placeholder) placeholder.style.display = 'none';
                } else {
                    img.style.display = 'none';
                    if (placeholder) placeholder.style.display = 'flex';
                }

                title.textContent = sub.nombre_subcategoria;

                const goToSub = (e) => {
                    e?.preventDefault?.();
                    e?.stopPropagation?.();
                    smoothNavigate(`categoria.php?sub=${sub.id_subcategoria}`);
                };

                card.addEventListener('click', goToSub);
                if (btn) btn.addEventListener('click', goToSub);

                subsContainer.appendChild(clone);
            });

            subsSection.style.display = category.subcategorias.length > 0 ? 'block' : 'none';

            // Si no hay productos sueltos, el título principal va en la barra superior
            if (noSubProducts.length === 0 && category.subcategorias.length > 0) {
                prodsHeader.textContent = category.nombre_categoria;
            }
        }

        // Cargar productos por categoría
        async function loadProductsByCategory(categoryId, noSubOnly = false) {
            console.log('🔍 loadProductsByCategory:', { categoryId, noSubOnly });
            
            const url = noSubOnly 
                ? `get_products.php?categoria=${categoryId}&sin_subcategoria=1`
                : `get_products.php?categoria=${categoryId}`;
            
            console.log('🌐 Fetching:', url);
            const response = await fetch(url);
            const products = await response.json();
            console.log('📦 Productos recibidos:', products.length, products);

            if (noSubOnly) {
                if (products.length > 0) {
                    displayProducts(products);
                }
                return;
            }

            displayProducts(products);
        }

        // Cargar productos por subcategoría
        async function loadProductsBySubcategory(subcategoryId) {
            const response = await fetch(`get_products.php?subcategoria=${subcategoryId}`);
            const products = await response.json();
            displayProducts(products);
        }

        // Cargar TODOS los productos (sin filtro)
        async function loadAllProducts() {
            const response = await fetch('get_products.php');
            const products = await response.json();
            displayProducts(products);
        }

        // Mostrar productos
        function displayProducts(products) {
            console.log('🎨 displayProducts() iniciado con', products.length, 'productos');
            
            const container = document.getElementById('products-container');
            const template = document.getElementById('product-template');
            const prodsSection = document.getElementById('products-display-section');

            // FORZAR visibilidad
            prodsSection.style.display = 'block';
            prodsSection.style.visibility = 'visible';
            prodsSection.style.opacity = '1';
            
            console.log('✅ Sección de productos visible');
            
            container.innerHTML = '';

            if (!products || products.length === 0) {
                console.warn('⚠️ No hay productos para mostrar');
                container.innerHTML = '<div style="text-align: center; padding: 40px; color: #F6DA01;"><p>No hay productos disponibles</p></div>';
                return;
            }

            console.log('🔨 Renderizando', products.length, 'productos...');
            
            products.forEach((product, index) => {
                console.log(`  - Producto ${index + 1}:`, product.nombre_producto);
                
                const clone = template.content.cloneNode(true);
                const img = clone.querySelector('.product-img');
                const title = clone.querySelector('.product-title');
                const priceContainer = clone.querySelector('.price-container');
                const addToCartBtn = clone.querySelector('.add-to-cart');

                if (img) {
                    img.src = product.imagen_principal || 'images/no-image.png';
                    img.alt = product.nombre_producto;
                }

                if (title) title.textContent = product.nombre_producto;

                const productPrice = parseFloat(product.precio) || 0;
                const productDiscount = parseFloat(product.descuento) || 0;
                if (window.VBPrecio && priceContainer) {
                    window.VBPrecio.aplicarEnContenedor(priceContainer, productPrice, productDiscount);
                } else if (priceContainer) {
                    const priceEl = priceContainer.querySelector('.product-price');
                    if (priceEl) {
                        priceEl.textContent = `$${Math.max(0, productPrice - productDiscount).toFixed(2)}`;
                    }
                }

                if (addToCartBtn) {
                    addToCartBtn.type = 'button';
                    addToCartBtn.classList.add('add-to-cart-btn');
                    addToCartBtn.dataset.productId = product.id_producto;
                    addToCartBtn.dataset.nombre = product.nombre_producto || '';
                    addToCartBtn.dataset.precio = productPrice;
                    addToCartBtn.dataset.descuento = productDiscount;
                    addToCartBtn.dataset.imagen = product.imagen_principal || 'images/no-image.png';
                    if (typeof Cart !== 'undefined' && typeof Cart.registerProduct === 'function') {
                        Cart.registerProduct(product);
                    }
                }

                container.appendChild(clone);
            });
            
            console.log('✅ Productos renderizados exitosamente!');
        }

        // Iniciar carga
        document.addEventListener('DOMContentLoaded', () => {
            loadData();
        });
    </script>
</body>
</html>
