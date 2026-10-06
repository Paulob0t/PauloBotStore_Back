<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Carrito de Compras - VendingBox</title>
    <link rel="icon" type="image/png" href="images/favicon.png">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&family=Poppins:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    
    <!-- Custom Styles -->
    <link rel="stylesheet" href="css/cart-vendingbox.css">
    <link rel="stylesheet" href="css/cart-premium.css">
</head>

<body class="cart-page">
    <!-- Header VendingBox -->
    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container-fluid px-4">
            <a class="navbar-brand" href="index.php">VendingBox</a>
            <a class="back-nav-link" href="javascript:history.back()">
                <i class="bi bi-arrow-left"></i> Seguir comprando
            </a>
        </div>
    </nav>

    <!-- Hero Section Compacto -->
    <section class="hero-section py-3 position-relative overflow-hidden" style="background: linear-gradient(135deg, #0a0a0a 0%, #1a1a1a 100%); border-bottom: 3px solid #F6DA01;">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-7">
                    <div class="d-flex align-items-center gap-3">
                        <div class="d-flex align-items-center justify-content-center" style="width: 60px; height: 60px; background: linear-gradient(135deg, #F6DA01 0%, #ffe44d 100%); border-radius: 50%; flex-shrink: 0;">
                            <i class="bi bi-cart-check-fill" style="font-size: 1.8rem; color: #0a0a0a;"></i>
                        </div>
                        <div>
                            <h1 class="mb-1" style="font-family: 'Outfit', sans-serif; font-weight: 900; font-size: 1.8rem; color: #F6DA01; text-transform: uppercase; letter-spacing: 1px;">Carrito de Compras</h1>
                            <p class="mb-0 text-white-50" style="font-size: 0.85rem;">Revisa tus productos y completa tu compra</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5 text-lg-end mt-3 mt-lg-0">
                    <div class="d-inline-flex align-items-center gap-3 px-3 py-2" style="background: rgba(246,218,1,0.15); border: 2px solid rgba(246,218,1,0.3); border-radius: 18px;">
                        <div>
                            <div class="text-white-50 small">Productos:</div>
                            <strong id="hero-cart-count" class="d-block" style="font-family: 'Outfit', sans-serif; font-size: 1rem; color: #F6DA01;">0 items</strong>
                        </div>
                        <div style="width: 2px; height: 35px; background: rgba(246,218,1,0.2);"></div>
                        <div>
                            <div class="text-white-50 small">Total:</div>
                            <strong id="hero-cart-total" style="font-family: 'Outfit', sans-serif; font-size: 1.2rem; color: #F6DA01;">$0.00</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <section class="cart-content py-3 bg-light" style="background: #0a0a0a !important; min-height: 60vh;">
        <div class="container">
            <div class="row g-3">
                <!-- Lista de Productos Horizontal -->
                <div class="col-lg-8">
                    <!-- Grid de productos en LISTA HORIZONTAL con SPACING OPTIMIZADO -->
                    <div id="cart-products-grid" class="d-flex flex-column gap-3">
                        <!-- Los productos se renderizan aquí dinámicamente -->
                    </div>

                    <!-- Tabla oculta para compatibilidad con JS existente -->
                    <div class="d-none">
                        <table class="table shopping-summery">
                            <tbody></tbody>
                        </table>
                    </div>
                    
                    <!-- Empty State Mejorado -->
                    <div class="empty-cart text-center py-5 d-none">
                        <div class="mb-4">
                            <div class="d-inline-flex align-items-center justify-content-center mb-4" style="width: 150px; height: 150px; background: linear-gradient(135deg, rgba(246,218,1,0.1) 0%, rgba(246,218,1,0.02) 100%); border-radius: 50%; position: relative;">
                                <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; border: 3px dashed rgba(246,218,1,0.3); border-radius: 50%; animation: rotate 20s linear infinite;"></div>
                                <i class="bi bi-cart3" style="font-size: 4.5rem; color: rgba(246,218,1,0.5);"></i>
                            </div>
                        </div>
                        <h2 class="mb-3" style="font-family: 'Outfit', sans-serif; font-weight: 900; color: #F6DA01; font-size: 2.5rem;">¡Tu carrito está vacío!</h2>
                        <p class="text-white-50 mb-5" style="font-size: 1.1rem; max-width: 500px; margin: 0 auto;">Explora nuestros productos y encuentra lo que necesitas. ¡Tenemos ofertas increíbles esperándote!</p>
                        
                        <a href="index.php" class="btn btn-lg px-5 py-3 shadow-lg" style="background: linear-gradient(135deg, #F6DA01 0%, #ffe44d 100%); color: #0a0a0a; border: none; border-radius: 50px; font-family: 'Outfit', sans-serif; font-weight: 900; letter-spacing: 1px; text-decoration: none; font-size: 1.1rem; transition: all 0.3s ease;">
                            <i class="bi bi-shop me-2"></i>Ir a la Tienda
                        </a>
                        
                        <!-- Características destacadas -->
                        <div class="row g-4 mt-5 justify-content-center">
                            <div class="col-md-4">
                                <div class="p-4 rounded-4" style="background: rgba(246,218,1,0.08); border: 2px solid rgba(246,218,1,0.15);">
                                    <div class="mb-3">
                                        <i class="bi bi-lightning-charge-fill" style="font-size: 3rem; color: #F6DA01;"></i>
                                    </div>
                                    <h5 class="fw-bold text-white mb-2" style="font-family: 'Outfit', sans-serif;">Entrega Instantánea</h5>
                                    <p class="text-white-50 small mb-0">Recibe tu producto al momento</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-4 rounded-4" style="background: rgba(246,218,1,0.08); border: 2px solid rgba(246,218,1,0.15);">
                                    <div class="mb-3">
                                        <i class="bi bi-shield-check" style="font-size: 3rem; color: #4ade80;"></i>
                                    </div>
                                    <h5 class="fw-bold text-white mb-2" style="font-family: 'Outfit', sans-serif;">100% Seguro</h5>
                                    <p class="text-white-50 small mb-0">Transacciones protegidas</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-4 rounded-4" style="background: rgba(246,218,1,0.08); border: 2px solid rgba(246,218,1,0.15);">
                                    <div class="mb-3">
                                        <i class="bi bi-percent" style="font-size: 3rem; color: #F6DA01;"></i>
                                    </div>
                                    <h5 class="fw-bold text-white mb-2" style="font-family: 'Outfit', sans-serif;">Mejores Precios</h5>
                                    <p class="text-white-50 small mb-0">Ofertas todo el año</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Resumen del Pedido Mejorado -->
                <div class="col-lg-4">
                    <div class="sticky-top" style="top: 80px;">
                        <!-- Card Principal de Resumen -->
                        <div class="card shadow-sm border-0 mb-3" style="border-radius: 18px; overflow: hidden;">
                            <div class="card-header bg-dark text-white py-2 px-3" style="background: linear-gradient(135deg, #0a0a0a 0%, #1a1a1a 100%) !important;">
                                <h5 class="mb-0 fw-bold" style="font-family: 'Outfit', sans-serif; letter-spacing: 0.5px; font-size: 1.1rem;">
                                    <i class="bi bi-receipt me-2 text-warning"></i>Resumen del Pedido
                                </h5>
                            </div>
                            <div class="card-body p-3" style="background: #1a1a1a;">
                                <!-- Totales con mejor diseño -->
                                <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom" style="border-color: rgba(246,218,1,0.15) !important;">
                                    <span class="text-muted" style="font-size: 0.9rem;">
                                        <i class="bi bi-basket3 me-1"></i>Subtotal
                                    </span>
                                    <strong id="cart-subtotal" style="font-family: 'Outfit', sans-serif; font-size: 1rem; color: #fff;">$0.00</strong>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom" style="border-color: rgba(246,218,1,0.15) !important;">
                                    <span class="text-success" style="font-size: 0.9rem;">
                                        <i class="bi bi-tag-fill me-1"></i>Descuentos
                                    </span>
                                    <strong class="text-success" id="cart-savings" style="font-family: 'Outfit', sans-serif; font-size: 1rem;">$0.00</strong>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom" style="border-color: rgba(246,218,1,0.4) !important; border-width: 2px !important;">
                                    <span class="fw-bold" style="font-size: 1rem; color: #fff;">Total a Pagar</span>
                                    <span id="cart-total" style="font-family: 'Outfit', sans-serif; font-weight: 900; font-size: 1.8rem; color: #F6DA01; text-shadow: 0 0 15px rgba(246,218,1,0.3);">$0.00</span>
                                </div>
                                
                                <!-- Calculadora de Efectivo con diseño mejorado -->
                                <div class="mb-3 p-2 rounded-3" style="background: rgba(246,218,1,0.05); border: 1px solid rgba(246,218,1,0.2);">
                                    <input type="number" id="cash-input-amount" class="d-none" step="0.01" min="0">
                                    
                                    <div class="d-flex justify-content-between align-items-center mb-2 pb-2" style="border-bottom: 1px dashed rgba(255,255,255,0.1);">
                                        <span id="cash-received-label" style="font-size: 0.85rem; color: rgba(255,255,255,0.6);">
                                            <i class="bi bi-wallet2 me-1"></i>Dinero ingresado
                                        </span>
                                        <strong id="cash-received-display" class="text-muted" style="font-family: 'Outfit', sans-serif; font-size: 0.95rem;">$0.00</strong>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span id="cash-difference-label" style="font-size: 0.85rem; color: rgba(255,255,255,0.6);">
                                            <i class="bi bi-arrow-return-left me-1"></i>Cambio
                                        </span>
                                        <strong id="cash-difference-amount" style="font-family: 'Outfit', sans-serif; font-size: 1.1rem;">$0.00</strong>
                                    </div>
                                </div>
                                
                                <!-- Botones de Pago con mejor diseño -->
                                <div class="d-grid gap-2">
                                    <button class="btn btn-lg fw-bold shadow-sm position-relative overflow-hidden" data-payment="card" style="background: #F6DA01; color: #0a0a0a; border: none; border-radius: 50px; padding: 14px; font-family: 'Outfit', sans-serif; letter-spacing: 1px; transition: all 0.3s ease; font-size: 1rem;">
                                        <i class="bi bi-credit-card me-2"></i>Pagar con Tarjeta
                                    </button>
                                    <button class="btn btn-lg fw-bold shadow-sm position-relative" data-payment="cash" style="background: transparent; color: #F6DA01; border: 2px solid rgba(246,218,1,0.4); border-radius: 50px; padding: 12px; font-family: 'Outfit', sans-serif; letter-spacing: 1px; transition: all 0.3s ease; font-size: 1rem;">
                                        <i class="bi bi-wallet2 me-2"></i>Pagar en Efectivo
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Card de Seguridad -->
                        <div class="card shadow-sm border-0" style="border-radius: 18px; overflow: hidden; background: #1a1a1a; border: 1px solid rgba(246,218,1,0.2) !important;">
                            <div class="card-body p-3">
                                <h6 class="mb-2 fw-bold text-white" style="font-family: 'Outfit', sans-serif; font-size: 1rem;">
                                    <i class="bi bi-shield-check text-success me-2"></i>Garantía VendingBox
                                </h6>
                                <div class="d-flex align-items-start mb-2">
                                    <i class="bi bi-check-circle-fill text-success me-2 mt-1" style="font-size: 1.1rem;"></i>
                                    <div>
                                        <strong class="d-block text-white mb-1" style="font-size: 0.85rem;">Compra 100% Segura</strong>
                                        <small class="text-muted" style="font-size: 0.75rem;">Transacciones protegidas</small>
                                    </div>
                                </div>
                                <div class="d-flex align-items-start mb-2">
                                    <i class="bi bi-lightning-charge-fill text-warning me-2 mt-1" style="font-size: 1.1rem;"></i>
                                    <div>
                                        <strong class="d-block text-white mb-1" style="font-size: 0.85rem;">Entrega Instantánea</strong>
                                        <small class="text-muted" style="font-size: 0.75rem;">Recibe tu producto al instante</small>
                                    </div>
                                </div>
                                <div class="d-flex align-items-start">
                                    <i class="bi bi-headset text-info me-2 mt-1" style="font-size: 1.1rem;"></i>
                                    <div>
                                        <strong class="d-block text-white mb-1" style="font-size: 0.85rem;">Soporte 24/7</strong>
                                        <small class="text-muted" style="font-size: 0.75rem;">Te ayudamos cuando lo necesites</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>



    <!-- jQuery desde CDN -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    
    <!-- Bootstrap 5.3 Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Mercado Pago SDK -->
    <script src="https://sdk.mercadopago.com/js/v2"></script>
    
    <!-- Carrito Module -->
    <script src="qz-tray.js" defer></script>
    <script src="js/modulos/print-ticket.js" defer></script>
    <script src="js/modulos/carrito.js"></script>
    
    <script>
        // 🎨 FUNCIÓN PARA RENDERIZAR PRODUCTOS CON DISEÑO PREMIUM (SCOPE GLOBAL)
        window.renderProductCards = function() {
                const grid = document.getElementById('cart-products-grid');
                const emptyCart = document.querySelector('.empty-cart');
                const heroCount = document.getElementById('hero-cart-count');
                const heroTotal = document.getElementById('hero-cart-total');
                
                if (typeof Cart === 'undefined' || typeof Cart.getItems !== 'function') {
                    console.warn('⚠️ Cart no disponible, mostrando empty state');
                    if (emptyCart) {
                        emptyCart.classList.remove('d-none');
                    }
                    if (heroCount) heroCount.textContent = '0 items';
                    if (heroTotal) heroTotal.textContent = '$0.00';
                    return;
                }
                
                if (!grid) {
                    console.error('❌ No se encontró el grid de productos');
                    return;
                }
                
                const cart = Cart.getItems();
                
                console.log('🎨 Renderizando carrito con', cart.length, 'productos');
                console.log('📦 Items del carrito:', cart);
                
                if (cart.length === 0) {
                    // Mostrar empty state
                    grid.innerHTML = '';
                    if (emptyCart) {
                        emptyCart.classList.remove('d-none');
                        emptyCart.classList.add('animate__animated', 'animate__fadeIn');
                    }
                    
                    // Actualizar hero
                    if (heroCount) heroCount.textContent = '0 productos';
                    if (heroTotal) heroTotal.textContent = '$0.00';
                    
                    console.log('📭 Carrito vacío - mostrando empty state');
                    return;
                }
                
                // Ocultar empty state
                if (emptyCart) {
                    emptyCart.classList.add('d-none');
                }
                
                // Calcular total para hero
                let totalGeneral = 0;
                cart.forEach(item => {
                    const precioOriginal = parseFloat(item.precio) || 0;
                    const descuento = item.descuento ? parseFloat(item.descuento) : 0;
                    const qty = parseInt(item.quantity, 10) || 0;
                    const precioUnit = precioOriginal - descuento;
                    totalGeneral += precioUnit * qty;
                });
                
                const itemCount = (typeof Cart !== 'undefined' && Cart.getItemsCount)
                    ? Cart.getItemsCount()
                    : cart.reduce((n, i) => n + (parseInt(i.quantity, 10) || 0), 0);

                if (heroCount) {
                    heroCount.textContent = itemCount === 1 ? '1 producto' : `${itemCount} productos`;
                }
                if (heroTotal) heroTotal.textContent = `$${totalGeneral.toFixed(2)}`;

                if (typeof Cart !== 'undefined' && typeof Cart.updateCartTotals === 'function') {
                    Cart.updateCartTotals();
                }
                
                // Renderizar productos en LISTA HORIZONTAL PREMIUM
                const itemsHTML = cart.map((item, index) => {
                    const precioOriginal = parseFloat(item.precio) || 0;
                    const descuentoVal = item.descuento != null ? parseFloat(item.descuento) : 0;
                    const descuento = isNaN(descuentoVal) ? 0 : descuentoVal;
                    const qty = parseInt(item.quantity, 10) || 0;
                    const precioUnit = precioOriginal - descuento;
                    const total = precioUnit * qty;
                    const tieneDescuento = descuento > 0;
                    const porcentajeDescuento = tieneDescuento ? Math.round((descuento / precioOriginal) * 100) : 0;
                    
                    return `
                        <div class="product-item-horizontal" data-product-id="${item.id_producto}" style="animation-delay: ${index * 0.08}s;">
                            <!-- Imagen (columna izquierda) -->
                            <div class="product-item-image" style="flex: 0 0 160px; background: rgba(255,255,255,0.02); padding: 12px; display: flex; align-items: center; justify-content: center;">
                                <img src="${item.imagen_principal}" alt="${item.nombre_producto}" style="max-width: 100%; max-height: 150px; object-fit: contain; border-radius: 10px;">
                            </div>
                            
                            <!-- Info del producto (columna central) -->
                            <div class="product-item-info" style="flex: 1; padding: 16px 18px; display: flex; flex-direction: column; justify-content: center; gap: 10px;">
                                <h5 class="product-item-title" style="font-family: 'Outfit', sans-serif; font-weight: 700; font-size: 1.2rem; color: #fff; margin: 0; line-height: 1.3;">${item.nombre_producto}</h5>
                                
                                <div class="product-item-price-row" style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                                    <span class="product-item-price" style="font-family: 'Outfit', sans-serif; font-weight: 900; font-size: 1.6rem; color: #F6DA01; text-shadow: 0 0 20px rgba(246,218,1,0.4);">$${precioUnit.toFixed(2)}</span>
                                    ${tieneDescuento ? `
                                        <span class="product-item-price-original" style="font-size: 1rem; color: #999; text-decoration: line-through;">$${precioOriginal.toFixed(2)}</span>
                                        <span class="product-item-discount-badge" style="background: linear-gradient(135deg, #4ade80 0%, #22c55e 100%); color: #fff; padding: 5px 12px; border-radius: 18px; font-weight: 700; font-size: 0.8rem; box-shadow: 0 4px 12px rgba(74,222,128,0.3);">
                                            <i class="bi bi-tag-fill me-1"></i>${porcentajeDescuento}% OFF - $${descuento.toFixed(2)}
                                        </span>
                                    ` : ''}
                                </div>
                            </div>
                            
                            <!-- Controles (columna derecha) -->
                            <div class="product-item-controls" style="flex: 0 0 280px; padding: 16px; display: flex; flex-direction: column; gap: 12px; border-left: 2px solid rgba(246, 218, 1, 0.2); background: linear-gradient(135deg, rgba(0,0,0,0.4) 0%, rgba(0,0,0,0.2) 100%);">
                                <!-- Cantidad -->
                                <div class="product-item-quantity-section" style="display: flex; align-items: center; justify-content: space-between; padding: 12px 14px; background: rgba(246, 218, 1, 0.1); border-radius: 12px; border: 2px solid rgba(246, 218, 1, 0.25);">
                                    <span class="product-item-quantity-label" style="font-weight: 700; color: rgba(255, 255, 255, 0.8); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.6px;">
                                        <i class="bi bi-box-seam me-1"></i>Cant.
                                    </span>
                                    <div class="product-item-quantity-controls" style="display: flex; align-items: center; gap: 12px;">
                                        <button type="button" class="product-item-qty-btn btn-qty-horizontal" data-type="minus" data-product-id="${item.id_producto}" style="width: 36px; height: 36px; border-radius: 50%; background: rgba(246, 218, 1, 0.15); border: 2px solid #F6DA01; color: #F6DA01; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; font-weight: 700; cursor: pointer; transition: all 0.2s ease;">
                                            <i class="bi bi-dash-lg"></i>
                                        </button>
                                        <span class="product-item-qty-value qty-horizontal-${item.id_producto}" style="font-family: 'Outfit', sans-serif; font-weight: 900; font-size: 1.4rem; color: #fff; min-width: 40px; text-align: center;">${qty}</span>
                                        <button type="button" class="product-item-qty-btn btn-qty-horizontal" data-type="plus" data-product-id="${item.id_producto}" style="width: 36px; height: 36px; border-radius: 50%; background: rgba(246, 218, 1, 0.15); border: 2px solid #F6DA01; color: #F6DA01; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; font-weight: 700; cursor: pointer; transition: all 0.2s ease;">
                                            <i class="bi bi-plus-lg"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Total -->
                                <div class="product-item-total-section" style="display: flex; justify-content: space-between; align-items: center; padding: 14px; background: linear-gradient(135deg, rgba(246, 218, 1, 0.18) 0%, rgba(246, 218, 1, 0.08) 100%); border-radius: 12px; border: 2px solid rgba(246, 218, 1, 0.35); box-shadow: 0 4px 15px rgba(246, 218, 1, 0.1);">
                                    <span class="product-item-total-label" style="font-weight: 800; color: rgba(255, 255, 255, 0.85); font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.6px;">
                                        <i class="bi bi-calculator me-1"></i>Total
                                    </span>
                                    <span class="product-item-total-amount total-horizontal-${item.id_producto}" style="font-family: 'Outfit', sans-serif; font-weight: 900; font-size: 1.7rem; color: #F6DA01; text-shadow: 0 2px 12px rgba(246, 218, 1, 0.4);">$${total.toFixed(2)}</span>
                                </div>
                                
                                <!-- Botón eliminar -->
                                <button type="button" class="product-item-remove btn-remove-horizontal" data-product-id="${item.id_producto}" style="width: 100%; background: rgba(255, 59, 59, 0.12); border: 2px solid rgba(255, 59, 59, 0.5); color: #ff6b6b; border-radius: 12px; padding: 12px; font-weight: 800; font-size: 0.85rem; transition: all 0.3s ease; cursor: pointer; text-transform: uppercase; letter-spacing: 0.6px;">
                                    <i class="bi bi-trash3 me-1"></i>Eliminar
                                </button>
                            </div>
                        </div>
                    `;
                }).join('');
                
                grid.innerHTML = itemsHTML;
                
                // Agregar event listeners
                attachHorizontalListeners();
            };
            
            // 🎯 EVENT LISTENERS PARA LISTA HORIZONTAL (SCOPE GLOBAL)
            window.attachHorizontalListeners = function() {
                // Botones +/-
                document.querySelectorAll('.btn-qty-horizontal').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        e.preventDefault();
                        const productId = btn.dataset.productId;
                        const type = btn.dataset.type;
                        
const cartItems = Cart.getItems();
                        const item = cartItems.find(item => 
                            String(item.id_producto) === String(productId)
                        );
                        
                        if (item) {
                            const newQuantity = type === 'plus' ? item.quantity + 1 : item.quantity - 1;
                            
                            if (newQuantity > 0) {
                                item.quantity = newQuantity;
                                Cart.saveToStorage();
                                
                                // Actualizar UI de la lista horizontal
                                const qtyDisplay = document.querySelector(`.qty-horizontal-${productId}`);
                                if (qtyDisplay) qtyDisplay.textContent = newQuantity;
                                
                                // Recalcular total del producto
                                const precioOriginal = parseFloat(item.precio) || 0;
                                const descuento = item.descuento ? parseFloat(item.descuento) : 0;
                                const precioUnit = precioOriginal - descuento;
                                const total = precioUnit * newQuantity;
                                
                                const totalDisplay = document.querySelector(`.total-horizontal-${productId}`);
                                if (totalDisplay) totalDisplay.textContent = `$${total.toFixed(2)}`;
                                
                                // Actualizar totales generales
                                if (typeof Cart !== 'undefined') {
                                    Cart.updateCartTotals();
                                    Cart.renderCartTable(); // Actualizar tabla oculta
                                }
                                
                                // Actualizar hero
                                renderProductCards();
                                
                                // Reset timer de inactividad
                                if (typeof Cart !== 'undefined') {
                                    Cart.resetInactivityTimer();
                                }
                            }
                        }
                    });
                });
                
                // Botones eliminar
                document.querySelectorAll('.btn-remove-horizontal').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        e.preventDefault();
                        const productId = btn.dataset.productId;
                        
                        Swal.fire({
                            title: '¿Eliminar producto?',
                            text: 'Se quitará completamente del carrito',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#F6DA01',
                            cancelButtonColor: '#6c757d',
                            confirmButtonText: 'Sí, eliminar',
                            cancelButtonText: 'Cancelar',
                            background: '#1a1a1a',
                            color: '#ffffff'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                // ✅ USAR MÉTODO CORRECTO DEL CART
                                Cart.removeItemCompletely(productId);
                                
                                // Re-renderizar lista horizontal
                                setTimeout(() => {
                                    renderProductCards();
                                }, 50);
                            }
                        });
                    });
                });
            };

            // ====== INTEGRACIÓN SILENCIOSA DEL MONEDERO (SIN WIDGET VISUAL) ======
            let monederoSaldoActual = 0;
            let monederoPolling = null;
            
            function obtenerSaldoMonedero() {
                fetch('monedero_api.php?action=get_saldo&_=' + Date.now())
                    .then(response => {
                        const contentType = response.headers.get('content-type');
                        if (contentType && contentType.includes('application/json')) {
                            return response.json();
                        }
                        throw new Error('API no devolvió JSON');
                    })
                    .then(data => {
                        if (data.success && data.saldo !== null && data.saldo !== undefined) {
                            const nuevoSaldo = parseFloat(data.saldo_cliente ?? data.saldo);
                            
                            if (!isNaN(nuevoSaldo) && nuevoSaldo !== monederoSaldoActual) {
                                const saldoAnterior = monederoSaldoActual;
                                const incremento = nuevoSaldo - saldoAnterior;
                                
                                // 💰 SI HUBO INCREMENTO, REGISTRAR MONEDA EN INVENTARIO
                                if (incremento > 0) {
                                    console.log(`💰 Nueva moneda detectada: $${incremento} (Saldo: ${saldoAnterior} → ${nuevoSaldo})`);
                                    registrarMonedaEnInventario(incremento);
                                }
                                
                                monederoSaldoActual = nuevoSaldo;
                                window.monederoSaldoActual = nuevoSaldo;
                                console.log('💰 Saldo monedero:', nuevoSaldo);
                                
                                // Actualizar el resumen del pedido
                                if (typeof window.updateCashDisplay === 'function') {
                                    window.updateCashDisplay(nuevoSaldo);
                                }
                            }
                        }
                    })
                    .catch(() => {
                        // Silenciar errores de polling
                    });
            }
            
            /**
             * 📝 Registra una moneda recibida en el inventario
             * @param {number} denominacion - Valor de la moneda (1, 2, 5, 10, 20, etc.)
             */
            function registrarMonedaEnInventario(denominacion) {
                // Redondear a la denominación válida más cercana
                const denomsValidas = [1, 2, 5, 10, 20, 50, 100, 200, 500];
                let denomFinal = denomsValidas.find(d => Math.abs(d - denominacion) < 0.5);
                
                if (!denomFinal) {
                    // 💡 Si es un monto grande (ej: $40), intentar descomponer en denominaciones válidas
                    console.warn(`⚠️ Denominación no válida: $${denominacion} - Descomponiendo...`);
                    
                    // Descomponer usando greedy (mayor a menor)
                    const descompuesto = [];
                    let resto = denominacion;
                    
                    for (let i = denomsValidas.length - 1; i >= 0; i--) {
                        const denom = denomsValidas[i];
                        while (resto >= denom) {
                            descompuesto.push(denom);
                            resto -= denom;
                        }
                    }
                    
                    // Si se pudo descomponer completamente
                    if (resto === 0 && descompuesto.length > 0) {
                        console.log(`✅ Descompuesto $${denominacion} en:`, descompuesto);
                        
                        // Registrar cada denominación
                        descompuesto.forEach(d => {
                            registrarMonedaIndividual(d);
                        });
                        return;
                    } else {
                        console.error(`❌ No se pudo descomponer $${denominacion}`);
                        return;
                    }
                }
                
                // Si es una denominación válida, registrar directamente
                registrarMonedaIndividual(denomFinal);
            }
            
            /**
             * 📝 Registra una moneda individual en el inventario (denominación válida)
             */
            function registrarMonedaIndividual(denominacion) {
                
                console.log(`💰 Registrando moneda individual: $${denominacion}`);
                
                // Enviar registro al backend
                fetch('monedero_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=register_coin_received&denominacion=' + denominacion
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        console.log(`✅ Moneda registrada en inventario: $${denominacion} (Total: ${data.cantidad_actual} monedas de esta denominación)`);
                        console.log(`📊 Inventario total: $${data.total_pesos}`);
                    } else {
                        console.error('❌ Error registrando moneda:', data.mensaje);
                    }
                })
                .catch(error => {
                    console.error('❌ Error en registrarMonedaEnInventario:', error);
                });
            }
            
            function iniciarPollingMonedero() {
                // Polling cada 150ms para detectar monedas insertadas
                obtenerSaldoMonedero(); // Primera verificación inmediata
                
                monederoPolling = setInterval(() => {
                    obtenerSaldoMonedero();
                }, 150);
            }

            // ====== INICIALIZACIÓN DEL CARRITO ======
            document.addEventListener('DOMContentLoaded', () => {
                console.log('🔍 Verificando carrito...');
                
                // 💰 INICIAR POLLING DEL MONEDERO (sin widget visual)
                iniciarPollingMonedero();
                
                if (typeof Cart !== 'undefined') {
                    try {
                        console.log('📦 Cargando datos del carrito...');
                        
                        // Recargar datos desde localStorage
                        Cart.loadFromStorage();
                        console.log('✅ loadFromStorage() ejecutado');
                        
                        // Inicializar carrito
                        Cart.init();
                        console.log('✅ Cart.init() ejecutado');
                        
                        requestAnimationFrame(() => {
                            const cartData = Cart.getItems();
                            const itemCount = cartData.length;
                            console.log('✅ Carrito inicializado con', itemCount, 'productos');
                            console.log('📦 Cart.getItems():', cartData);
                            
                            // 🎨 RENDERIZAR PRODUCTOS
                            renderProductCards();
                            Cart.renderCartTable();
                            Cart.updateCartTotals();
                            
                            // 🔄 Segundo intento después de 100ms por si acaso
                            setTimeout(() => {
                                const currentCount = Cart.getItems().length;
                                if (currentCount > 0) {
                                    console.log('🔄 Re-renderizando carrito (verificación)');
                                    renderProductCards();
                                }
                            }, 100);
                        });
                        
                        // 🔄 INTERCEPTAR renderCartTable para sincronizar ambas vistas
                        const originalRenderCartTable = Cart.renderCartTable;
                        Cart.renderCartTable = function() {
                            console.log('🔄 Cart.renderCartTable() llamado - sincronizando vistas');
                            originalRenderCartTable.call(this);
                            
                            // Re-renderizar productos horizontales también
                            setTimeout(() => {
                                renderProductCards();
                            }, 10);
                        };
                        
                        // 📡 LISTENER PARA CAMBIOS EN LOCALSTORAGE (desde otras pestañas/módulos)
                        window.addEventListener('storage', (e) => {
                            if (e.key === 'cart' || e.key === 'serviceCart') {
                                console.log('📡 LocalStorage cambió externamente - recargando carrito');
                                Cart.loadFromStorage();
                                renderProductCards();
                                Cart.renderCartTable();
                            }
                        });
                        
                        // 🔄 LISTENER CUSTOM PARA ACTUALIZACIÓN DE CARRITO
                        window.addEventListener('cartUpdated', () => {
                            console.log('🔄 Evento cartUpdated recibido - re-renderizando');
                            renderProductCards();
                        });
                        
                        // 🛡️ FALLBACK: Verificar cada 500ms durante 5 segundos si hay productos que no se renderizaron
                        let checksRealizados = 0;
                        const maxChecks = 10;
                        const verificacionInterval = setInterval(() => {
                            checksRealizados++;
                            
                            const itemsEnCarrito = Cart.getItems().length;
                            const itemsEnPantalla = document.querySelectorAll('.product-item-horizontal').length;
                            
                            if (itemsEnCarrito > 0 && itemsEnPantalla === 0) {
                                console.warn(`⚠️ Desincronización detectada: ${itemsEnCarrito} items en carrito pero 0 en pantalla - re-renderizando`);
                                renderProductCards();
                            }
                            
                            // Detener verificación después de max checks o cuando esté sincronizado
                            if (checksRealizados >= maxChecks || (itemsEnCarrito === itemsEnPantalla && itemsEnCarrito > 0)) {
                                clearInterval(verificacionInterval);
                                console.log(`✅ Verificación completa: ${itemsEnCarrito} items sincronizados`);
                            }
                        }, 500);
                        
                    } catch (error) {
                        console.error('⚠️ Error al cargar carrito:', error);
                        console.error('Stack:', error.stack);
                        
                        // Mostrar empty state si hay error
                        const emptyCart = document.querySelector('.empty-cart');
                        if (emptyCart) {
                            emptyCart.classList.remove('d-none');
                        }
                    }
                } else {
                    console.error('❌ Cart no está definido - verificar que carrito.js se cargó correctamente');
                }
            });

            // 💰 CALCULADORA VISUAL DE EFECTIVO (saldo del monedero en tiempo real)
            const cashInput = document.getElementById('cash-input-amount');
            let cashReceived = 0; // Variable para trackear el dinero recibido
            
            // Actualizar calculadora visual
            function updateCashCalculator() {
                const total = typeof Cart !== 'undefined' ? Cart.getTotal() : 0;
                
                // Elementos
                const receivedLabel = document.getElementById('cash-received-label');
                const receivedDisplay = document.getElementById('cash-received-display');
                const diffLabel = document.getElementById('cash-difference-label');
                const diffAmount = document.getElementById('cash-difference-amount');
                
                const dineroDisponible = cashReceived;
                
                if (dineroDisponible === 0) {
                    // No se ha ingresado nada - mostrar estado inicial
                    receivedLabel.innerHTML = '<i class="bi bi-wallet2 me-1"></i>Dinero ingresado';
                    receivedDisplay.textContent = '$0.00';
                    receivedDisplay.className = 'text-muted';
                    
                    diffLabel.innerHTML = '<i class="bi bi-exclamation-triangle me-1"></i>Te falta';
                    diffAmount.textContent = `$${total.toFixed(2)}`;
                    diffAmount.className = 'text-danger fw-bold';
                } else {
                    // Hay dinero ingresado - calcular diferencia
                    const difference = dineroDisponible - total;
                    
                    receivedLabel.innerHTML = '<i class="bi bi-cash-stack me-1"></i>Dinero ingresado';
                    receivedDisplay.textContent = `$${dineroDisponible.toFixed(2)}`;
                    receivedDisplay.className = 'text-success fw-bold';
                    
                    if (difference < 0) {
                        // Falta dinero
                        diffLabel.innerHTML = '<i class="bi bi-exclamation-triangle me-1"></i>Te falta';
                        diffAmount.textContent = `$${Math.abs(difference).toFixed(2)}`;
                        diffAmount.className = 'text-danger fw-bold';
                    } else if (difference === 0) {
                        // Exacto
                        diffLabel.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i>Cambio';
                        diffAmount.textContent = '$0.00';
                        diffAmount.className = 'text-success fw-bold';
                    } else {
                        // Hay cambio - solo mostrar el monto, sin botón extra
                        diffLabel.innerHTML = '<i class="bi bi-arrow-return-left me-1"></i>Cambio';
                        diffAmount.textContent = `$${difference.toFixed(2)}`;
                        diffAmount.className = 'text-warning fw-bold fs-5';
                        
                        // Guardar el cambio disponible para procesarlo automáticamente
                        cambioDisponible = difference;
                    }
                }
            }
            
            // Variable global para guardar el cambio
            let cambioDisponible = 0;
            
            // ====== 💰 FUNCIONES DE DISPENSAR CAMBIO INTELIGENTE ======
            
            /**
             * 🔍 Verifica si hay cambio disponible ANTES de procesar pago
             * @param {number} montoCambio - Monto de cambio requerido
             * @returns {Promise<Object>} - {disponible, desglose, mensaje}
             */
            window.verificarDisponibilidadCambio = async function(montoCambio) {
                try {
                    const response = await fetch(`monedero_api.php?action=check_change_availability&monto=${montoCambio}`);
                    const data = await response.json();
                    
                    if (data.success) {
                        return {
                            disponible: data.available,
                            desglose: data.desglose_propuesto || {},
                            totalMonedas: data.total_monedas || 0,
                            faltante: data.faltante || 0,
                            mensaje: data.mensaje,
                            inventarioActual: data.inventario_actual || {},
                            totalDisponible: data.total_disponible || 0
                        };
                    } else {
                        throw new Error(data.mensaje || 'Error al verificar cambio');
                    }
                } catch (error) {
                    console.error('❌ Error verificando cambio:', error);
                    return {
                        disponible: false,
                        mensaje: 'Error de conexión',
                        desglose: {},
                        faltante: montoCambio
                    };
                }
            };
            
            /**
             * 💵 Dispensa cambio físicamente desde el monedero
             * @param {number} montoCambio - Monto a dispensar
             * @returns {Promise<Object>} - {success, desglose, mensaje}
             */
            window.dispensarCambioFisico = async function(montoCambio) {
                try {
                    console.log('💰 Dispensando cambio físico:', montoCambio);
                    
                    const formData = new FormData();
                    formData.append('monto', montoCambio);
                    
                    const response = await fetch('monedero_api.php?action=dispensar_cambio', {
                        method: 'POST',
                        body: formData
                    });
                    
                    // 🔍 Obtener texto primero para debugging
                    const responseText = await response.text();
                    console.log('📥 Respuesta raw:', responseText.substring(0, 500));
                    
                    // Intentar parsear como JSON
                    let data;
                    try {
                        data = JSON.parse(responseText);
                    } catch (parseError) {
                        console.error('❌ Error parseando JSON:', parseError);
                        console.error('📄 Respuesta completa:', responseText);
                        throw new Error('Respuesta inválida del servidor: ' + responseText.substring(0, 200));
                    }
                    
                    if (data.success) {
                        console.log('✅ Cambio dispensado:', data);
                        if (typeof Cart !== 'undefined' && typeof Cart.restarSaldoMaquina === 'function') {
                            await Cart.restarSaldoMaquina(montoCambio);
                        } else if (typeof window.getMonederoSaldo === 'function') {
                            const restante = Math.max(0, window.getMonederoSaldo() - montoCambio);
                            if (typeof window.updateCashDisplay === 'function') {
                                window.updateCashDisplay(restante);
                            }
                        }
                        return {
                            success: true,
                            desglose: data.desglose,
                            comandos: data.comandos,
                            totalMonedas: data.total_monedas,
                            mensaje: data.mensaje,
                            inventarioActualizado: data.inventario_actualizado
                        };
                    } else {
                        console.error('❌ Error dispensando:', data);
                        return {
                            success: false,
                            mensaje: data.mensaje || 'Error desconocido',
                            errores: data.errores || []
                        };
                    }
                } catch (error) {
                    console.error('❌ Error en dispensarCambioFisico:', error);
                    return {
                        success: false,
                        mensaje: 'Error de conexión: ' + error.message
                    };
                }
            };
            
            /**
             * 📊 Muestra desglose visual del cambio
             * @param {Object} desglose - {denominacion: cantidad}
             * @returns {string} - HTML del desglose
             */
            function generarDesgloseHTML(desglose) {
                if (!desglose || Object.keys(desglose).length === 0) {
                    return '<p class="text-muted">No se requiere cambio</p>';
                }
                
                let html = '<div class="row g-2 mt-2">';
                
                // Ordenar denominaciones de mayor a menor
                const denoms = Object.keys(desglose).sort((a, b) => b - a);
                
                denoms.forEach(denom => {
                    const cantidad = desglose[denom];
                    const total = denom * cantidad;
                    
                    html += `
                        <div class="col-6">
                            <div class="p-2 rounded" style="background: rgba(246,218,1,0.1); border: 1px solid rgba(246,218,1,0.3);">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-bold" style="color: #F6DA01;">$${denom}</span>
                                    <span class="text-white">×${cantidad}</span>
                                    <span class="text-white-50 small">= $${total}</span>
                                </div>
                            </div>
                        </div>
                    `;
                });
                
                html += '</div>';
                
                const totalCambio = denoms.reduce((sum, denom) => sum + (denom * desglose[denom]), 0);
                html += `
                    <div class="mt-3 p-3 rounded" style="background: linear-gradient(135deg, rgba(246,218,1,0.2), rgba(246,218,1,0.1)); border: 2px solid rgba(246,218,1,0.4);">
                        <div class="d-flex justify-content-between align-items-center">
                            <strong style="color: #fff;">Total a dispensar:</strong>
                            <strong style="color: #F6DA01; font-size: 1.5rem;">$${totalCambio.toFixed(2)}</strong>
                        </div>
                        <small class="text-white-50 d-block mt-2">
                            <i class="bi bi-coins me-1"></i>${denoms.reduce((sum, d) => sum + desglose[d], 0)} monedas en total
                        </small>
                    </div>
                `;
                
                return html;
            }
            
            // Actualizar cuando pida el pago en efectivo
            window.updateCashDisplay = function(received) {
                cashReceived = received; // Guardar el monto recibido
                updateCashCalculator(); // Actualizar vista
            };
            
            // Resetear cuando se completa la venta
            window.resetCashDisplay = function() {
                cashReceived = 0;
                cambioDisponible = 0;
                monederoSaldoActual = 0; // Reset también el saldo del monedero
                updateCashCalculator();
            };
            
            // 💰 OBTENER SALDO ACTUAL DEL MONEDERO (para pago automático)
            window.getMonederoSaldo = function() {
                return monederoSaldoActual;
            };
            
            // ====== 🎯 OBJETO GLOBAL DE INTEGRACIÓN CON MONEDERO ======
            window.MonederoIntegration = {
                /**
                 * Verifica si hay cambio disponible y lo dispensa automáticamente
                 */
                async dispensarCambioFisico(montoCambio) {
                    console.log('💰 MonederoIntegration.dispensarCambioFisico:', montoCambio);
                    
                    if (montoCambio <= 0) {
                        return { success: true, mensaje: 'No se requiere cambio' };
                    }
                    
                    // 1️⃣ VERIFICAR DISPONIBILIDAD PRIMERO
                    const verificacion = await window.verificarDisponibilidadCambio(montoCambio);
                    
                    if (!verificacion.disponible) {
                        // 🚨 NO HAY CAMBIO DISPONIBLE
                        await Swal.fire({
                            icon: 'error',
                            title: '❌ Cambio Insuficiente',
                            html: `
                                <div class="alert alert-danger">
                                    <h5 class="mb-3">No hay suficiente cambio en el dispensador</h5>
                                    <div class="text-start">
                                        <p><strong>Cambio requerido:</strong> $${montoCambio.toFixed(2)}</p>
                                        <p><strong>Faltante:</strong> $${verificacion.faltante.toFixed(2)}</p>
                                        <p class="mb-0"><strong>Total disponible:</strong> $${verificacion.totalDisponible.toFixed(2)}</p>
                                    </div>
                                    <hr>
                                    <p class="mb-0 fw-bold">Opciones:</p>
                                    <ul class="text-start mb-0">
                                        <li>Solicitar monto exacto al cliente</li>
                                        <li>Entregar cambio manualmente</li>
                                        <li>Recargar el dispensador de cambio</li>
                                    </ul>
                                </div>
                            `,
                            confirmButtonText: 'Entendido',
                            confirmButtonColor: '#dc3545',
                            width: '600px'
                        });
                        
                        throw new Error(`Cambio insuficiente. Falta: $${verificacion.faltante.toFixed(2)}`);
                    }
                    
                    // 2️⃣ MOSTRAR DESGLOSE ANTES DE DISPENSAR
                    const confirmacion = await Swal.fire({
                        title: '💵 Desglose de Cambio',
                        html: `
                            <div class="text-start">
                                <div class="alert alert-info mb-3">
                                    <h5><i class="bi bi-info-circle me-2"></i>Se dispensará automáticamente:</h5>
                                </div>
                                ${generarDesgloseHTML(verificacion.desglose)}
                                <div class="alert alert-warning mt-3 mb-0">
                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                    <strong>¿Proceder con el dispensado automático?</strong>
                                </div>
                            </div>
                        `,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#28a745',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: '<i class="bi bi-check-circle me-2"></i>Sí, Dispensar',
                        cancelButtonText: '<i class="bi bi-x-circle me-2"></i>Cancelar',
                        width: '600px',
                        background: '#1a1a1a',
                        color: '#ffffff'
                    });
                    
                    if (!confirmacion.isConfirmed) {
                        throw new Error('Dispensado cancelado por el usuario');
                    }
                    
                    // 3️⃣ DISPENSAR FÍSICAMENTE
                    const loadingAlert = Swal.fire({
                        title: 'Dispensando cambio...',
                        html: `
                            <div class="text-center">
                                <div class="spinner-border text-warning mb-3" role="status" style="width: 3rem; height: 3rem;"></div>
                                <h4 class="text-warning mb-3">$${montoCambio.toFixed(2)}</h4>
                                <div class="progress" style="height: 25px;">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-warning" 
                                         role="progressbar" style="width: 100%">
                                        Dispensando ${verificacion.totalMonedas} monedas...
                                    </div>
                                </div>
                                <p class="text-muted mt-3 small">
                                    <i class="bi bi-hourglass-split me-2"></i>Por favor espera...
                                </p>
                            </div>
                        `,
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        background: '#1a1a1a',
                        color: '#ffffff'
                    });
                    
                    const resultado = await window.dispensarCambioFisico(montoCambio);
                    
                    Swal.close();
                    
                    if (resultado.success) {
                        // ✅ ÉXITO
                        await Swal.fire({
                            icon: 'success',
                            title: '✅ Cambio Dispensado',
                            html: `
                                <div class="alert alert-success">
                                    <h4 class="mb-3">$${montoCambio.toFixed(2)}</h4>
                                    <p class="mb-0">
                                        <i class="bi bi-check-circle-fill me-2"></i>
                                        ${verificacion.totalMonedas} monedas dispensadas correctamente
                                    </p>
                                </div>
                                ${generarDesgloseHTML(resultado.desglose)}
                            `,
                            confirmButtonText: 'Continuar',
                            confirmButtonColor: '#28a745',
                            timer: 3000,
                            timerProgressBar: true,
                            background: '#1a1a1a',
                            color: '#ffffff'
                        });
                        
                        return resultado;
                    } else {
                        // ❌ ERROR
                        throw new Error(resultado.mensaje || 'Error al dispensar cambio');
                    }
                }
            };
            
            // Botones de pago - SOLO DOS BOTONES
            document.addEventListener('click', (event) => {
                const trigger = event.target.closest('button[data-payment]');
                if (!trigger) return;
                event.preventDefault();

                const method = trigger.dataset.payment;
                
                if (typeof Cart !== 'undefined') {
                    if (method === 'card') {
                        Cart.handleCardPayment();
                    } else if (method === 'cash') {
                        Cart.handleCashPayment();
                    }
                }
            });
            
            // Actualizar contador del navbar y calculadora
            const updateNavBadge = () => {
                if (typeof Cart !== 'undefined') {
                    const count = Cart.getItemsCount();
                    const badge = document.getElementById('cart-count-inner');
                    if (badge) {
                        badge.textContent = count;
                    }
                    
                    // Actualizar calculadora
                    updateCashCalculator();
                }
            };
            
            // Actualizar inmediatamente
            updateNavBadge();
            
            // Observar cambios cada 300ms
            setInterval(updateNavBadge, 300);
    </script>
</body>

</html>