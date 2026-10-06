/**
 * Integración del Monedero con el Sistema de Ventas
 * Escucha cambios en el saldo y actualiza la UI
 * 
 * Fecha: 21-01-2026
 */

const MonederoIntegration = {
    saldoActual: 0,
    saldoMaquina: 0,
    checkInterval: null,
    saldoDisplay: null,
    ultimaActualizacion: 0,
    modoRapido: false,
    errorLogueado: false, // Para evitar loguear el mismo error repetidamente
    
    /**
     * Inicializa la integración del monedero
     */
    init: function() {
        // Crear display de saldo
        this.crearDisplaySaldo();
        
        // Obtener saldo inicial
        this.obtenerSaldo();
        
        // Iniciar polling para detectar nuevas monedas
        this.iniciarPolling();
        
        // Agregar botón de pago con monedas en el carrito
        this.agregarBotonPagoMonedas();
    },
    
    /**
     * Crea un display flotante para mostrar el saldo
     */
    crearDisplaySaldo: function() {
        // Primero verificar si ya existe para no duplicar
        const existente = document.getElementById('monedero-display');
        if (existente) {
            existente.remove();
        }
        
        const displayHTML = `
            <div id="monedero-display" class="wallet-widget collapsed">
                <!-- Pestaña colapsada -->
                <div class="wallet-tab" onclick="MonederoIntegration.toggleWidget()" title="Ver mi saldo">
                    <div class="wallet-tab-icon">
                        <i class="bi bi-wallet2"></i>
                    </div>
                    <div class="wallet-tab-amount" id="wallet-tab-amount">$0.00</div>
                    <div class="wallet-tab-label">TU SALDO</div>
                </div>
                
                <!-- Contenido expandido -->
                <div class="wallet-content">
                    <div class="wallet-header">
                        <h3 class="wallet-title">MONEDERO</h3>
                        <button class="wallet-close" onclick="MonederoIntegration.toggleWidget()">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>

                    <div class="wallet-balance-section">
                        <div class="wallet-balance-label">Tu saldo (cliente)</div>
                        <div id="monedero-saldo" class="wallet-balance">$0.00</div>
                        <div class="wallet-balance-hint">Disponible para pagar productos</div>
                    </div>

                    <div id="monedero-status" class="wallet-status">
                        <i class="bi bi-check-circle-fill"></i>
                        <span id="status-text">Saldo disponible para pagar</span>
                    </div>
                </div>
            </div>
        `;
        
        // Insertar al final del body
        document.body.insertAdjacentHTML('beforeend', displayHTML);
        this.saldoDisplay = document.getElementById('monedero-display');
    },
    
    /**
     * Toggle para expandir/colapsar el widget
     */
    toggleWidget: function() {
        const widget = document.getElementById('monedero-display');
        if (widget) {
            widget.classList.toggle('collapsed');
        }
    },
    
    /**
     * Obtiene el saldo actual del monedero
     */
    obtenerSaldo: function() {
        fetch('monedero_api.php?action=get_saldo&_=' + Date.now())
            .then(response => {
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    throw new Error('El API no devolvió JSON. Posible error en monedero_api.php');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    this.actualizarSaldo(data.saldo_cliente ?? data.saldo ?? 0);
                    this.actualizarSaldoMaquina(data.saldo_maquina ?? 0);
                }
            })
            .catch(error => {
                console.error('Error al obtener saldo:', error);
            });
    },

    actualizarSaldoMaquina: function(nuevoSaldo) {
        const parsed = parseFloat(nuevoSaldo) || 0;
        if (parsed === this.saldoMaquina) {
            return;
        }
        this.saldoMaquina = parsed;
        window.saldoMaquinaActual = parsed;
        const el = document.getElementById('monedero-saldo-maquina');
        if (el) {
            el.textContent = '$' + parsed.toFixed(2);
            if (parsed <= 0) {
                el.classList.add('wallet-machine-amount--low');
            } else {
                el.classList.remove('wallet-machine-amount--low');
            }
        }
    },
    
    /**
     * Actualiza el display del saldo
     */
    actualizarSaldo: function(nuevoSaldo) {
        const nuevoSaldoParsed = parseFloat(nuevoSaldo);
        
        // NO actualizar si el saldo es exactamente el mismo
        if (nuevoSaldoParsed === this.saldoActual) {
            return;
        }
        
        const saldoAnterior = this.saldoActual;
        this.saldoActual = nuevoSaldoParsed;
        
        // 🔔 NOTIFICAR CAMBIO DE SALDO globalmente
        window.monederoSaldoActual = nuevoSaldoParsed;
        
        const saldoElement = document.getElementById('monedero-saldo');
        const tabSaldoElement = document.getElementById('wallet-tab-amount');
        const statusElement = document.getElementById('monedero-status');
        const statusIcon = document.getElementById('status-icon');
        const statusText = document.getElementById('status-text');
        const indicator = document.getElementById('monedero-indicator');
        
        if (saldoElement) {
            // Animación cuando aumenta el saldo
            if (nuevoSaldoParsed > saldoAnterior) {
                // Efecto de incremento
                saldoElement.style.animation = 'none';
                setTimeout(() => {
                    saldoElement.style.animation = 'monederoPulse 0.6s ease-out';
                }, 10);
            }
            
            // Actualizar el monto con animación de conteo
            this.animarCambioSaldo(saldoElement, saldoAnterior, nuevoSaldoParsed);
            
            // Actualizar también el saldo en la pestaña
            if (tabSaldoElement) {
                tabSaldoElement.textContent = '$' + nuevoSaldoParsed.toFixed(2);
            }
            
            // Actualizar mensaje de estado e indicador
            if (this.saldoActual > 0) {
                if (statusIcon) statusIcon.className = 'bi bi-check-circle-fill';
                if (statusText) statusText.textContent = 'Saldo disponible para pagar';
                
                // MOSTRAR el widget cuando hay saldo
                if (this.saldoDisplay) {
                    this.saldoDisplay.classList.add('has-balance');
                }
            } else {
                if (statusIcon) statusIcon.className = 'bi bi-exclamation-circle';
                if (statusText) statusText.textContent = 'Inserta monedas para agregar';
                
                // OCULTAR el widget cuando no hay saldo
                if (this.saldoDisplay) {
                    this.saldoDisplay.classList.remove('has-balance');
                }
            }
        }
        
        // Actualizar botón de efectivo
        this.actualizarBotonEfectivo();
    },
    
    /**
     * Anima el cambio de saldo con efecto de conteo
     */
    animarCambioSaldo: function(element, desde, hasta) {
        const duracion = 400; // ms - MÁS RÁPIDO para fluidez
        const pasos = 20;
        const incremento = (hasta - desde) / pasos;
        let actual = desde;
        let paso = 0;
        
        const intervalo = setInterval(() => {
            paso++;
            actual += incremento;
            
            if (paso >= pasos) {
                actual = hasta;
                clearInterval(intervalo);
            }
            
            element.textContent = '$' + actual.toFixed(2);
        }, duracion / pasos);
    },
    
    /**
     * Inicia polling para detectar nuevas monedas
     */
    iniciarPolling: function() {
        // Verificar cada 150ms si hay nuevas monedas (modo ultra rápido)
        // Se cambia a 100ms cuando detecta actividad reciente para máxima fluidez
        const poll = () => {
            this.checkNuevaMoneda();
            this.verificarCambioSaldo();
            
            // Si hubo actividad en los últimos 15 segundos, polling ULTRA rápido
            const tiempoDesdeUltimaActualizacion = Date.now() - this.ultimaActualizacion;
            const intervalo = tiempoDesdeUltimaActualizacion < 15000 ? 100 : 150;
            
            if (intervalo === 100 && !this.modoRapido) {
                this.modoRapido = true;
            } else if (intervalo === 150 && this.modoRapido) {
                this.modoRapido = false;
            }
            
            this.checkInterval = setTimeout(poll, intervalo);
        };
        
        poll();
    },
    
    /**
     * Detiene el polling
     */
    detenerPolling: function() {
        if (this.checkInterval) {
            clearTimeout(this.checkInterval);
            this.checkInterval = null;
        }
    },
    
    /**
     * Verifica si se insertó una nueva moneda
     */
    checkNuevaMoneda: function() {
        fetch('monedero_api.php?action=check_nueva_moneda')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.nueva_moneda) {
                    // Se insertó una nueva moneda!
                    // Obtener saldo actualizado
                    this.obtenerSaldo();
                    
                    // Mostrar notificación
                    this.mostrarNotificacion(data.monto);
                    
                    // Reproducir sonido (opcional)
                    this.reproducirSonido();
                }
            })
            .catch(error => {
                // Silenciar errores repetitivos en polling
                // Solo loguear el primer error
                if (!this.errorLogueado) {
                    console.error('Error al verificar nueva moneda:', error);
                    console.error('Revisa que monedero_api.php esté funcionando correctamente');
                    this.errorLogueado = true;
                }
            });
    },
    
    /**
     * Verifica cambios en el saldo directamente
     * Esto complementa checkNuevaMoneda por si se pierde la señal
     */
    verificarCambioSaldo: function() {
        fetch('monedero_api.php?action=get_saldo&_=' + Date.now())
            .then(response => {
                // Verificar si la respuesta es JSON
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    throw new Error('El API no devolvió JSON');
                }
                return response.json();
            })
            .then(data => {
                if (!data.success) {
                    return;
                }

                const nuevoSaldo = parseFloat(data.saldo_cliente ?? data.saldo);
                const nuevoSaldoMaquina = parseFloat(data.saldo_maquina);

                if (!isNaN(nuevoSaldoMaquina)) {
                    this.actualizarSaldoMaquina(nuevoSaldoMaquina);
                }

                if (data.saldo !== null && data.saldo !== undefined && !isNaN(nuevoSaldo)) {
                    if (nuevoSaldo !== this.saldoActual) {
                        const diferencia = nuevoSaldo - this.saldoActual;
                        this.actualizarSaldo(nuevoSaldo);
                        this.ultimaActualizacion = Date.now();

                        window.dispatchEvent(new CustomEvent('monederoSaldoChanged', {
                            detail: { saldo: nuevoSaldo, diferencia: diferencia }
                        }));

                        if (diferencia > 0) {
                            this.mostrarNotificacion(diferencia);
                            this.reproducirSonido();
                        }
                    }
                }
            })
            .catch(error => {
                // Silenciar errores de red para no llenar console
            });
    },    /**
     * Muestra una notificación cuando se inserta una moneda
     */
    mostrarNotificacion: function(monto) {
        // NO mostrar alerta - el display morado es suficiente
        // El display morado ya muestra el saldo actualizado
    },
    
    /**
     * Reproduce un sonido cuando se inserta una moneda
     */
    reproducirSonido: function() {
        // Crear un beep simple usando Web Audio API
        try {
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();
            
            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);
            
            oscillator.frequency.value = 800;
            oscillator.type = 'sine';
            
            gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.2);
            
            oscillator.start(audioContext.currentTime);
            oscillator.stop(audioContext.currentTime + 0.2);
        } catch (e) {
            // Sonido no disponible
        }
    },
    
    /**
     * Agrega funcionalidad al botón de efectivo para usar monedas
     */
    agregarBotonPagoMonedas: function() {
        // Buscar el botón de efectivo existente
        const checkAndModify = () => {
            const btnEfectivo = document.querySelector('[data-payment="cash"]');
            
            if (btnEfectivo && !btnEfectivo.dataset.monederoActivado) {
                // Marcar como activado para no duplicar
                btnEfectivo.dataset.monederoActivado = 'true';
                
                btnEfectivo.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    if (typeof Cart !== 'undefined' && typeof Cart.handleCashPayment === 'function') {
                        Cart.handleCashPayment();
                    }
                }, true);
                
                // Actualizar texto del botón para mostrar saldo
                this.actualizarBotonEfectivo();
            }
        };
        
        // Intentar agregar inmediatamente y también después de 1 segundo
        checkAndModify();
        setTimeout(checkAndModify, 1000);
    },
    
    /**
     * Actualiza el botón de efectivo con el saldo de monedas
     */
    actualizarBotonEfectivo: function() {
        const btnEfectivo = document.querySelector('[data-payment="cash"]');
        if (btnEfectivo) {
            const icono = '<i class="bi bi-wallet2 me-2"></i>';
            
            if (this.saldoActual > 0) {
                // Si hay saldo, mostrar con color verde y el monto
                btnEfectivo.className = 'btn btn-success btn-lg fw-bold shadow-sm';
                btnEfectivo.innerHTML = `<i class="bi bi-coin me-2"></i>Pagar en Efectivo ($${this.saldoActual.toFixed(2)} disponible)`;
            } else {
                // Si no hay saldo, botón normal
                btnEfectivo.className = 'btn btn-outline-dark btn-lg fw-bold';
                btnEfectivo.innerHTML = `${icono}Pagar en Efectivo`;
            }
        }
    },
    
    /**
     * Procesa el pago con monedas
     */
    procesarPagoConMonedas: function() {
        // Verificar que hay suficiente saldo
        const totalCarrito = typeof Cart !== 'undefined' ? Cart.getTotal() : 0;
        
        if (this.saldoActual < totalCarrito) {
            if (typeof Cart !== 'undefined' && typeof Cart.mostrarSaldoInsuficiente === 'function') {
                Cart.mostrarSaldoInsuficiente(totalCarrito, this.saldoActual);
            }
            return;
        }
        
        // Confirmar pago
        Swal.fire({
            icon: 'question',
            title: '¿Confirmar Pago?',
            html: `
                <p>Total: <strong>$${totalCarrito.toFixed(2)}</strong></p>
                <p>Saldo: <strong>$${this.saldoActual.toFixed(2)}</strong></p>
                <p>Cambio: <strong>$${(this.saldoActual - totalCarrito).toFixed(2)}</strong></p>
            `,
            showCancelButton: true,
            confirmButtonText: 'Confirmar Pago',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                // Procesar venta
                this.finalizarVenta(totalCarrito);
            }
        });
    },
    
    /**
     * Finaliza la venta con monedas
     */
    finalizarVenta: function(total) {
        if (typeof Cart === 'undefined') {
            console.error('Cart no está definido');
            return;
        }
        
        const cart = Cart.getItems();
        
        // GUARDAR el saldo actual ANTES de enviar, porque puede cambiar
        const montoPagado = this.saldoActual;
        const cambioCalculado = montoPagado - total;

        if (cambioCalculado > 0 && typeof Cart !== 'undefined' && typeof Cart.verificarCambioEnMonedas === 'function') {
            Cart.verificarCambioEnMonedas(cambioCalculado).then((chk) => {
                if (!chk.disponible) {
                    Cart.mostrarCambioNoDisponible(cambioCalculado, chk);
                    return;
                }
                this._ejecutarFinalizarVenta(total, montoPagado, cambioCalculado, cart);
            });
            return;
        }

        this._ejecutarFinalizarVenta(total, montoPagado, cambioCalculado, cart);
    },

    _ejecutarFinalizarVenta: function(total, montoPagado, cambioCalculado, cart) {
        // Enviar datos al servidor
        fetch('procesar_venta.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                cart: cart,
                metodo_pago: 'efectivo', // Pago en efectivo (monedas)
                tipo_pago: 1, // 1 = EFECTIVO
                monto_pagado: montoPagado,
                cambio: cambioCalculado
            })
        })
        .then(response => response.json())
        .then(async (data) => {
            if (data.success) {
                if (data.ticket_data) {
                    sessionStorage.setItem('ultimo_ticket_data', JSON.stringify(data.ticket_data));
                    if (typeof PrintTicket !== 'undefined' && typeof qz !== 'undefined') {
                        try {
                            await PrintTicket.imprimirTicketVenta(data.ticket_data);
                        } catch (printErr) {
                            console.warn('No se pudo imprimir el ticket:', printErr);
                        }
                    }
                }

                const registrarCobro = (typeof Cart !== 'undefined' && typeof Cart.registrarCobroEnMaquina === 'function')
                    ? Cart.registrarCobroEnMaquina(montoPagado, cambioCalculado)
                    : Promise.resolve();

                registrarCobro.then(() => {
                // ✅ PRIMERO: Limpiar carrito
                if (typeof Cart !== 'undefined' && typeof Cart.clearCart === 'function') {
                    Cart.clearCart();
                }
                
                // ✅ SEGUNDO: Resetear saldo del cliente
                this.resetearSaldo();
                
                // 💰 TERCERO: Dispensar cambio si hay (descuenta del float máquina)
                if (cambioCalculado > 0) {
                    this.dispensarCambioFisico(cambioCalculado).then(() => {
                        // Mostrar éxito después de dispensar
                        this.mostrarExitoVenta(montoPagado, total, cambioCalculado, data.id_pago || data.folio);
                    }).catch(() => {
                        // Mostrar éxito aunque falle el dispensado
                        this.mostrarExitoVenta(montoPagado, total, cambioCalculado, data.id_pago || data.folio);
                    });
                } else {
                    // Sin cambio, mostrar éxito directamente
                    this.mostrarExitoVenta(montoPagado, total, cambioCalculado, data.id_pago || data.folio);
                }
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error en el Pago',
                    text: data.mensaje || 'Ocurrió un error al procesar el pago'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se pudo procesar el pago'
            });
        });
    },
    
    /**
     * Resetea el saldo del monedero
     */
    resetearSaldo: function() {
        // 🔥 ACTUALIZAR INMEDIATAMENTE en UI antes de esperar respuesta
        this.actualizarSaldo(0);
        
        fetch('monedero_api.php?action=reset_saldo', {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Ya actualizado arriba
            } else {
                console.error('❌ Error al limpiar saldo:', data);
            }
        })
        .catch(error => {
            console.error('❌ Error al resetear saldo:', error);
        });
    },
    
    /**
     * 💰 Dispensa cambio físicamente desde el monedero
     */
    dispensarCambioFisico: function(monto, opciones = {}) {
        const silencioso = opciones.silencioso === true;
        return new Promise((resolve, reject) => {
            // Mostrar loading
            Swal.fire({
                title: '💰 Dispensando Cambio',
                html: `
                    <div class="text-center">
                        <div class="spinner-border text-warning mb-3" role="status" style="width: 3rem; height: 3rem;"></div>
                        <p class="fs-5 fw-bold text-warning mb-2">$${monto.toFixed(2)}</p>
                        <p class="text-muted">Por favor espera, la máquina está entregando tu cambio...</p>
                    </div>
                `,
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Enviar comando para dispensar
            fetch('monedero_api.php?action=dispensar_cambio', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `monto=${monto}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('✅ Cambio dispensado:', data);

                    Swal.close();

                    if (!silencioso) {
                        const desgloseHTML = Object.entries(data.desglose || {})
                            .map(([denom, cant]) => `<div class="d-flex justify-content-between mb-2"><span>$${denom}</span><span>×${cant}</span></div>`)
                            .join('');

                        Swal.fire({
                            icon: 'success',
                            title: '💰 Cambio entregado',
                            html: `<p>Total dispensado: <strong>$${monto.toFixed(2)}</strong></p>${desgloseHTML}`,
                            timer: 3000,
                            timerProgressBar: true,
                            showConfirmButton: false
                        });
                    }
                    
                    resolve(data);
                } else {
                    console.error('❌ Error al dispensar:', data.mensaje);
                    Swal.close();
                    
                    // Mostrar error pero no bloquear
                    Swal.fire({
                        icon: 'warning',
                        title: 'Dispensador no disponible',
                        html: `
                            <p>No se pudo dispensar el cambio automáticamente.</p>
                            <div class="alert alert-warning mt-3">
                                <strong>⚠️ Entrega manualmente: $${monto.toFixed(2)}</strong>
                            </div>
                        `,
                        confirmButtonText: 'Entendido',
                        timer: 4000
                    });
                    
                    reject(data);
                }
            })
            .catch(error => {
                console.error('❌ Error en dispensar_cambio:', error);
                Swal.close();
                
                // Error de red, pero no bloquear
                Swal.fire({
                    icon: 'warning',
                    title: 'Dispensador no disponible',
                    text: `Entrega manualmente el cambio: $${monto.toFixed(2)}`,
                    confirmButtonText: 'Entendido',
                    timer: 3000
                });
                
                reject(error);
            });
        });
    },
    
    /**
     * Muestra mensaje de éxito de la venta
     */
    mostrarExitoVenta: function(montoPagado, total, cambio, folio) {
        if (typeof Cart !== 'undefined' && typeof Cart.finalizarCompraUI === 'function') {
            Cart.finalizarCompraUI();
        }
        if (typeof Cart !== 'undefined' && typeof Cart.mostrarRedireccionMenu === 'function') {
            Cart.mostrarRedireccionMenu('index.php');
            return;
        }
        Swal.fire({
            icon: 'success',
            title: '¡Compra exitosa!',
            html: '<p>Redirigiendo al menú...</p>',
            timer: 10000,
            timerProgressBar: true,
            showConfirmButton: false
        }).then(() => {
            window.location.href = 'index.php';
        });
    }
};

// Inicializar cuando el DOM esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        setTimeout(() => MonederoIntegration.init(), 100); // Pequeño delay para asegurar que el DOM esté listo
    });
} else {
    // Si el DOM ya está listo, inicializar inmediatamente
    setTimeout(() => MonederoIntegration.init(), 100);
}
