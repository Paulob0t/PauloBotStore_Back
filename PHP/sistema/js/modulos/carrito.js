    let cartItems = JSON.parse(localStorage.getItem('cart')) || [];
    let serviceItems = JSON.parse(localStorage.getItem('serviceCart')) || [];

    class Cart {
        static inactivityTimeout;
        static warningShown = false;
        static paymentInProgress = false; // 🔥 Bandera para pausar timer durante pago
        static lastResetTime = 0; // 🔥 Control de throttling
        static resetThrottle = 5000; // 🔥 No reiniciar más de 1 vez cada 5 segundos
        
        static resetInactivityTimer() {
            const ahora = Date.now();
            
            // 🔥 THROTTLING: Si se llamó hace menos de 5 segundos, ignorar
            if (ahora - this.lastResetTime < this.resetThrottle) {
                return;
            }
            
            this.lastResetTime = ahora;
            clearTimeout(this.inactivityTimeout);
            
            // Si ya hay un warning mostrado, no limpiar la bandera
            if (!this.warningShown) {
                this.warningShown = false;
            }

            const hasItems = cartItems && cartItems.length > 0;
            if (!hasItems) {
                // Ensure no lingering modal when cart is empty
                if (typeof Swal !== 'undefined') {
                    Swal.close();
                }
                this.warningShown = false;
                return;
            }

            // 🔥 NO iniciar timer si hay un pago en proceso
            if (this.paymentInProgress) {
                return;
            }

            this.inactivityTimeout = setTimeout(() => {
                // Re-check before warning to avoid showing when cart was cleared meanwhile
                const stillHasItems = cartItems && cartItems.length > 0;
                if (stillHasItems && !this.warningShown && !this.paymentInProgress) {
                    this.showInactivityWarning();
                }
            }, 60000); // 1 minuto (60000 ms)
        }
        
        static showInactivityWarning() {
            // Guard: do not show if there are no items or si ya se está mostrando
            const hasItems = cartItems && cartItems.length > 0;
            if (!hasItems || this.warningShown) return;
            
            this.warningShown = true;
            
            Swal.fire({
                title: '¡Atención!',
                text: 'Los elementos del carrito se borrarán en 1 minuto por inactividad',
                icon: 'warning',
                timer: 60000, // 1 minuto
                timerProgressBar: true,
                showConfirmButton: true,
                confirmButtonText: 'Mantener carrito',
                showCancelButton: true,
                cancelButtonText: 'Borrar ahora',
                allowOutsideClick: false, // 🔥 Evitar cerrar accidentalmente
                allowEscapeKey: false
            }).then((result) => {
                this.warningShown = false; // 🔥 Limpiar bandera al cerrar
                
                const stillHasItems = cartItems && cartItems.length > 0;
                if (!stillHasItems) {
                    return;
                }
                
                if (result.dismiss === Swal.DismissReason.timer) {
                    this.clearCart();
                } else if (result.isConfirmed) {
                    this.lastResetTime = 0; // 🔥 Reset throttle para permitir reinicio inmediato
                    this.resetInactivityTimer();
                } else if (result.dismiss === Swal.DismissReason.cancel) {
                    this.clearCart();
                }
            });
        }
        
        static clearCart() {
            cartItems = [];
            this.saveCart();
            this.updateCartUI();
            this.updatePayButton(); // ✅ Deshabilitar botón cuando se vacíe el carrito
            
            Swal.fire({
                title: 'Carrito borrado',
                text: 'El carrito se ha vaciado por inactividad',
                icon: 'info',
                toast: true,
                position: 'bottom-end',
                showConfirmButton: false,
                timer: 3000
            });
        }

        static updatePayButton() {
            const checkoutBtn = document.querySelector('.shopping-item .bottom a.btn');
            const cartItems = this.getItems();
            const isEmpty = cartItems.length === 0;

            

            // MINI-CARRITO: Botón en index.php
            if (checkoutBtn) {
                // Remover listener anterior si existe
                if (checkoutBtn._clickHandler) {
                    checkoutBtn.removeEventListener('click', checkoutBtn._clickHandler);
                }

                if (isEmpty) {
                    // Carrito vacío: bloquear navegación y mostrar alerta
                    checkoutBtn.style.opacity = '0.5';
                    checkoutBtn.style.cursor = 'not-allowed';
                    checkoutBtn.innerHTML = '<i class="ti-shopping-cart"></i> Carrito Vacío';
                    
                    // Guardar href original si no existe
                    if (!checkoutBtn.dataset.originalHref) {
                        checkoutBtn.dataset.originalHref = checkoutBtn.getAttribute('href');
                    }
                    
                    // ELIMINAR href para que no navegue
                    checkoutBtn.removeAttribute('href');
                    
                    checkoutBtn._clickHandler = (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        Swal.fire({
                            icon: 'warning',
                            title: 'Carrito Vacío',
                            text: 'Agrega productos antes de continuar al pago',
                            confirmButtonColor: '#e9f71dff',
                            confirmButtonText: 'Entendido',
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: true,
                            timer: 4000
                        });
                        return false;
                    };
                    checkoutBtn.addEventListener('click', checkoutBtn._clickHandler);
                } else {
                    // Carrito con productos: permitir navegación
                    checkoutBtn.style.opacity = '1';
                    checkoutBtn.style.cursor = 'pointer';
                    checkoutBtn.innerHTML = '<i class="fa fa-credit-card"></i> Pagar';
                    
                    // RESTAURAR href original
                    if (checkoutBtn.dataset.originalHref) {
                        checkoutBtn.setAttribute('href', checkoutBtn.dataset.originalHref);
                    }
                    
                    // Remover el click handler
                    if (checkoutBtn._clickHandler) {
                        checkoutBtn.removeEventListener('click', checkoutBtn._clickHandler);
                        checkoutBtn._clickHandler = null;
                    }
                }
            } 
            // CART.PHP: Botón en página de carrito
            const cartPageBtn = document.querySelector('.button-header a[href="checkout.html"]');
            if (cartPageBtn) {
                // Remover listener anterior
                if (cartPageBtn._clickHandler) {
                    cartPageBtn.removeEventListener('click', cartPageBtn._clickHandler);
                }

                if (isEmpty) {
                    cartPageBtn.style.opacity = '0.5';
                    cartPageBtn.style.cursor = 'not-allowed';
                    
                    // Guardar y remover href
                    if (!cartPageBtn.dataset.originalHref) {
                        cartPageBtn.dataset.originalHref = cartPageBtn.getAttribute('href');
                    }
                    cartPageBtn.removeAttribute('href');
                    
                    cartPageBtn._clickHandler = (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        Swal.fire({
                            icon: 'warning',
                            title: 'Carrito Vacío',
                            text: 'Debes agregar productos antes de continuar al pago',
                            confirmButtonColor: '#F7941D',
                            confirmButtonText: 'Entendido'
                        });
                        return false;
                    };
                    cartPageBtn.addEventListener('click', cartPageBtn._clickHandler);
                } else {
                    cartPageBtn.style.opacity = '1';
                    cartPageBtn.style.cursor = 'pointer';
                    
                    // Restaurar href
                    if (cartPageBtn.dataset.originalHref) {
                        cartPageBtn.setAttribute('href', cartPageBtn.dataset.originalHref);
                    }
                    
                    if (cartPageBtn._clickHandler) {
                        cartPageBtn.removeEventListener('click', cartPageBtn._clickHandler);
                        cartPageBtn._clickHandler = null;
                    }
                }
            }
        }

        static loadFromStorage() {
            // 🔥 Recargar datos desde localStorage antes de renderizar
            cartItems = JSON.parse(localStorage.getItem('cart')) || [];
            serviceItems = JSON.parse(localStorage.getItem('serviceCart')) || [];
            console.log('📦 Carrito recargado:', cartItems.length, 'productos');
        }

        static renderCartTable() {
            const tbody = document.querySelector('.shopping-summery tbody');
            const emptyCart = document.querySelector('.empty-cart');
            const tableWrapper = document.querySelector('.card-body');
            
            // 🔥 SIEMPRE recargar desde localStorage primero
            this.loadFromStorage();
            
            // SIEMPRE usar las variables globales actualizadas, NO localStorage directamente
            const cart = cartItems || [];
            
            console.log('🎨 Renderizando tabla del carrito...');
            console.log('📦 Productos a mostrar:', cart.length);
            console.log('📋 Datos:', cart);
            
            if (!tbody) {
                console.error('❌ No se encontró el tbody de la tabla');
                return;
            }
            
            if (cart.length === 0) {
                // Mostrar empty state
                if (emptyCart) {
                    emptyCart.classList.remove('d-none');
                }
                if (tableWrapper) {
                    const table = tableWrapper.querySelector('.table-responsive');
                    if (table) table.classList.add('d-none');
                }
                tbody.innerHTML = '';
                this.updateCartTotals();
                return;
            }
            
            // Ocultar empty state y mostrar tabla
            if (emptyCart) {
                emptyCart.classList.add('d-none');
            }
            if (tableWrapper) {
                const table = tableWrapper.querySelector('.table-responsive');
                if (table) table.classList.remove('d-none');
            }
            
            const productsHTML = cart.map(item => {
                const precioOriginal = parseFloat(item.precio) || 0;
                const descuentoVal = item.descuento != null ? parseFloat(item.descuento) : 0;
                const descuento = isNaN(descuentoVal) ? 0 : descuentoVal;
                const qty = parseInt(item.quantity, 10) || 0;
                // ✅ Descuento en PESOS, no porcentaje
                const precioUnit = precioOriginal - descuento;
                const total = precioUnit * qty;
                
                return `
                    <tr data-product-row="${item.id_producto}">
                        <td class="text-center">
                            <img src="${item.imagen_principal}" alt="${item.nombre_producto}" class="img-fluid">
                        </td>
                        <td>
                            <strong>${item.nombre_producto}</strong>
                        </td>
                        <td class="text-center">
                            ${descuento > 0
                                ? `<span class="text-danger fw-bold">$${precioUnit.toFixed(2)}</span><br>
                                   <small class="text-muted text-decoration-line-through">$${precioOriginal.toFixed(2)}</small>`
                                : `<span class="fw-bold">$${precioOriginal.toFixed(2)}</span>`}
                        </td>
                        <td class="text-center">
                            <div class="quantity-controls">
                                <button type="button" class="btn-qty" data-type="minus" data-product-id="${item.id_producto}">
                                    <i class="bi bi-dash"></i>
                                </button>
                                <span class="quantity-value qty-input-${item.id_producto}">${qty}</span>
                                <button type="button" class="btn-qty" data-type="plus" data-product-id="${item.id_producto}">
                                    <i class="bi bi-plus"></i>
                                </button>
                            </div>
                        </td>
                        <td class="text-center fw-bold total-${item.id_producto}">$${total.toFixed(2)}</td>
                        <td class="text-center">
                            <button type="button" class="btn btn-remove btn-sm remove-product-all-btn" data-product-id="${item.id_producto}" title="Eliminar">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
            });
            
            tbody.innerHTML = productsHTML.join('');
            
            // IMPORTANTE: Agregar event listeners DESPUÉS de renderizar el HTML
            this.attachCartTableListeners();
            
            this.updateCartTotals();
        }
        
        static attachCartTableListeners() {

            // Event listeners para botones +/-
            document.querySelectorAll('.btn-qty').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    const productId = btn.dataset.productId;
                    const type = btn.dataset.type;
                    
                    const item = cartItems.find(item => 
                        String(item.id_producto) === String(productId)
                    );
                    
                    if (item) {
                        const newQuantity = type === 'plus' ? item.quantity + 1 : item.quantity - 1;
                        this.updateQuantity(productId, newQuantity);
                    }
                });
            });
            
            // Event listeners para botones de eliminar producto
            document.querySelectorAll('.remove-product-btn').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    const productId = btn.dataset.productId;
                    this.removeItem(productId);
                });
            });

                // Event listeners para eliminar completamente un producto
                document.querySelectorAll('.remove-product-all-btn').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        e.preventDefault();
                        const productId = btn.dataset.productId;
                        this.removeItemCompletely(productId);
                    });
                });
            
            // Event listeners para botones de eliminar servicio
            document.querySelectorAll('.remove-service-btn').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    const serviceSku = btn.dataset.serviceSku;
                    this.removeService(serviceSku);
                });
            });
            
        }

        static updateCartTotals() {
            // USAR las variables globales, NO localStorage
            const cart = cartItems || [];
            
            let subtotalProductos = 0;
            let ahorro = 0;
            
            cart.forEach(item => {
                const precioOriginal = parseFloat(item.precio) || 0;
                const descuento = item.descuento ? parseFloat(item.descuento) : 0;
                const qty = parseInt(item.quantity, 10) || 0;
                // ✅ Descuento en PESOS, no porcentaje
                const precioUnit = precioOriginal - descuento;
                
                subtotalProductos += precioUnit * qty;
                if (descuento > 0) {
                    ahorro += descuento * qty;
                }
            });
            
            const total = subtotalProductos;
            
            const subtotalEl = document.getElementById('cart-subtotal');
            if (subtotalEl) subtotalEl.textContent = `$${subtotalProductos.toFixed(2)}`;
            const savingsEl = document.getElementById('cart-savings');
            if (savingsEl) savingsEl.textContent = `$${ahorro.toFixed(2)}`;
            const totalEl = document.getElementById('cart-total');
            if (totalEl) totalEl.textContent = `$${total.toFixed(2)}`;
        }

        /** Saldo del cliente (dinero insertado para comprar) */
        static async obtenerSaldoCliente() {
            if (typeof window.getMonederoSaldo === 'function') {
                const saldoLocal = parseFloat(window.getMonederoSaldo()) || 0;
                if (saldoLocal > 0) return saldoLocal;
            }
            if (typeof MonederoIntegration !== 'undefined' && MonederoIntegration.saldoActual > 0) {
                return MonederoIntegration.saldoActual;
            }
            if (typeof window.monederoSaldoActual === 'number' && window.monederoSaldoActual > 0) {
                return window.monederoSaldoActual;
            }
            try {
                const response = await fetch('monedero_api.php?action=get_saldo&_=' + Date.now());
                const data = await response.json();
                if (data.success) {
                    return parseFloat(data.saldo_cliente ?? data.saldo) || 0;
                }
            } catch (e) {
                console.warn('No se pudo leer saldo del cliente:', e);
            }
            return 0;
        }

        /** Saldo físico de la máquina (inventario de monedas para cambio) */
        static async obtenerSaldoMaquina() {
            if (typeof MonederoIntegration !== 'undefined' && MonederoIntegration.saldoMaquina > 0) {
                return MonederoIntegration.saldoMaquina;
            }
            try {
                const response = await fetch('monedero_api.php?action=get_saldo&_=' + Date.now());
                const data = await response.json();
                if (data.success) {
                    return parseFloat(data.saldo_maquina) || 0;
                }
            } catch (e) {
                console.warn('No se pudo leer saldo de la máquina:', e);
            }
            return 0;
        }

        static async ajustarSaldoCliente(nuevoSaldo) {
            const saldo = Math.max(0, parseFloat(nuevoSaldo) || 0);
            try {
                const fd = new FormData();
                fd.append('saldo', saldo);
                await fetch('monedero_api.php?action=set_saldo', { method: 'POST', body: fd });
            } catch (e) {
                console.warn('No se pudo ajustar saldo del cliente:', e);
            }
            if (typeof MonederoIntegration !== 'undefined' && MonederoIntegration.actualizarSaldo) {
                MonederoIntegration.actualizarSaldo(saldo);
            }
            window.monederoSaldoActual = saldo;
            if (typeof window.updateCashDisplay === 'function') {
                window.updateCashDisplay(saldo);
            }
            return saldo;
        }

        static ajustarSaldoMaquina(nuevoSaldo) {
            return this.ajustarSaldoCliente(nuevoSaldo);
        }

        static async restarSaldoCliente(monto) {
            const actual = await this.obtenerSaldoCliente();
            return this.ajustarSaldoCliente(Math.max(0, actual - (parseFloat(monto) || 0)));
        }

        static restarSaldoMaquina(monto) {
            return this.restarSaldoCliente(monto);
        }

        /** Verifica que el cambio se pueda dar en monedas (no billetes) */
        static async verificarCambioEnMonedas(montoCambio) {
            if ((parseFloat(montoCambio) || 0) <= 0) {
                return { disponible: true, mensaje: 'Sin cambio' };
            }
            try {
                const response = await fetch(`monedero_api.php?action=check_change_availability&monto=${montoCambio}`);
                const data = await response.json();
                return {
                    disponible: !!data.available,
                    mensaje: data.mensaje || '',
                    faltante: data.faltante || 0,
                    desglose: data.desglose_propuesto || {},
                    cambioSoloMonedas: true
                };
            } catch (e) {
                return {
                    disponible: false,
                    mensaje: 'No se pudo verificar el cambio en monedas',
                    cambioSoloMonedas: true
                };
            }
        }

        static async mostrarCambioNoDisponible(montoCambio, info = {}) {
            const faltante = parseFloat(info.faltante) || montoCambio;
            await Swal.fire({
                icon: 'error',
                title: 'Cambio no disponible',
                html: `
                    <div class="vb-pay-breakdown">
                        <div class="vb-pay-note vb-pay-note--change">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            El cambio se entrega <strong>solo en monedas</strong>. No podemos regresar billetes.
                        </div>
                        <div class="vb-pay-row">
                            <span>Cambio requerido</span>
                            <strong>$${(parseFloat(montoCambio) || 0).toFixed(2)}</strong>
                        </div>
                        <div class="vb-pay-row">
                            <span>Falta en monedas</span>
                            <strong>$${faltante.toFixed(2)}</strong>
                        </div>
                        <p class="mb-0 mt-2" style="color:rgba(255,255,255,0.7);font-size:0.9rem;">
                            Inserta monedas, paga exacto, o usa tarjeta.
                        </p>
                    </div>
                `,
                confirmButtonText: 'Entendido',
                customClass: { popup: 'swal-vendingbox' },
                background: '#141414',
                color: '#ffffff',
                iconColor: '#F6DA01'
            });
        }

        /** Cobro efectivo: suma lo pagado al float de la máquina (el cambio se resta al dispensar) */
        static async registrarCobroEnMaquina(montoPagado, cambio = 0) {
            try {
                const fd = new FormData();
                fd.append('action', 'registrar_pago_efectivo');
                fd.append('monto_pagado', parseFloat(montoPagado) || 0);
                fd.append('cambio', parseFloat(cambio) || 0);
                const response = await fetch('monedero_api.php', { method: 'POST', body: fd });
                const data = await response.json();
                if (!data.success) {
                    console.warn('No se pudo registrar cobro en máquina:', data.mensaje);
                }
                return data;
            } catch (e) {
                console.warn('Error registrando cobro en máquina:', e);
                return { success: false };
            }
        }

        static finalizarCompraUI() {
            localStorage.removeItem('cart');
            localStorage.removeItem('serviceCart');
            cartItems = [];
            serviceItems = [];
            this.saveCart();
            this.updateCartUI();
            this.updatePayButton();
            this.updateCartTotals();
            if (typeof window.renderProductCards === 'function') {
                window.renderProductCards();
            }
            if (typeof window.resetCashDisplay === 'function') {
                window.resetCashDisplay();
            }
            window.dispatchEvent(new CustomEvent('cartUpdated'));
        }

        static mostrarRedireccionMenu(destino = 'index.php') {
            return Swal.fire({
                icon: 'success',
                title: '¡Compra exitosa!',
                html: '<p class="mb-0">Redirigiendo al menú...</p>',
                timer: 10000,
                timerProgressBar: true,
                showConfirmButton: false,
                allowOutsideClick: false,
                allowEscapeKey: false,
                customClass: { popup: 'swal-vendingbox' },
                background: '#141414',
                color: '#ffffff',
                iconColor: '#F6DA01'
            }).then(() => {
                window.location.href = destino;
            });
        }

        static async procesarVenta(metodoPago, tipoPago, tipoTarjeta = 0, opciones = {}) {
           
            
            const cart = JSON.parse(localStorage.getItem('cart')) || [];
            
            if (cart.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Carrito vacío',
                    text: 'No hay productos en el carrito para procesar.',
                    confirmButtonColor: '#F6DA01',
                    confirmButtonText: 'OK'
                });
                return;
            }
            
            Swal.fire({
                title: 'Procesando venta...',
                text: 'Por favor espere',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            try {
                
                const requestData = {
                    cart: cart,
                    metodo_pago: metodoPago,
                    tipo_pago: tipoPago,
                    tipo_tarjeta: tipoTarjeta,
                    monto_pagado: opciones.montoPagado ?? null,
                    cambio: opciones.cambio ?? 0
                };
                
                
                const response = await fetch('procesar_venta.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(requestData)
                });
                
                
                const responseText = await response.text();
                
                
                let ventaResult = null;
                try {
                    ventaResult = JSON.parse(responseText);
                } catch (parseError) {
                    
                    
                    // Redireccionar a página de error
                    window.location.href = 'pago_rechazado.php?error=' + 
                        encodeURIComponent('Error del servidor: ' + responseText.substring(0, 100)) + 
                        '&metodo=' + encodeURIComponent(metodoPago);
                    return;
                }
                
                // Verificar si hay error o falta success
                if (ventaResult.error || !ventaResult.success) {
                   
                    // Redireccionar a página de rechazo
                    window.location.href = 'pago_rechazado.php?error=' + 
                        encodeURIComponent(ventaResult.mensaje || ventaResult.error || 'Error desconocido') + 
                        '&metodo=' + encodeURIComponent(metodoPago);
                    return;
                }


                if (ventaResult.ticket_data) {
                    sessionStorage.setItem('ultimo_ticket_data', JSON.stringify(ventaResult.ticket_data));
                }

                if (typeof PrintTicket !== 'undefined' && typeof qz !== 'undefined' && ventaResult.ticket_data) {
                    try {
                        const timeoutPromise = new Promise((_, reject) =>
                            setTimeout(() => reject(new Error('Timeout impresión')), 12000)
                        );
                        await Promise.race([
                            PrintTicket.imprimirTicketVenta(ventaResult.ticket_data),
                            timeoutPromise
                        ]);
                        await new Promise(resolve => setTimeout(resolve, 500));
                    } catch (printError) {
                        console.warn('No se pudo imprimir el ticket:', printError);
                    }
                }

                Swal.close();
                this.finalizarCompraUI();

                if (opciones.usarMenuRedirect !== false) {
                    await this.mostrarRedireccionMenu(opciones.destino || 'index.php');
                } else {
                    const redirectUrl = 'pago_aprobado.php?folio=' +
                        encodeURIComponent(ventaResult.folio) +
                        '&total=' + encodeURIComponent(ventaResult.total) +
                        '&metodo=' + encodeURIComponent(ventaResult.metodo_pago);
                    window.location.href = redirectUrl;
                }
                    
            } catch (error) {
                
                
                // Redireccionar a página de error
                window.location.href = 'pago_rechazado.php?error=' + 
                    encodeURIComponent(error.message || 'Error de conexión') + 
                    '&metodo=' + encodeURIComponent(metodoPago);
            }
        }

        static handleCardPayment() {
            // 🔥 Activar bandera de pago en proceso
            this.paymentInProgress = true;
            
            // 🔥 PASO 1: Mostrar advertencia ANTES de enviar pago a la terminal
            Swal.fire({
                title: '💳 Pago con Tarjeta',
                html: `
                    <div style="text-align: center;">
                        <h4 class="mb-4" style="color: #F7931E; font-weight: bold;">
                            <i class="bi bi-credit-card-2-front me-2"></i>
                            Preparar Terminal Point
                        </h4>
                        
                        <!-- 🔥 IMAGEN REAL de la terminal Point -->
                        <div style="position: relative; display: inline-block; margin-bottom: 2rem;">
                            <img src="images/point_img.jpg" alt="Terminal Point" style="max-width: 100%; height: auto; border-radius: 15px; box-shadow: 0 10px 40px rgba(0,0,0,0.3);">
                            
                            <!-- Flecha animada apuntando a "Tarjetas" -->
                            <div style="position: absolute; left: 25%; top: 20%; transform: translate(-50%, -50%); animation: arrowBounce 1.5s ease-in-out infinite;">
                                <div style="font-size: 5rem; color: #dc3545; filter: drop-shadow(3px 3px 8px rgba(0,0,0,0.5)); text-shadow: 2px 2px 4px rgba(255,255,255,0.8);">👇</div>
                                <div style="position: absolute; top: -60px; left: 50%; transform: translateX(-50%); background: #dc3545; color: white; padding: 1rem 2rem; border-radius: 30px; font-weight: bold; font-size: 1.4rem; white-space: nowrap; box-shadow: 0 6px 20px rgba(220, 53, 69, 0.6); animation: bounceLabel 1.5s ease-in-out infinite;">
                                    ¡DA CLICK AQUÍ!
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-danger mt-3 mb-0">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <strong>⚠️ NO des click hasta que la terminal esté en "Tarjetas"</strong>
                        </div>
                    </div>
                    <style>
                        @keyframes arrowBounce {
                            0%, 100% { 
                                transform: translateY(0); 
                                opacity: 1;
                            }
                            50% { 
                                transform: translateY(8px); 
                                opacity: 0.7;
                            }
                        }
                        
                        @keyframes bounceLabel {
                            0%, 100% { 
                                transform: translateX(-50%) scale(1); 
                            }
                            50% { 
                                transform: translateX(-50%) scale(1.1); 
                            }
                        }
                    </style>
                `,
                width: '650px',
                icon: 'info',
                iconColor: '#FFD700',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '✅ Enviar Pago a Terminal',
                cancelButtonText: '<i class="bi bi-x-circle-fill me-2"></i>Cancelar',
                allowOutsideClick: false,
                customClass: {
                    popup: 'swal-wide',
                    confirmButton: 'shadow-lg',
                    cancelButton: 'shadow'
                },
                buttonsStyling: true
            }).then((result) => {
                if (result.isConfirmed) {
                    // 🔥 AHORA SÍ - Enviar pago a terminal
                    this.procesarPagoConPoint();
                } else {
                    // Usuario canceló - desactivar bandera
                    this.paymentInProgress = false;
                }
            });
        }

        static async mostrarSaldoInsuficiente(total, saldo) {
            const faltante = total - saldo;
            const result = await Swal.fire({
                icon: 'warning',
                title: 'Saldo insuficiente',
                html: `
                    <div class="vb-pay-breakdown">
                        <div class="vb-pay-row">
                            <span>Total a pagar</span>
                            <strong>$${total.toFixed(2)}</strong>
                        </div>
                        <div class="vb-pay-row">
                            <span>Ingresado</span>
                            <strong>$${saldo.toFixed(2)}</strong>
                        </div>
                        <div class="vb-pay-note vb-pay-note--change">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            Te faltan <strong>$${faltante.toFixed(2)}</strong>
                        </div>
                    </div>
                `,
                showCancelButton: true,
                showDenyButton: true,
                confirmButtonText: '<i class="bi bi-credit-card me-2"></i>Pagar con Tarjeta',
                denyButtonText: '<i class="bi bi-coin me-2"></i>Insertar más monedas',
                cancelButtonText: 'Cancelar',
                customClass: { popup: 'swal-vendingbox' },
                background: '#141414',
                color: '#ffffff',
                iconColor: '#F6DA01',
                reverseButtons: true
            });

            if (result.isConfirmed) {
                this.handleCardPayment();
            }
        }

        static async handleCashPayment() {
            const items = this.getItems();
            if (items.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Carrito vacío',
                    text: 'No hay productos en el carrito'
                });
                return;
            }

            const total = items.reduce((sum, item) => {
                const precio = parseFloat(item.precio) || 0;
                const descuento = parseFloat(item.descuento) || 0;
                const precioFinal = precio - descuento;
                return sum + (precioFinal * item.quantity);
            }, 0);

            const saldoCliente = await this.obtenerSaldoCliente();

            if (typeof window.updateCashDisplay === 'function') {
                window.updateCashDisplay(saldoCliente);
            }

            if (saldoCliente <= 0) {
                const result = await Swal.fire({
                    icon: 'info',
                    title: 'Inserta monedas',
                    html: `
                        <div class="vb-pay-breakdown">
                            <div class="vb-pay-row">
                                <span>Total a pagar</span>
                                <strong>$${total.toFixed(2)}</strong>
                            </div>
                            <p class="mb-0 mt-2" style="color:rgba(255,255,255,0.7);font-size:0.9rem;">
                                Inserta monedas o billetes en la máquina para pagar en efectivo.
                            </p>
                        </div>
                    `,
                    showCancelButton: true,
                    confirmButtonText: '<i class="bi bi-credit-card me-2"></i>Pagar con Tarjeta',
                    cancelButtonText: 'Entendido',
                    customClass: { popup: 'swal-vendingbox' },
                    background: '#141414',
                    color: '#ffffff',
                    iconColor: '#F6DA01'
                });
                if (result.isConfirmed) {
                    this.handleCardPayment();
                }
                return;
            }

            if (saldoCliente < total) {
                await this.mostrarSaldoInsuficiente(total, saldoCliente);
                return;
            }

            this.handleCashPaymentWithAmount(saldoCliente);
        }

        static async handleCashPaymentWithAmount(amountReceived) {
            const items = this.getItems();
            if (items.length === 0) return;

            const total = items.reduce((sum, item) => {
                const precio = parseFloat(item.precio) || 0;
                const descuento = parseFloat(item.descuento) || 0;
                const precioFinal = precio - descuento;
                return sum + (precioFinal * item.quantity);
            }, 0);

            const change = amountReceived - total;
            
            // Actualizar display visual antes de mostrar confirmación
            if (typeof window.updateCashDisplay === 'function') {
                window.updateCashDisplay(amountReceived);
            }
            
            // Validar que el monto sea suficiente
            if (change < 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'Monto insuficiente',
                    html: `
                        <div class="text-start">
                            <p>El dinero recibido no es suficiente.</p>
                            <div class="alert alert-danger">
                                <strong>Faltante: $${Math.abs(change).toFixed(2)}</strong>
                            </div>
                        </div>
                    `,
                    confirmButtonText: 'Entendido'
                });
                return;
            }

            if (change > 0) {
                const cambioOk = await this.verificarCambioEnMonedas(change);
                if (!cambioOk.disponible) {
                    await this.mostrarCambioNoDisponible(change, cambioOk);
                    return;
                }
            }
            
            // Mostrar desglose del cambio
            const result = await Swal.fire({
                title: 'Desglose de Pago',
                html: `
                    <div class="vb-pay-breakdown">
                        <div class="vb-pay-row">
                            <span><i class="bi bi-receipt me-2"></i>Total a pagar</span>
                            <strong>$${total.toFixed(2)}</strong>
                        </div>
                        <div class="vb-pay-row">
                            <span><i class="bi bi-cash-stack me-2"></i>Ingresado en la máquina</span>
                            <strong>$${amountReceived.toFixed(2)}</strong>
                        </div>
                        <div class="vb-pay-change-box">
                            <small>Cambio a devolver</small>
                            <div class="vb-pay-change-amount">$${change.toFixed(2)}</div>
                        </div>
                        ${change > 0 ? `
                            <div class="vb-pay-note vb-pay-note--change">
                                <i class="bi bi-coin me-2"></i>
                                Cambio en <strong>monedas únicamente</strong>: <strong>$${change.toFixed(2)}</strong>
                            </div>
                        ` : `
                            <div class="vb-pay-note vb-pay-note--exact">
                                <i class="bi bi-check-circle-fill me-2"></i>
                                Pago exacto — sin cambio
                            </div>
                        `}
                    </div>
                `,
                icon: 'success',
                iconColor: '#F6DA01',
                customClass: {
                    popup: 'swal-vendingbox',
                    confirmButton: 'swal-vb-confirm',
                    cancelButton: 'swal-vb-cancel'
                },
                confirmButtonText: '<i class="bi bi-check-circle-fill me-2"></i>Procesar Venta',
                showCancelButton: true,
                cancelButtonText: '<i class="bi bi-x-circle me-2"></i>Cancelar',
                width: '440px',
                background: '#141414',
                color: '#ffffff'
            });

            if (result.isConfirmed) {
                const opcionesVenta = {
                    montoPagado: amountReceived,
                    cambio: change,
                    usarMenuRedirect: true
                };

                await this.registrarCobroEnMaquina(amountReceived, change);
                await this.ajustarSaldoCliente(0);

                if (change > 0) {
                    await this.dispensarYProcesarVenta(change, total, opcionesVenta);
                } else {
                    this.procesarVenta('Efectivo', 2, 0, opcionesVenta);
                }
            }
        }
        
        /**
         * Dispensar cambio automáticamente y luego procesar venta
         */
        static async dispensarYProcesarVenta(cambio, total, opcionesVenta = {}) {
            console.log('💰 Procesando cambio automáticamente:', cambio);

            const opciones = {
                montoPagado: opcionesVenta.montoPagado,
                cambio: cambio,
                usarMenuRedirect: opcionesVenta.usarMenuRedirect !== false,
                ...opcionesVenta
            };
            
            try {
                if (typeof MonederoIntegration !== 'undefined' && typeof MonederoIntegration.dispensarCambioFisico === 'function') {
                    Swal.fire({
                        title: 'Procesando venta...',
                        html: `
                            <div class="text-center">
                                <div class="spinner-border text-warning mb-3" role="status"></div>
                                <p>Dispensando cambio: <strong>$${cambio.toFixed(2)}</strong></p>
                            </div>
                        `,
                        allowOutsideClick: false,
                        showConfirmButton: false
                    });
                    
                    await MonederoIntegration.dispensarCambioFisico(cambio, { silencioso: true });
                    
                    Swal.close();
                    this.procesarVenta('Efectivo', 2, 0, opciones);
                } else if (typeof window.dispensarCambioFisico === 'function') {
                    Swal.fire({
                        title: 'Dispensando cambio...',
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        didOpen: () => Swal.showLoading()
                    });
                    await window.dispensarCambioFisico(cambio);
                    Swal.close();
                    this.procesarVenta('Efectivo', 2, 0, opciones);
                } else {
                    throw new Error('Sistema de dispensado no disponible');
                }
            } catch (error) {
                console.warn('⚠️ No se pudo dispensar automáticamente:', error);
                Swal.fire({
                    icon: 'warning',
                    title: 'Entrega el cambio manualmente',
                    html: `
                        <div class="alert alert-warning">
                            <h3 class="mb-2">$${cambio.toFixed(2)}</h3>
                            <p>El dispensador no está disponible.<br>
                            <strong>Entrega este cambio al cliente</strong></p>
                        </div>
                    `,
                    confirmButtonText: 'Entendido',
                    confirmButtonColor: '#28a745',
                    timer: 5000,
                    timerProgressBar: true
                });
                this.procesarVenta('Efectivo', 2, 0, opciones);
            }
        }

        /**
         * 🔥 MERCADO PAGO POINT - Procesar pago con terminal física (DIRECTO)
         */
        static async procesarPagoConPoint() {
            const items = this.getItems();
            
            if (items.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Carrito vacío',
                    text: 'No hay productos en el carrito'
                });
                return;
            }

            // Calcular total
            const total = items.reduce((sum, item) => {
                const precio = parseFloat(item.precio) || 0;
                const descuento = parseFloat(item.descuento) || 0;
                const precioFinal = precio - descuento;
                return sum + (precioFinal * item.quantity);
            }, 0);

            // Crear descripción del pedido
            const description = items.length === 1 
                ? items[0].nombre_producto 
                : `Compra VendingBox - ${items.length} productos`;

            // Enviar DIRECTAMENTE al Point sin confirmación
            await this.enviarPagoAlPoint(total, description, items);
        }

        /**
         * Enviar pago al Point y monitorear estado
         */
        static async enviarPagoAlPoint(amount, description, items) {
            let paymentIntentId = null;
            let deviceId = null; // 🔥 Guardar device_id para cancelación
            let pollingInterval = null;

            try {
                // Paso 1: Crear payment intent
                Swal.fire({
                    title: 'Conectando con Point...',
                    html: '<div class="spinner-border text-warning mb-3" role="status"></div><p>Enviando monto a la terminal</p>',
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                const createResponse = await fetch('mercadopago_point.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'create_payment_intent',
                        amount: amount,
                        description: description,
                        external_reference: 'ORDER-' + Date.now()
                    })
                });

                const createResult = await createResponse.json();

                if (!createResult.success) {
                    throw new Error(createResult.error || 'Error al crear payment intent');
                }

                paymentIntentId = createResult.payment_intent.id;
                deviceId = createResult.payment_intent.device_id; // 🔥 GUARDAR device_id

                // Paso 2: Mostrar que está esperando el pago CON IMAGEN DE INSTRUCCIONES
                Swal.fire({
                    title: '💳 Siga las Instrucciones',
                    html: `
                        <div style="text-align: center;">
                            
                            <h3 class="text-warning mb-3">$${amount.toFixed(2)} MXN</h3>
                            <div class="alert alert-success mb-3" style="font-size: 1.1rem;">
                                <strong>✅ Pago enviado a la terminal Point</strong>
                            </div>
                            <p class="text-muted" style="font-size: 1rem;">
                                <strong>Ahora en la terminal:</strong><br>
                                1️⃣ Toca "Tarjetas"<br>
                                2️⃣ Espera que esté lista<br>
                                3️⃣ Acerca tu tarjeta
                            </p>
                            <div id="point-timer" class="mt-3">
                                <div class="progress" style="height: 25px;">
                                    <div id="timer-bar" class="progress-bar bg-warning" role="progressbar" style="width: 100%;">
                                        <span id="timer-text" style="font-weight: 600;">0:15 restantes</span>
                                    </div>
                                </div>
                                <small class="text-muted">Tiempo límite para acercar la tarjeta</small>
                            </div>
                        </div>
                        <style>
                            @keyframes pulse {
                                0%, 100% { transform: scale(1); opacity: 1; }
                                50% { transform: scale(1.1); opacity: 0.8; }
                            }
                        </style>
                    `,
                    allowOutsideClick: false,
                    showCancelButton: true,
                    showConfirmButton: false,
                    cancelButtonText: '❌ Cancelar',
                    cancelButtonColor: '#dc3545',
                    didOpen: () => {
                        // Timer visual con barra de progreso
                        let seconds = 0;
                        const maxSeconds = 15; // 🔥 15 segundos - Coincidir con maxAttempts
                        
                        const timerInterval = setInterval(() => {
                            seconds++;
                            const remaining = maxSeconds - seconds;
                            const minutes = Math.floor(remaining / 60);
                            const secs = remaining % 60;
                            const percentage = (remaining / maxSeconds) * 100;
                            
                            const timerBar = document.getElementById('timer-bar');
                            const timerText = document.getElementById('timer-text');
                            
                            if (timerBar && timerText) {
                                timerBar.style.width = percentage + '%';
                                timerText.textContent = `${minutes}:${secs.toString().padStart(2, '0')} restantes`;
                                
                                // Cambiar color según el tiempo (ajustado para 15 segundos)
                                if (remaining <= 5) {
                                    timerBar.classList.remove('bg-warning');
                                    timerBar.classList.add('bg-danger');
                                } else if (remaining <= 10) {
                                    timerBar.classList.remove('bg-warning');
                                    timerBar.classList.add('bg-warning');
                                }
                            }
                        }, 1000);

                        // Guardar para limpiar después
                        Swal.getPopup().dataset.timerInterval = timerInterval;
                    }
                });

                // Paso 3: Polling para verificar estado
                let attempts = 0;
                const maxAttempts = 30; // 🔥 30 segundos - Cancelar ANTES de que llegue a ON_TERMINAL (estado irrecuperable)

                const checkStatus = async () => {
                    try {
                        attempts++;

                        const statusResponse = await fetch('mercadopago_point.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                action: 'get_payment_status',
                                payment_intent_id: paymentIntentId
                            })
                        });

                        const statusResult = await statusResponse.json();

                        if (statusResult.success && statusResult.status) {
                            const state = statusResult.status.state;

                            if (state === 'FINISHED') {
                                // Pago completado
                                clearInterval(pollingInterval);
                                clearInterval(Swal.getPopup().dataset.timerInterval);
                                Cart.paymentInProgress = false; // 🔥 Desactivar bandera

                                const payment = statusResult.status.payment;
                                
                                Swal.fire({
                                    icon: 'success',
                                    title: '¡Pago Exitoso!',
                                    html: `
                                        <div class="alert alert-success">
                                            <h4>✅ Pago aprobado</h4>
                                            <p><strong>Monto:</strong> $${payment.amount} MXN</p>
                                            <p><strong>ID:</strong> ${payment.id}</p>
                                        </div>
                                    `,
                                    confirmButtonText: 'Ver Comprobante',
                                    confirmButtonColor: '#28a745'
                                }).then(() => {
                                    (async () => {
                                        try {
                                            // ✅ Registrar la venta en nuestro sistema (genera ticket_data para imprimir)
                                            const ventaResponse = await fetch('procesar_venta.php', {
                                                method: 'POST',
                                                headers: { 'Content-Type': 'application/json' },
                                                body: JSON.stringify({
                                                    cart: items,
                                                    metodo_pago: 'Mercado Pago Point',
                                                    tipo_pago: 1, // 1 = Tarjeta
                                                    tipo_tarjeta: 0
                                                })
                                            });

                                            const ventaText = await ventaResponse.text();
                                            let ventaResult = null;
                                            try {
                                                ventaResult = JSON.parse(ventaText);
                                            } catch (e) {
                                                throw new Error('Error del servidor al registrar la venta');
                                            }

                                            if (ventaResult.error || !ventaResult.success) {
                                                throw new Error(ventaResult.mensaje || ventaResult.error || 'No se pudo registrar la venta');
                                            }

                                            // Guardar datos del ticket para impresión automática en pago_aprobado.php
                                            if (ventaResult.ticket_data) {
                                                sessionStorage.setItem('ultimo_ticket_data', JSON.stringify(ventaResult.ticket_data));
                                            }

                                            // Limpiar carrito DESPUÉS de registrar
                                            this.clearCart();

                                            // Redirigir usando el flujo interno (folio/total/metodo) para mostrar despachos y permitir impresión
                                            const redirectUrl = 'pago_aprobado.php?folio=' +
                                                encodeURIComponent(ventaResult.folio) +
                                                '&total=' + encodeURIComponent(ventaResult.total) +
                                                '&metodo=' + encodeURIComponent(ventaResult.metodo_pago) +
                                                '&ticket=1';

                                            window.location.href = redirectUrl;
                                        } catch (err) {
                                            console.error('❌ Pago aprobado, pero error al registrar la venta:', err);
                                            Cart.paymentInProgress = false;
                                            Swal.fire({
                                                icon: 'warning',
                                                title: 'Pago aprobado',
                                                html: `
                                                    <p>El pago se aprobó en la terminal, pero hubo un problema registrando la venta / imprimiendo el ticket.</p>
                                                    <div class="alert alert-warning text-start">
                                                        <strong>ID de pago:</strong> ${payment.id}<br>
                                                        <strong>Monto:</strong> $${payment.amount} MXN
                                                    </div>
                                                    <p class="text-muted">Revisa la conexión con el servidor o intenta nuevamente.</p>
                                                `,
                                                confirmButtonText: 'Ir al inicio'
                                            }).then(() => {
                                                window.location.href = 'index.php';
                                            });
                                        }
                                    })();
                                });

                            } else if (state === 'ERROR' || state === 'CANCELED') {
                                // Pago fallido o cancelado
                                clearInterval(pollingInterval);
                                clearInterval(Swal.getPopup().dataset.timerInterval);
                                Cart.paymentInProgress = false; // 🔥 Desactivar bandera

                                throw new Error(state === 'CANCELED' ? 'Pago cancelado' : 'Error en el pago');

                            } else if (attempts >= maxAttempts) {
                                // Timeout - Cancelar en la terminal INMEDIATAMENTE
                                clearInterval(pollingInterval);
                                const timerInterval = Swal.getPopup()?.dataset?.timerInterval;
                                if (timerInterval) clearInterval(timerInterval);
                                Cart.paymentInProgress = false;
                                
                                // 🔥 CERRAR EL MODAL INMEDIATAMENTE
                                Swal.close();
                                
                                // 🔥 MOSTRAR LOADING MIENTRAS SE CANCELA
                                Swal.fire({
                                    title: 'Cancelando...',
                                    html: '<div class="spinner-border text-warning mb-3" role="status"></div><p>Regresando la terminal al inicio</p>',
                                    allowOutsideClick: false,
                                    showConfirmButton: false,
                                    didOpen: () => {
                                        Swal.showLoading();
                                    }
                                });
                                
                                // 🔥 EJECUTAR DELETE MÚLTIPLES VECES para forzar cancelación
                                let cancelado = false;
                                const intentosCancelacion = 3; // Intentar 3 veces
                                
                                for (let i = 0; i < intentosCancelacion; i++) {
                                    try {
                                        const cancelResponse = await fetch('mercadopago_point.php', {
                                            method: 'POST',
                                            headers: { 'Content-Type': 'application/json' },
                                            body: JSON.stringify({
                                                action: 'cancel_payment',
                                                payment_intent_id: paymentIntentId,
                                                device_id: deviceId // 🔥 IMPORTANTE: Enviar device_id
                                            })
                                        });
                                        
                                        const cancelResult = await cancelResponse.json();
                                        
                                        if (cancelResult.success && cancelResult.cancelled) {
                                            cancelado = true;
                                            break; // Salir si tuvo éxito
                                        } else if (cancelResult.http_code === 409) {
                                            // 🔥 HTTP 409 = El pago ya está en la terminal y NO se puede cancelar remotamente
                                            cancelado = 'conflict'; // Marcador especial
                                            break; // No seguir intentando
                                        }
                                        
                                        // Esperar 1 segundo antes del siguiente intento
                                        if (i < intentosCancelacion - 1) {
                                            await new Promise(resolve => setTimeout(resolve, 1000));
                                        }
                                    } catch (e) {
                                        // Error en intento de cancelación
                                    }
                                }
                                
                                // Verificar resultado de cancelación
                                
                                // Esperar 2 segundos más para que la terminal procese
                                await new Promise(resolve => setTimeout(resolve, 2000));
                                
                                // Mostrar resultado según el tipo de error
                                if (cancelado === 'conflict') {
                                    // 🔥 HTTP 409: El pago está en la terminal y NO se puede cancelar remotamente
                                    Swal.fire({
                                        icon: 'info',
                                        title: '⏱️ Pago en Terminal',
                                        width: '700px',
                                        html: `
                                            <div style="text-align: center;">
                                                <div class="alert alert-warning mb-4" style="font-size: 1.2rem; padding: 2rem;">
                                                    <h4 class="mb-3"><strong>⚠️ No se puede cancelar remotamente</strong></h4>
                                                    <p class="mb-0">El pago ya está mostrándose en la terminal Point.</p>
                                                </div>
                                                
                                                <div class="text-start mb-4">
                                                    <h5 class="mb-3">📋 SOLUCIÓN ÚNICA:</h5>
                                                    <div class="bg-danger text-white p-4 rounded mb-3" style="font-size: 1.3rem; font-weight: bold;">
                                                        👉 PRESIONA EL BOTÓN "ATRÁS" (←) EN LA TERMINAL
                                                    </div>
                                                    
                                                    <!-- 🔥 IMAGEN DE LA TERMINAL CON FLECHA Y CÍRCULO ROJO -->
                                                    <div style="position: relative; display: inline-block; margin: 2rem 0;">
                                                        <img src="images/point_img.jpg" alt="Terminal Point" style="max-width: 100%; height: auto; border-radius: 15px; box-shadow: 0 10px 40px rgba(0,0,0,0.3);">
                                                        
                                                        <!-- 🔴 CÍRCULO ROJO PULSANTE sobre el botón lateral IZQUIERDO -->
                                                        <div style="position: absolute; left: 25%; top: 20%; transform: translate(-50%, -50%); width: 90px; height: 90px; border-radius: 50%; border: 6px solid #ff0000; background: rgba(255, 0, 0, 0.4); animation: pulseCircle 1.5s ease-in-out infinite; box-shadow: 0 0 25px rgba(255, 0, 0, 1), 0 0 50px rgba(255, 0, 0, 0.7), inset 0 0 25px rgba(255, 0, 0, 0.5);"></div>
                                                        
                                                        <!-- Label al lado del círculo rojo -->
                                                        <div style="position: absolute; left: 65%; top: 17%; transform: translateX(-50%); background: #dc3545; color: white; padding: 1rem 1.5rem; border-radius: 30px; font-weight: bold; font-size: 1.4rem; white-space: nowrap; box-shadow: 0 8px 25px rgba(220, 53, 69, 0.8); animation: bounceLabel 1.5s ease-in-out infinite; z-index: 999;">
                                                            ⬅️ BOTÓN ATRÁS
                                                        </div>
                                                        
                                                        <!-- Flecha GIGANTE apuntando desde arriba -->
                                                        <div style="position: absolute; left: 25%; top: -5%; transform: translateX(-50%); animation: arrowBounceDown 1.5s ease-in-out infinite; z-index: 998;">
                                                            <div style="font-size: 6rem; filter: drop-shadow(5px 5px 15px rgba(0,0,0,0.8)); color: #ff0000;">👇</div>
                                                        </div>
                                                    </div>
                                            
                                                </div>
                                                
                                                <div class="alert alert-secondary small">
                                                    <strong>⚠️ IMPORTANTE:</strong> La terminal NO se resetea sola.
                                                    Debes presionar el botón físicamente para cancelar el pago.
                                                </div>
                                            </div>
                                            <style>
                                                @keyframes pulseCircle {
                                                    0%, 100% { 
                                                        transform: translate(-50%, -50%) scale(1); 
                                                        opacity: 1;
                                                        border-width: 5px;
                                                    }
                                                    50% { 
                                                        transform: translate(-50%, -50%) scale(1.3); 
                                                        opacity: 0.6;
                                                        border-width: 8px;
                                                    }
                                                }
                                                
                                                @keyframes arrowBounceDown {
                                                    0%, 100% { 
                                                        transform: translateX(-50%) translateY(0); 
                                                        opacity: 1;
                                                    }
                                                    50% { 
                                                        transform: translateX(-50%) translateY(10px); 
                                                        opacity: 0.7;
                                                    }
                                                }
                                                
                                                @keyframes bounceLabel {
                                                    0%, 100% { 
                                                        transform: translateX(-50%) scale(1); 
                                                    }
                                                    50% { 
                                                        transform: translateX(-50%) scale(1.1); 
                                                    }
                                                }
                                            </style>
                                        `,
                                        confirmButtonText: '✅ Entendido',
                                        confirmButtonColor: '#28a745',
                                        allowOutsideClick: false
                                    });
                                } else {
                                    // Timeout normal o cancelación exitosa
                                    Swal.fire({
                                        icon: 'warning',
                                        title: '⏱️ Tiempo Agotado',
                                        width: '750px',
                                        html: `
                                            <div style="text-align: center;">
                                                ${!cancelado ? `
                                                <!-- INSTRUCCIONES GIGANTES -->
                                                <div class="alert alert-danger mb-4" style="font-size: 1.3rem; padding: 2.5rem; border: 4px solid #dc3545; background: #fff5f5;">
                                                    <h2 class="mb-4" style="color: #dc3545; font-size: 2.2rem; font-weight: 900;">
                                                        ⚠️ ACCIÓN REQUERIDA ⚠️
                                                    </h2>
                                                    
                                                    <!-- Paso 1 -->
                                                    <div class="bg-dark text-white p-4 rounded mb-3" style="font-size: 1.6rem; font-weight: bold; animation: pulse 2s infinite;">
                                                        🔴 MIRA LA TERMINAL AMARILLA
                                                    </div>
                                                    
                                                    <!-- Paso 2 con imagen de referencia -->
                                                    <div class="bg-white p-4 rounded mb-3 border border-danger" style="font-size: 1.4rem;">
                                                        <p class="mb-2"><strong>👉 PRESIONA EL BOTÓN LATERAL IZQUIERDO</strong></p>
                                                        <p class="mb-0 text-muted" style="font-size: 1.1rem;">(El botón que está en el LADO de la terminal)</p>
                                                    </div>
                                                    
                                                    <!-- Resultado -->
                                                    <div class="bg-light p-3 rounded" style="font-size: 1.2rem;">
                                                        <p class="mb-0"><strong>✅ Esto cancelará el pago</strong></p>
                                                        <p class="mb-0">y regresará la terminal al inicio</p>
                                                    </div>
                                                </div>
                                                
                                                <p class="text-danger mb-3" style="font-size: 1.1rem;">
                                                    ⏱️ Se agotó el tiempo de espera (45 segundos)
                                                </p>
                                                ` : `
                                                <!-- Si se canceló exitosamente -->
                                                <div class="alert alert-success" style="font-size: 1.2rem; padding: 2rem;">
                                                    <h4 class="mb-3">✅ Cancelación enviada</h4>
                                                    <p class="mb-0">La terminal debería regresar al inicio en unos segundos</p>
                                                    <p class="text-muted mt-2 mb-0" style="font-size: 0.95rem;">Si no regresa, presiona el botón lateral de la terminal</p>
                                                </div>
                                                `}
                                            </div>
                                            <style>
                                                @keyframes pulse {
                                                    0%, 100% { transform: scale(1); }
                                                    50% { transform: scale(1.05); }
                                                }
                                            </style>
                                        `,
                                        confirmButtonText: '🔄 Reintentar Pago',
                                        confirmButtonColor: '#FFD700',
                                        showCancelButton: true,
                                        cancelButtonText: 'Cerrar',
                                        cancelButtonColor: '#6c757d',
                                        allowOutsideClick: false
                                    }).then((result) => {
                                        if (result.isConfirmed) {
                                            this.handleCardPayment();
                                        }
                                    });
                                }
                                
                                return; // Detener el polling
                            }
                        }

                    } catch (error) {
                        clearInterval(pollingInterval);
                    }
                };

                // Iniciar polling cada 1 segundo
                pollingInterval = setInterval(checkStatus, 1000);

                // Primera verificación inmediata
                await checkStatus();

                // Manejar cancelación manual
                const cancelBtn = Swal.getCancelButton();
                if (cancelBtn) {
                    cancelBtn.onclick = async () => {
                        clearInterval(pollingInterval);
                        clearInterval(Swal.getPopup().dataset.timerInterval);
                        Cart.paymentInProgress = false; // 🔥 Desactivar bandera

                        // Cancelar en el servidor
                        try {
                            await fetch('mercadopago_point.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({
                                    action: 'cancel_payment',
                                    payment_intent_id: paymentIntentId,
                                    device_id: deviceId // 🔥 IMPORTANTE: Enviar device_id
                                })
                            });
                        } catch (e) {
                            console.error('Error al cancelar:', e);
                        }

                        Swal.fire({
                            icon: 'info',
                            title: 'Pago Cancelado',
                            text: 'El pago fue cancelado',
                            confirmButtonText: 'Entendido'
                        });
                    };
                }

            } catch (error) {
                console.error('❌ Error Point:', error);
                
                if (pollingInterval) clearInterval(pollingInterval);
                Cart.paymentInProgress = false; // 🔥 Desactivar bandera

                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.message || 'No se pudo procesar el pago con Point',
                    confirmButtonText: 'Entendido'
                });
            }
        }

        /**
         * 🔥 MERCADO PAGO - Mostrar formulario de tarjeta directo
         */
        static async mostrarFormularioTarjeta() {
            const items = this.getItems();
            
            if (items.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Carrito vacío',
                    text: 'No hay productos en el carrito'
                });
                return;
            }

            // Calcular total
            const total = items.reduce((sum, item) => {
                const precio = parseFloat(item.precio) || 0;
                const descuento = parseFloat(item.descuento) || 0;
                const precioFinal = precio - descuento;
                return sum + (precioFinal * item.quantity);
            }, 0);

            // Mostrar modal con formulario de tarjeta
            const { value: formData } = await Swal.fire({
                title: 'Pago con Tarjeta',
                html: `
                    <div id="mp-card-form" style="max-width: 500px; margin: 0 auto;">
                        <div class="alert alert-info mb-4">
                            <strong>Total a pagar:</strong> $${total.toFixed(2)} MXN
                        </div>
                        
                        <div class="mb-3 text-start">
                            <label class="form-label">Número de Tarjeta *</label>
                            <input id="mp-card-number" type="text" class="form-control" 
                                   placeholder="0000 0000 0000 0000" maxlength="19" 
                                   autocomplete="cc-number">
                            <div id="mp-card-brand" class="mt-1 text-muted small"></div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-6 text-start">
                                <label class="form-label">Vencimiento *</label>
                                <input id="mp-card-expiry" type="text" class="form-control" 
                                       placeholder="MM/AA" maxlength="5" 
                                       autocomplete="cc-exp">
                            </div>
                            <div class="col-6 text-start">
                                <label class="form-label">CVV *</label>
                                <input id="mp-card-cvv" type="text" class="form-control" 
                                       placeholder="123" maxlength="4" 
                                       autocomplete="cc-csc">
                            </div>
                        </div>
                        
                        <div class="mb-3 text-start">
                            <label class="form-label">Nombre en la Tarjeta *</label>
                            <input id="mp-card-holder" type="text" class="form-control" 
                                   placeholder="JUAN PEREZ" 
                                   autocomplete="cc-name" style="text-transform: uppercase">
                        </div>
                        
                        <div class="mb-3 text-start">
                            <label class="form-label">Email *</label>
                            <input id="mp-card-email" type="email" class="form-control" 
                                   placeholder="correo@ejemplo.com" 
                                   autocomplete="email">
                        </div>
                        
                        <div class="mb-3 text-start">
                            <label class="form-label">RFC/ID</label>
                            <input id="mp-card-doc" type="text" class="form-control" 
                                   placeholder="ABCD123456XYZ" maxlength="18">
                            <small class="text-muted">Opcional - RFC o identificación</small>
                        </div>

                        <div class="alert alert-warning small mt-3">
                            <i class="bi bi-shield-check"></i> Pago procesado de forma segura por Mercado Pago
                        </div>
                    </div>
                `,
                width: 600,
                showCancelButton: true,
                confirmButtonText: '<i class="bi bi-credit-card"></i> Pagar $' + total.toFixed(2),
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#009ee3',
                didOpen: () => {
                    // Auto-formatear número de tarjeta
                    const cardNumberInput = document.getElementById('mp-card-number');
                    const expiryInput = document.getElementById('mp-card-expiry');
                    const cvvInput = document.getElementById('mp-card-cvv');
                    const holderInput = document.getElementById('mp-card-holder');

                    // Formatear número de tarjeta
                    cardNumberInput.addEventListener('input', (e) => {
                        let value = e.target.value.replace(/\s/g, '');
                        let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
                        e.target.value = formattedValue;
                        
                        // Detectar tipo de tarjeta
                        const brandDiv = document.getElementById('mp-card-brand');
                        if (value.startsWith('4')) {
                            brandDiv.innerHTML = '<i class="bi bi-credit-card text-primary"></i> Visa';
                        } else if (value.startsWith('5')) {
                            brandDiv.innerHTML = '<i class="bi bi-credit-card text-warning"></i> Mastercard';
                        } else if (value.startsWith('37') || value.startsWith('34')) {
                            brandDiv.innerHTML = '<i class="bi bi-credit-card text-info"></i> American Express';
                        } else {
                            brandDiv.innerHTML = '';
                        }
                    });

                    // Formatear fecha de vencimiento
                    expiryInput.addEventListener('input', (e) => {
                        let value = e.target.value.replace(/\D/g, '');
                        if (value.length >= 2) {
                            value = value.substring(0, 2) + '/' + value.substring(2, 4);
                        }
                        e.target.value = value;
                    });

                    // Solo números en CVV
                    cvvInput.addEventListener('input', (e) => {
                        e.target.value = e.target.value.replace(/\D/g, '');
                    });

                    // Mayúsculas en nombre
                    holderInput.addEventListener('input', (e) => {
                        e.target.value = e.target.value.toUpperCase();
                    });
                },
                preConfirm: () => {
                    const cardNumber = document.getElementById('mp-card-number').value.replace(/\s/g, '');
                    const expiry = document.getElementById('mp-card-expiry').value;
                    const cvv = document.getElementById('mp-card-cvv').value;
                    const holder = document.getElementById('mp-card-holder').value;
                    const email = document.getElementById('mp-card-email').value;
                    const doc = document.getElementById('mp-card-doc').value;

                    // Validaciones
                    if (!cardNumber || cardNumber.length < 13) {
                        Swal.showValidationMessage('Número de tarjeta inválido');
                        return false;
                    }
                    if (!expiry || expiry.length !== 5) {
                        Swal.showValidationMessage('Fecha de vencimiento inválida (MM/AA)');
                        return false;
                    }
                    if (!cvv || cvv.length < 3) {
                        Swal.showValidationMessage('CVV inválido');
                        return false;
                    }
                    if (!holder || holder.length < 3) {
                        Swal.showValidationMessage('Nombre del titular requerido');
                        return false;
                    }
                    if (!email || !email.includes('@')) {
                        Swal.showValidationMessage('Email inválido');
                        return false;
                    }

                    const [expMonth, expYear] = expiry.split('/');

                    return {
                        cardNumber: cardNumber,
                        expirationMonth: expMonth,
                        expirationYear: '20' + expYear,
                        cvv: cvv,
                        cardholderName: holder,
                        email: email,
                        docNumber: doc || '00000000000'
                    };
                }
            });

            if (!formData) return;

            // Procesar el pago
            await this.procesarPagoDirecto(formData, items, total);
        }

        /**
         * 🔥 MERCADO PAGO - Procesar pago directo
         */
        static async procesarPagoDirecto(cardData, items, total) {
            Swal.fire({
                title: 'Procesando pago...',
                html: `
                    <div class="spinner-border text-primary mb-3" role="status"></div>
                    <p>Validando tarjeta con Mercado Pago</p>
                    <small class="text-muted">No cierres esta ventana</small>
                `,
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            try {
                // Paso 1: Inicializar MP SDK
                const mp = new MercadoPago('TEST-0c1b6e80-fe3c-4004-bfb5-2c725a406cc9', {
                    locale: 'es-MX'
                });

                // Paso 2: Crear token de tarjeta
                const cardToken = await mp.createCardToken({
                    cardNumber: cardData.cardNumber,
                    cardholderName: cardData.cardholderName,
                    cardExpirationMonth: cardData.expirationMonth,
                    cardExpirationYear: cardData.expirationYear,
                    securityCode: cardData.cvv,
                    identificationType: 'RFC',
                    identificationNumber: cardData.docNumber
                });

                if (!cardToken || !cardToken.id) {
                    throw new Error('No se pudo crear el token de la tarjeta');
                }

                // Paso 3: Enviar al servidor para procesar
                const paymentData = {
                    token: cardToken.id,
                    payment_method_id: cardToken.payment_method_id,
                    transaction_amount: total,
                    installments: 1,
                    description: 'Compra VendingBox - ' + items.length + ' productos',
                    payer: {
                        email: cardData.email,
                        identification: {
                            type: 'RFC',
                            number: cardData.docNumber
                        },
                        first_name: cardData.cardholderName.split(' ')[0],
                        last_name: cardData.cardholderName.split(' ').slice(1).join(' ') || 'Cliente'
                    },
                    items: items.map(item => ({
                        id: item.id_producto.toString(),
                        title: item.nombre_producto,
                        quantity: item.quantity,
                        unit_price: parseFloat(item.precio) - parseFloat(item.descuento || 0)
                    }))
                };

                console.log('📤 Enviando pago:', paymentData);

                const response = await fetch('mercadopago_process_card.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(paymentData)
                });

                const result = await response.json();

                if (!result.success) {
                    throw new Error(result.message || result.error || 'Error al procesar el pago');
                }

                // Pago exitoso
                Swal.fire({
                    icon: 'success',
                    title: '¡Pago Exitoso!',
                    text: result.message,
                    confirmButtonText: 'Ver Comprobante',
                    timer: 3000
                }).then(() => {
                    // Limpiar carrito
                    this.clearCart();
                    
                    // Redirigir a página de éxito
                    if (result.redirect) {
                        window.location.href = result.redirect;
                    }
                });

            } catch (error) {
                let errorMessage = 'No se pudo procesar el pago';
                
                if (error.message) {
                    errorMessage = error.message;
                } else if (error.cause && error.cause.length > 0) {
                    errorMessage = error.cause[0].description || errorMessage;
                }

                Swal.fire({
                    icon: 'error',
                    title: 'Error en el Pago',
                    text: errorMessage,
                    confirmButtonText: 'Reintentar',
                    showCancelButton: true,
                    cancelButtonText: 'Volver'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Reintentar
                        this.mostrarFormularioTarjeta();
                    }
                });
            }
        }

        /**
         * 🔥 MERCADO PAGO - Procesar pago con preferencia (checkout redirect)
         */
        static async procesarPagoMercadoPago() {
            const items = this.getItems();
            
            if (items.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Carrito vacío',
                    text: 'No hay productos en el carrito'
                });
                return;
            }

            // Solicitar datos del cliente
            const { value: formValues } = await Swal.fire({
                title: 'Datos para el pago',
                html: `
                    <div class="text-start">
                        <div class="mb-3">
                            <label class="form-label">Nombre completo *</label>
                            <input id="mp-name" class="swal2-input" placeholder="Juan Pérez" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email *</label>
                            <input id="mp-email" type="email" class="swal2-input" placeholder="correo@ejemplo.com" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Teléfono</label>
                            <input id="mp-phone" class="swal2-input" placeholder="5512345678">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Código Postal</label>
                            <input id="mp-zip" class="swal2-input" placeholder="01000">
                        </div>
                    </div>
                `,
                focusConfirm: false,
                showCancelButton: true,
                confirmButtonText: 'Continuar al pago',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#009ee3',
                preConfirm: () => {
                    const name = document.getElementById('mp-name').value;
                    const email = document.getElementById('mp-email').value;
                    
                    if (!name || !email) {
                        Swal.showValidationMessage('Nombre y email son obligatorios');
                        return false;
                    }
                    
                    // Validar email
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(email)) {
                        Swal.showValidationMessage('Email inválido');
                        return false;
                    }
                    
                    return {
                        name: name,
                        email: email,
                        phone: document.getElementById('mp-phone').value || '5512345678',
                        zip_code: document.getElementById('mp-zip').value || '01000'
                    };
                }
            });

            if (!formValues) return;

            // Mostrar loading
            Swal.fire({
                title: 'Procesando...',
                html: '<div class="spinner-border text-primary" role="status"></div><br>Generando link de pago seguro',
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            try {
                // Preparar datos para enviar
                const paymentData = {
                    items: items,
                    payer: formValues
                };

                console.log('📤 Enviando a Mercado Pago:', paymentData);

                // Llamar al endpoint PHP
                const response = await fetch('mercadopago_create_preference.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(paymentData)
                });

                const result = await response.json();

                if (!result.success) {
                    throw new Error(result.error || 'Error al crear preferencia de pago');
                }

                localStorage.setItem('mp_order_reference', result.external_reference);
                localStorage.setItem('mp_preference_id', result.preference_id);

                Swal.fire({
                    icon: 'success',
                    title: '¡Listo!',
                    text: 'Serás redirigido a Mercado Pago para completar el pago',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    // Usar sandbox en modo test, init_point en producción
                    const paymentUrl = result.mode === 'test' && result.sandbox_init_point 
                        ? result.sandbox_init_point 
                        : result.init_point;
                    
                    window.location.href = paymentUrl;
                });

            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.message || 'No se pudo procesar el pago. Intenta nuevamente.',
                    confirmButtonText: 'Entendido'
                });
            }
        }

        static resolveProduct(btnOrId) {
            const productId = typeof btnOrId === 'object'
                ? (btnOrId.dataset?.productId || btnOrId.getAttribute?.('data-product-id'))
                : btnOrId;

            if (!productId) return null;

            const pools = [
                ...(Array.isArray(window.products) ? window.products : []),
                ...(Array.isArray(window.featuredProducts) ? window.featuredProducts : [])
            ];

            let product = pools.find(p => String(p.id_producto) === String(productId));

            if (!product && window._productIndex) {
                product = window._productIndex[String(productId)];
            }

            if (!product && window.CarouselManager?.carousels) {
                for (const key of Object.keys(window.CarouselManager.carousels)) {
                    const carousel = window.CarouselManager.carousels[key];
                    product = carousel.data?.find(p => String(p.id_producto) === String(productId));
                    if (product) break;
                }
            }

            if (!product && typeof btnOrId === 'object' && btnOrId.dataset?.nombre) {
                product = {
                    id_producto: productId,
                    nombre_producto: btnOrId.dataset.nombre,
                    precio: parseFloat(btnOrId.dataset.precio) || 0,
                    descuento: parseFloat(btnOrId.dataset.descuento) || 0,
                    imagen_principal: btnOrId.dataset.imagen || './images/default-product.png'
                };
            }

            return product || null;
        }

        static registerProduct(product) {
            if (!product || product.id_producto == null) return;
            if (!window._productIndex) window._productIndex = {};
            window._productIndex[String(product.id_producto)] = product;
        }

        static addItem(product) {
            // Asegurar que el producto tenga un ID válido
            const productId = product.id_producto || product.id;
            if (!productId) {
                return;
            }

            this.registerProduct(product);

            // Buscar si el producto ya existe en el carrito (por ID)
            const existingItem = cartItems.find(item => 
                String(item.id_producto) === String(productId)
            );

            if (existingItem) {
                // Si existe, solo incrementar cantidad
                existingItem.quantity += 1;
            } else {
                // Si no existe, agregarlo con estructura consistente
                cartItems.push({
                    id_producto: productId,
                    nombre_producto: product.nombre_producto || product.nombre,
                    precio: product.precio,
                    descuento: product.descuento || 0,
                    imagen_principal: product.imagen_principal || product.imagen || './images/placeholder-product.png',
                    quantity: 1
                });
            }

            this.saveCart();
            this.updateCartUI();
            this.updatePayButton(); // ✅ Habilitar botón cuando se agregue producto

            Swal.fire({
                title: 'Producto agregado',
                text: 'El producto se agrego al carrito',
                icon: 'success',
                toast: true,
                position: 'bottom-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer)
                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                }
            });
        }

        static removeItem(productId) {
            // Buscar el item con búsqueda flexible de ID
            const itemIndex = cartItems.findIndex(item => 
                String(item.id_producto) === String(productId) ||
                String(item.id) === String(productId)
            );
            
            if (itemIndex === -1) {
                return;
            }
            
            const item = cartItems[itemIndex];
            
            // Si tiene más de 1, solo disminuir la cantidad
            if (item.quantity > 1) {
                item.quantity--;
                
                this.saveCart();
                this.renderCartTable();
                this.updateCartUI();
                this.updatePayButton(); // ✅ Actualizar botón después de reducir cantidad
                this.resetInactivityTimer();
                
                // Mensaje de cantidad reducida
                Swal.fire({
                    title: 'Cantidad actualizada',
                    text: `Cantidad reducida a ${item.quantity}`,
                    icon: 'info',
                    toast: true,
                    position: 'bottom-end',
                    showConfirmButton: false,
                    timer: 1500
                });
            } else {
                // Si solo hay 1, eliminar el producto completamente
                cartItems.splice(itemIndex, 1);
                
                this.saveCart();
                this.renderCartTable();
                this.updateCartUI();
                this.updatePayButton(); // ✅ Deshabilitar botón si carrito queda vacío
                this.resetInactivityTimer();
                
                // Mensaje de producto eliminado
                Swal.fire({
                    title: 'Producto eliminado',
                    text: 'El producto se eliminó del carrito',
                    icon: 'success',
                    toast: true,
                    position: 'bottom-end',
                    showConfirmButton: false,
                    timer: 2000
                });
            }
        }

        static removeItemCompletely(productId) {
            const itemIndex = cartItems.findIndex(item =>
                String(item.id_producto) === String(productId) ||
                String(item.id) === String(productId)
            );

            if (itemIndex === -1) {
                return;
            }

            const [item] = cartItems.splice(itemIndex, 1);

            this.saveCart();
            this.renderCartTable();
            this.updateCartUI();
            this.updatePayButton();
            this.resetInactivityTimer();

            Swal.fire({
                title: 'Producto removido',
                text: `${item?.nombre_producto || 'Producto'} se eliminó completamente del carrito`,
                icon: 'success',
                toast: true,
                position: 'bottom-end',
                showConfirmButton: false,
                timer: 2000
            });
        }

        static updateQuantity(productId, quantity) {
           
            
            // Comparar usando String para evitar problemas de tipos
            const item = cartItems.find(item => 
                String(item.id_producto) === String(productId)
            );
            
            if (item) {
                const newQty = parseInt(quantity);
                
                if (newQty <= 0) {
                    this.removeItem(productId);
                } else {
                    item.quantity = newQty;
                    this.saveCart();
                    
                    // Volver a renderizar la tabla completa para asegurar consistencia
                    this.renderCartTable();
                    this.updateCartUI();
                    this.updatePayButton(); // ✅ Actualizar estado del botón
                    this.resetInactivityTimer();
                }
            } 
        }

        static getTotal() {
            // Total solo de productos (sin servicios)
            const productsTotal = cartItems.reduce((total, item) => {
                const precioOriginal = parseFloat(item.precio) || 0;
                const descuentoVal = item.descuento != null ? parseFloat(item.descuento) : 0;
                const descuento = isNaN(descuentoVal) ? 0 : descuentoVal;
                const qty = parseInt(item.quantity, 10) || 0;
                // ✅ Descuento en PESOS, no porcentaje
                const price = precioOriginal - descuento;
                return total + (price * qty);
            }, 0);

            return productsTotal;
        }

        static getItemsCount() {
            // Solo contar productos, no servicios
            const productsCount = cartItems.reduce((total, item) => total + (parseInt(item.quantity, 10) || 0), 0);
            return productsCount;
        }

        static getItems() {
            // Retornar solo productos del carrito
            return [...cartItems];
        }

        /** Alias usado por cart.php y vistas legacy */
        static get items() {
            return this.getItems();
        }

        static saveToStorage() {
            this.saveCart();
        }

        static addService(service) {
            serviceItems.push(service);
            localStorage.setItem('serviceCart', JSON.stringify(serviceItems));
            this.updateCartUI();
            
            Swal.fire({
                title: 'Servicio agregado',
                text: 'El servicio se agregó al carrito',
                icon: 'success',
                toast: true,
                position: 'bottom-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
        }

        static removeService(sku) {
            serviceItems = serviceItems.filter(item => item.sku !== sku);
            localStorage.setItem('serviceCart', JSON.stringify(serviceItems));
            this.renderCartTable(); // Volver a renderizar la tabla completa
            this.updateCartUI(); // Actualizar el mini carrito también
            this.resetInactivityTimer();
            
            // Mostrar mensaje de confirmación
            Swal.fire({
                title: 'Servicio eliminado',
                text: 'El servicio se eliminó del carrito',
                icon: 'success',
                toast: true,
                position: 'bottom-end',
                showConfirmButton: false,
                timer: 2000
            });
        }

        static saveCart() {
            try {
                localStorage.setItem('cart', JSON.stringify(cartItems));
            } catch (e) {
                if (e.name === 'QuotaExceededError' || e.code === 22) {
                    console.error('⚠️ LocalStorage lleno, limpiando...');
                    // Limpiar datos viejos
                    this.limpiarLocalStorage();
                    // Intentar guardar de nuevo
                    try {
                        localStorage.setItem('cart', JSON.stringify(cartItems));
                    } catch (err) {
                        console.error('❌ No se pudo guardar el carrito:', err);
                        alert('Error: No se pudo guardar el carrito. El almacenamiento está lleno.');
                    }
                } else {
                    console.error('❌ Error al guardar carrito:', e);
                }
            }
        }

        static limpiarLocalStorage() {
            // Lista de items que SÍ queremos mantener
            const itemsImportantes = ['cart', 'user_session', 'token'];
            
            // Obtener todas las keys
            const todasLasKeys = [];
            for (let i = 0; i < localStorage.length; i++) {
                todasLasKeys.push(localStorage.key(i));
            }
            
            // Eliminar todo excepto lo importante
            todasLasKeys.forEach(key => {
                if (!itemsImportantes.includes(key)) {
                    localStorage.removeItem(key);
                    console.log('🗑️ Eliminado:', key);
                }
            });
            
            console.log('✅ LocalStorage limpiado');
        }

        static updateCartUI() {
            // 🔥 Recargar datos del localStorage al actualizar UI
            cartItems = JSON.parse(localStorage.getItem('cart')) || [];
            serviceItems = JSON.parse(localStorage.getItem('serviceCart')) || [];
            
            const cartCountInner = document.getElementById('cart-count-inner');
            const cartCountOuter = document.querySelector('.total-count');
            const cartList = document.querySelector('.shopping-list');
            const cartTotal = document.getElementById('cart-total');
            const itemCount = this.getItemsCount();
            const headerDisplay = document.getElementById('header-cart-count-display');
            const heroCount = document.getElementById('hero-cart-count');
            const heroTotal = document.getElementById('hero-cart-total');

            if (cartCountInner) {
                cartCountInner.textContent = itemCount;
            }
            if (cartCountOuter) {
                cartCountOuter.innerHTML = `<span style="display: block; width: 100%; text-align: center;">${itemCount}</span>`;
            }

            if (headerDisplay) {
                headerDisplay.textContent = itemCount;
            }

            if (heroCount) {
                const label = itemCount === 1 ? '1 producto' : `${itemCount} productos`;
                heroCount.textContent = label;
            }

            if (heroTotal) {
                heroTotal.textContent = `$${this.getTotal().toFixed(2)}`;
            }

            if (cartList) {
                // Si el carrito está vacío, mostrar mensaje
                if (cartItems.length === 0) {
                    cartList.innerHTML = '';
                } else {
                    // Render solo productos (sin servicios en el mini-carrito)
                    const productsHTML = cartItems.map(item => {
                        const precioOriginal = parseFloat(item.precio) || 0;
                        const descuentoVal = item.descuento != null ? parseFloat(item.descuento) : 0;
                        const descuento = isNaN(descuentoVal) ? 0 : descuentoVal;
                        const qty = parseInt(item.quantity, 10) || 0;
                        // ✅ Descuento en PESOS, no porcentaje
                        const price = precioOriginal - descuento;
                        const totalPrice = price * qty;
                        
                        // 💰 Construir HTML del precio con/sin descuento
                        let precioHTML;
                        if (descuento > 0) {
                            // Con descuento: mostrar precio con descuento y original tachado
                            precioHTML = `
                                <span style="color: #dc3545; font-weight: 600;">$${price.toFixed(2)}</span> × ${qty} = 
                                <span style="color: #dc3545; font-weight: 600;">$${totalPrice.toFixed(2)}</span>
                                <br>
                                <small style="text-decoration: line-through; color: #999;">$${precioOriginal.toFixed(2)} c/u</small>
                            `;
                        } else {
                            // Sin descuento: solo el precio normal
                            precioHTML = `$${price.toFixed(2)} × ${qty} = $${totalPrice.toFixed(2)}`;
                        }
                        
                        return `
                            <li class="cart-item" data-id="${item.id_producto}">
                                <img src="${item.imagen_principal}" alt="${item.nombre_producto}" class="cart-img">
                                <div class="content">
                                    <h4><a href="#">${item.nombre_producto}</a></h4>
                                    <div class="price">${precioHTML}</div>
                                </div>
                                <button class="remove remove-product-btn" data-product-id="${item.id_producto}" title="Eliminar producto">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </li>
                        `;
                    });

                    cartList.innerHTML = productsHTML.join('');
                }
            }

            // Actualizar contador de productos en el header
            const cartCount = document.querySelector('.dropdown-cart-header span');
            if (cartCount) {
                const itemCount = this.getItemsCount();
                cartCount.textContent = `${itemCount} ${itemCount === 1 ? 'Producto' : 'Productos'}`;
            }

            // Actualizar el total en el mini-carrito
            const totalAmountSpan = document.querySelector('.shopping-item .total .total-amount');
            if (totalAmountSpan) {
                totalAmountSpan.textContent = `$${this.getTotal().toFixed(2)}`;
            }
            
            // También actualizar si existe el ID cart-total (para cart.php)
            if (cartTotal) {
                cartTotal.textContent = `$${this.getTotal().toFixed(2)}`;
            }

            // Manage inactivity timer visibility based on cart content
            if (itemCount === 0) {
                clearTimeout(this.inactivityTimeout);
                this.warningShown = false;
                if (typeof Swal !== 'undefined' && Swal.close) {
                    Swal.close();
                }
            }
        }

        static init() {
            [...(window.products || []), ...(window.featuredProducts || [])].forEach(p => {
                this.registerProduct(p);
            });

            this.updateCartUI();
            this.updatePayButton(); // ✅ Validar estado inicial del botón
            this.resetInactivityTimer();
            
            // 🔥 OPTIMIZADO: Solo eventos importantes para detectar actividad
            // Removido 'mousemove' que se dispara demasiado seguido
            ['click', 'keypress', 'touchstart'].forEach(eventName => {
                document.addEventListener(eventName, () => {
                    if (cartItems.length > 0 && !this.paymentInProgress && !this.warningShown) {
                        this.resetInactivityTimer();
                    }
                }, { passive: true }); // 🔥 passive para mejor performance
            });
            
            // Remover listener anterior si existe
            if (this.handleCartClick) {
                document.removeEventListener('click', this.handleCartClick);
            }
            
            // Crear nuevo listener
            this.handleCartClick = (e) => {
                // Manejar botones +/-
                if (e.target.closest('.btn-number')) {
                    e.preventDefault();
                    const btn = e.target.closest('.btn-number');
                    const productId = btn.dataset.productId;
                    const type = btn.dataset.type;
                    
                    // Comparar tanto como string como número
                    const item = cartItems.find(item => 
                        item.id_producto == productId || 
                        item.id_producto === productId ||
                        String(item.id_producto) === String(productId)
                    );
                    
                    if (item) {
                        const newQuantity = type === 'plus' ? item.quantity + 1 : item.quantity - 1;
                        this.updateQuantity(productId, newQuantity);
                    }
                    return;
                }
                
                // Manejar botón de eliminar producto
                if (e.target.closest('.remove-product-btn')) {
                    e.preventDefault();
                    const btn = e.target.closest('.remove-product-btn');
                    const productId = btn.dataset.productId;
                    this.removeItem(productId);
                    return;
                }

                    // Manejar botón de eliminar completamente un producto
                    if (e.target.closest('.remove-product-all-btn')) {
                        e.preventDefault();
                        const btn = e.target.closest('.remove-product-all-btn');
                        const productId = btn.dataset.productId;
                        this.removeItemCompletely(productId);
                        return;
                    }
                
                // Manejar botón de eliminar servicio (solo en cart.php, no en mini-carrito)
                if (e.target.closest('.remove-service-btn')) {
                    e.preventDefault();
                    const btn = e.target.closest('.remove-service-btn');
                    const serviceSku = btn.dataset.serviceSku;
                    this.removeService(serviceSku);
                    return;
                }
                
                const addBtn = e.target.closest('.add-to-cart-btn, .add-to-cart');
                if (addBtn) {
                    e.preventDefault();
                    e.stopPropagation();
                    const btn = addBtn;
                    const product = this.resolveProduct(btn);

                    if (product) {
                        try {
                            this.addItem(product);
                        } catch (error) {
                            Swal.fire({
                                title: 'Error',
                                text: 'No se pudo agregar el producto al carrito',
                                icon: 'error',
                                toast: true,
                                position: 'bottom-end',
                                showConfirmButton: false,
                                timer: 3000
                            });
                        }
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: 'Producto no encontrado',
                            icon: 'error',
                            toast: true,
                            position: 'bottom-end',
                            showConfirmButton: false,
                            timer: 3000
                        });
                    }
                }
            };
            
            // Agregar el listener
            document.addEventListener('click', this.handleCartClick);
        }
    }

    function setupCartEvents() {
        const cartIcon = document.querySelector('.single-icon');
        const cartSidebar = document.getElementById('cart-sidebar');
        
        if (!cartIcon || !cartSidebar) return; 
        let timeout;

        cartIcon.addEventListener('mouseenter', () => {
            clearTimeout(timeout);
            cartSidebar.style.display = 'block';
        });

        cartIcon.addEventListener('mouseleave', () => {
            timeout = setTimeout(() => {
                if (!cartSidebar.matches(':hover')) {
                    cartSidebar.style.display = 'none';
                }
            }, 200);
        });

        cartSidebar.addEventListener('mouseenter', () => {
            clearTimeout(timeout);
        });

        cartSidebar.addEventListener('mouseleave', () => {
            timeout = setTimeout(() => {
                cartSidebar.style.display = 'none';
            }, 200);
        });

        cartIcon.addEventListener('click', (e) => {
            e.preventDefault();
        });
    }

    window.Cart = Cart;

    // NO llamar Cart.init() aquí, se llamará desde cart.php o index.php según sea necesario
