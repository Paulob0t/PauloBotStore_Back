/**
 * 🚀 PAYMENT HANDLER - Maneja pagos de servicios con Point o Efectivo
 */

class ServicePaymentHandler {
    /**
     * Wrapper de SweetAlert2 con z-index alto para aparecer sobre modales de servicios
     */
    static swal(options) {
        const defaultOptions = {
            customClass: {
                container: 'payment-swal-container'
            },
            didOpen: () => {
                // Asegurar que el contenedor tenga z-index mayor que los modales de servicios (99999)
                const container = document.querySelector('.swal2-container');
                if (container) {
                    container.style.zIndex = '100000';
                }
                // Ejecutar didOpen original si existe
                if (options.didOpen) {
                    options.didOpen();
                }
            }
        };
        
        // Merge de opciones
        const mergedOptions = { ...defaultOptions, ...options };
        
        // Si había un didOpen en options, ya lo llamamos desde nuestro didOpen wrapper
        if (options.didOpen) {
            mergedOptions.didOpen = () => {
                const container = document.querySelector('.swal2-container');
                if (container) {
                    container.style.zIndex = '100000';
                }
                options.didOpen();
            };
        }
        
        return Swal.fire(mergedOptions);
    }

    /**
     * Mostrar modal de selección de método de pago
     * @param {Object} paymentData - Datos del pago
     * @param {string} paymentData.serviceName - Nombre del servicio
     * @param {string} paymentData.reference - Referencia (número, cuenta, etc)
     * @param {number} paymentData.amount - Monto base
     * @param {number} paymentData.commission - Comisión
     * @param {string} paymentData.sku - SKU del servicio
     * @param {Function} onSuccess - Callback cuando el pago es exitoso
     */
    static async showPaymentOptions(paymentData, onSuccess = null) {
        const { serviceName, reference, amount, commission = 0, sku } = paymentData;
        const total = amount + commission;

        // 🪙 Obtener saldo del monedero
        let saldoMonedero = 0;
        try {
            const response = await fetch('monedero_api.php?action=get_saldo');
            const data = await response.json();
            if (data.success) {
                saldoMonedero = parseFloat(data.saldo) || 0;
            }
        } catch (error) {
            // Si falla, continuar sin monedero
        }

        const tieneSaldoSuficiente = saldoMonedero >= total;
        const faltante = total - saldoMonedero;

        const result = await this.swal({
            title: '💳 Método de Pago',
            html: `
                <div style="text-align: left; padding: 20px;">
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                        <p style="margin: 5px 0;"><strong>Servicio:</strong> ${serviceName}</p>
                        <p style="margin: 5px 0;"><strong>Referencia:</strong> ${reference}</p>
                        <p style="margin: 5px 0;"><strong>Monto:</strong> $${amount.toFixed(2)}</p>
                        ${commission > 0 ? `<p style="margin: 5px 0;"><strong>Comisión:</strong> $${commission.toFixed(2)}</p>` : ''}
                        <hr style="margin: 10px 0;">
                        <p style="margin: 5px 0; font-size: 1.2rem;"><strong>Total:</strong> <span style="color: #28a745;">$${total.toFixed(2)}</span></p>
                    </div>
                    
                    ${saldoMonedero > 0 ? `
                        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 12px; border-radius: 8px; margin-bottom: 15px; color: white; text-align: center;">
                            <p style="margin: 0; font-size: 0.9rem; opacity: 0.9;">💰 Saldo Monedero Disponible</p>
                            <p style="margin: 5px 0 0 0; font-size: 1.5rem; font-weight: bold;">$${saldoMonedero.toFixed(2)}</p>
                            ${!tieneSaldoSuficiente ? `
                                <p style="margin: 5px 0 0 0; font-size: 0.85rem; opacity: 0.9;">
                                    ⚠️ Faltan $${faltante.toFixed(2)} - Inserta más monedas
                                </p>
                            ` : `
                                <p style="margin: 5px 0 0 0; font-size: 0.85rem; opacity: 0.9;">
                                    ✅ Saldo suficiente para este servicio
                                </p>
                            `}
                        </div>
                    ` : ''}
                    
                    <p style="text-align: center; color: #666; margin-bottom: 15px;">Selecciona tu método de pago:</p>
                    
                    <div style="display: grid; gap: 10px;">
                        ${saldoMonedero > 0 ? `
                            <button id="pay-with-coins" class="payment-method-btn" style="
                                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                                border: none;
                                padding: 15px;
                                border-radius: 8px;
                                cursor: pointer;
                                font-size: 1.1rem;
                                font-weight: bold;
                                color: white;
                                transition: transform 0.2s;
                                ${!tieneSaldoSuficiente ? 'opacity: 0.6; cursor: not-allowed;' : ''}
                            " ${!tieneSaldoSuficiente ? 'disabled' : ''}>
                                <i class="bi bi-coin" style="font-size: 1.5rem;"></i><br>
                                Pagar con Monedas ($${saldoMonedero.toFixed(2)})
                            </button>
                        ` : ''}
                        
                        <button id="pay-with-point" class="payment-method-btn" style="
                            background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
                            border: none;
                            padding: 15px;
                            border-radius: 8px;
                            cursor: pointer;
                            font-size: 1.1rem;
                            font-weight: bold;
                            color: #000;
                            transition: transform 0.2s;
                        ">
                            <i class="bi bi-credit-card-2-front" style="font-size: 1.5rem;"></i><br>
                            Pagar con Tarjeta (Point)
                        </button>
                        
                        <button id="pay-with-cash" class="payment-method-btn" style="
                            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
                            border: none;
                            padding: 15px;
                            border-radius: 8px;
                            cursor: pointer;
                            font-size: 1.1rem;
                            font-weight: bold;
                            color: white;
                            transition: transform 0.2s;
                        ">
                            <i class="bi bi-cash-coin" style="font-size: 1.5rem;"></i><br>
                            Pagar en Efectivo
                        </button>
                    </div>
                </div>
                
                <style>
                    .payment-method-btn:not(:disabled):hover {
                        transform: translateY(-2px);
                        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
                    }
                </style>
            `,
            showConfirmButton: false,
            showCancelButton: true,
            cancelButtonText: 'Cancelar',
            cancelButtonColor: '#6c757d',
            width: 600,
            didOpen: () => {
                // Event listener para botón Monedas
                const coinsBtn = document.getElementById('pay-with-coins');
                if (coinsBtn) {
                    coinsBtn.addEventListener('click', () => {
                        if (tieneSaldoSuficiente) {
                            Swal.close();
                            this.processWithCoins(paymentData, saldoMonedero, onSuccess);
                        }
                    });
                }

                // Event listener para botón Point
                document.getElementById('pay-with-point').addEventListener('click', () => {
                    Swal.close();
                    this.processWithPoint(paymentData, onSuccess);
                });

                // Event listener para botón Efectivo
                document.getElementById('pay-with-cash').addEventListener('click', () => {
                    Swal.close();
                    this.processWithCash(paymentData, onSuccess);
                });
            }
        });
    }

    /**
     * Procesar pago con Point (Mercado Pago)
     */
    static async processWithPoint(paymentData, onSuccess) {
        const { serviceName, reference, amount, commission = 0, sku } = paymentData;
        const total = amount + commission;

        // Validar monto mínimo ($5 MXN)
        if (total < 5) {
            this.swal({
                icon: 'warning',
                title: 'Monto muy bajo',
                text: 'El monto mínimo para pagar con Point es $5.00 MXN',
                confirmButtonText: 'Entendido'
            });
            return;
        }

        try {
            // Paso 1: Crear payment intent en Point
            this.swal({
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
                    amount: total,
                    description: `${serviceName} - ${reference}`,
                    external_reference: `SERVICE-${sku}-${Date.now()}`
                })
            });

            const createResult = await createResponse.json();

            if (!createResult.success) {
                throw new Error(createResult.error || 'Error al crear payment intent');
            }

            const paymentIntentId = createResult.payment_intent.id;

            // Paso 2: Mostrar que está esperando el pago
            let pollingInterval = null;
            let timerInterval = null;
            let secondsElapsed = 0;

            const paymentPromise = new Promise((resolve, reject) => {
                this.swal({
                    title: '💳 Acerque su Tarjeta',
                    html: `
                        <div style="text-align: center;">
                            <div class="mb-4">
                                <i class="bi bi-credit-card-2-front" style="font-size: 5rem; color: #FFD700; animation: pulse 2s infinite;"></i>
                            </div>
                            <h3 class="text-warning mb-3">$${total.toFixed(2)} MXN</h3>
                            <div class="alert alert-success mb-3" style="font-size: 1.1rem;">
                                <strong>👉 Por favor, acerque su tarjeta al Point</strong>
                            </div>
                            <p class="text-muted">
                                ✓ Acerque, inserte o deslice su tarjeta<br>
                                ✓ Siga las instrucciones en la terminal<br>
                                ⏳ Espere la confirmación
                            </p>
                            <div id="point-timer" class="mt-3 text-muted small">
                                Esperando tarjeta... <span id="timer-seconds">0</span>s
                            </div>
                        </div>
                    `,
                    showCancelButton: true,
                    cancelButtonText: '❌ Cancelar',
                    cancelButtonColor: '#d33',
                    showConfirmButton: false,
                    allowOutsideClick: false,
                    didOpen: () => {
                        // Timer visual
                        timerInterval = setInterval(() => {
                            secondsElapsed++;
                            const timerSpan = document.getElementById('timer-seconds');
                            if (timerSpan) timerSpan.textContent = secondsElapsed;
                        }, 1000);

                        // Polling del estado del pago
                        pollingInterval = setInterval(async () => {
                            try {
                                const statusResponse = await fetch('mercadopago_point.php', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json' },
                                    body: JSON.stringify({
                                        action: 'get_payment_status',
                                        payment_intent_id: paymentIntentId
                                    })
                                });

                                const statusResult = await statusResponse.json();
                                
                                if (statusResult.success && statusResult.payment_intent) {
                                    const status = statusResult.payment_intent.status;
                                    
                                    if (status === 'FINISHED') {
                                        clearInterval(pollingInterval);
                                        clearInterval(timerInterval);
                                        resolve({ status: 'approved', data: statusResult.payment_intent });
                                    } else if (status === 'CANCELED' || status === 'ERROR') {
                                        clearInterval(pollingInterval);
                                        clearInterval(timerInterval);
                                        reject(new Error('Pago cancelado o con error'));
                                    }
                                }
                            } catch (error) {
                                console.error('Error al consultar estado:', error);
                            }
                        }, 1000); // Cada 1 segundo

                        // Timeout de 2 minutos
                        setTimeout(() => {
                            if (pollingInterval) {
                                clearInterval(pollingInterval);
                                clearInterval(timerInterval);
                                reject(new Error('Tiempo de espera agotado'));
                            }
                        }, 120000); // 120 segundos

                        // Cancelación manual
                        const cancelBtn = Swal.getCancelButton();
                        if (cancelBtn) {
                            cancelBtn.onclick = async () => {
                                clearInterval(pollingInterval);
                                clearInterval(timerInterval);
                                
                                try {
                                    await fetch('mercadopago_point.php', {
                                        method: 'POST',
                                        headers: { 'Content-Type': 'application/json' },
                                        body: JSON.stringify({
                                            action: 'cancel_payment',
                                            payment_intent_id: paymentIntentId
                                        })
                                    });
                                } catch (e) {
                                    console.error('Error al cancelar:', e);
                                }

                                Swal.close();
                                
                                this.swal({
                                    icon: 'info',
                                    title: 'Pago Cancelado',
                                    text: 'La operación fue cancelada. El Point está listo para un nuevo pago.',
                                    toast: true,
                                    position: 'top-end',
                                    showConfirmButton: false,
                                    timer: 3000,
                                    timerProgressBar: true
                                });
                                
                                reject(new Error('Cancelado por el usuario'));
                            };
                        }
                    }
                });
            });

            // Esperar resultado del pago
            const paymentResult = await paymentPromise;

            // Pago exitoso - Procesar en backend
            const paymentProcessResult = await this.processServicePayment({
                ...paymentData,
                paymentMethod: 'point',
                pointPaymentId: paymentResult.data.id
            });

            // NO mostrar alerta aquí - dejar que el callback onSuccess lo maneje
            if (onSuccess) onSuccess({ success: true, method: 'point', ...paymentProcessResult });

        } catch (error) {
            console.error('❌ Error en pago Point:', error);
            
            this.swal({
                icon: 'error',
                title: 'Error en el Pago',
                text: error.message || 'No se pudo procesar el pago con Point',
                confirmButtonText: 'Entendido'
            });
        }
    }

    /**
     * Procesar pago en efectivo
     * 💰 ACTUALIZADO: Verifica disponibilidad de cambio y lo dispensa automáticamente
     */
    static async processWithCash(paymentData, onSuccess) {
        const { serviceName, reference, amount, commission = 0, montoPagado = 0 } = paymentData;
        const total = amount + commission;
        const cambioNecesario = montoPagado > 0 ? (montoPagado - total) : 0;
        
        // 💰 Si hay cambio, verificar disponibilidad ANTES de aceptar el pago
        if (cambioNecesario > 0) {
            try {
                const checkResponse = await fetch('monedero_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=check_change_availability&monto=${cambioNecesario}`
                });
                
                const checkResult = await checkResponse.json();
                
                if (!checkResult.success || !checkResult.available) {
                    this.swal({
                        icon: 'error',
                        title: 'Cambio Insuficiente',
                        html: `
                            <div style="text-align: left; padding: 20px;">
                                <p><strong>No hay suficiente cambio disponible</strong></p>
                                <p style="margin: 10px 0;">Cambio necesario: <strong>$${cambioNecesario.toFixed(2)}</strong></p>
                                <p style="margin: 10px 0;">Faltante: <strong>$${checkResult.faltante?.toFixed(2) || cambioNecesario.toFixed(2)}</strong></p>
                                <hr>
                                <p style="font-size: 0.9rem; color: #666;">Por favor, utiliza otro método de pago o monto exacto.</p>
                            </div>
                        `,
                        confirmButtonText: 'Entendido'
                    });
                    return;
                }
            } catch (error) {
                console.error('Error verificando cambio:', error);
                this.swal({
                    icon: 'warning',
                    title: 'No se pudo verificar el cambio',
                    text: 'Continuando sin verificación...',
                    timer: 2000,
                    showConfirmButton: false
                });
            }
        }

        const result = await this.swal({
            title: '💵 Pago en Efectivo',
            html: `
                <div style="text-align: left; padding: 20px;">
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                        <p style="margin: 5px 0;"><strong>Servicio:</strong> ${serviceName}</p>
                        <p style="margin: 5px 0;"><strong>Referencia:</strong> ${reference}</p>
                        <p style="margin: 5px 0; font-size: 1.3rem; color: #28a745;"><strong>Total a pagar:</strong> $${total.toFixed(2)}</p>
                        ${montoPagado > 0 ? `
                            <hr style="margin: 10px 0;">
                            <p style="margin: 5px 0;"><strong>Monto recibido:</strong> $${montoPagado.toFixed(2)}</p>
                            ${cambioNecesario > 0 ? `<p style="margin: 5px 0; color: #dc3545;"><strong>Cambio a devolver:</strong> $${cambioNecesario.toFixed(2)}</p>` : ''}
                        ` : ''}
                    </div>
                    <p style="text-align: center; color: #666;">¿Confirmas que recibiste el pago en efectivo?</p>
                </div>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, confirmar',
            confirmButtonColor: '#28a745',
            cancelButtonText: 'Cancelar',
            cancelButtonColor: '#6c757d'
        });

        if (result.isConfirmed) {
            try {
                this.swal({
                    title: 'Procesando...',
                    text: 'Registrando pago en efectivo',
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    didOpen: () => Swal.showLoading()
                });

                await this.processServicePayment({
                    ...paymentData,
                    paymentMethod: 'cash'
                });

                // 💰 Si hay cambio, dispensarlo automáticamente
                if (cambioNecesario > 0) {
                    this.swal({
                        title: '💵 Dispensando Cambio',
                        html: `
                            <div class="spinner-border text-success mb-3" role="status"></div>
                            <p>Preparando su cambio de <strong>$${cambioNecesario.toFixed(2)}</strong></p>
                            <p class="text-muted small">Por favor espere...</p>
                        `,
                        allowOutsideClick: false,
                        showConfirmButton: false
                    });

                    try {
                        const dispensarResponse = await fetch('monedero_api.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: `action=dispensar_cambio&monto=${cambioNecesario}`
                        });

                        const dispensarResult = await dispensarResponse.json();

                        if (dispensarResult.success) {
                            this.swal({
                                icon: 'success',
                                title: '✅ Cambio Dispensado',
                                html: `
                                    <p style="font-size: 1.2rem; margin: 15px 0;">Cambio: <strong>$${cambioNecesario.toFixed(2)}</strong></p>
                                    <p style="color: #28a745;">Retire su cambio de la bandeja</p>
                                `,
                                confirmButtonText: 'Aceptar',
                                timer: 5000
                            });
                        } else {
                            throw new Error(dispensarResult.mensaje || 'Error al dispensar cambio');
                        }
                    } catch (error) {
                        console.error('Error dispensando cambio:', error);
                        this.swal({
                            icon: 'warning',
                            title: 'Error al Dispensar Cambio',
                            html: `
                                <p><strong>No se pudo dispensar el cambio automáticamente</strong></p>
                                <p style="margin: 10px 0;">Cambio a devolver: <strong>$${cambioNecesario.toFixed(2)}</strong></p>
                                <p style="color: #dc3545;">${error.message}</p>
                                <hr>
                                <p class="text-muted">Por favor, entregue el cambio manualmente</p>
                            `,
                            confirmButtonText: 'Entendido'
                        });
                    }
                }

                Swal.close();

                // NO mostrar alerta aquí - dejar que el callback lo maneje
                if (onSuccess) onSuccess({ success: true, method: 'cash', cambioDispensado: cambioNecesario });

            } catch (error) {
                console.error('Error al procesar pago en efectivo:', error);
                this.swal({
                    icon: 'error',
                    title: 'Error',
                    text: error.message || 'No se pudo procesar el pago',
                    confirmButtonText: 'Entendido'
                });
            }
        }
    }

    /**
     * Procesar el pago del servicio en el backend
     */
    static async processServicePayment(data) {
        // REQUEST simplificado según especificación
        const payload = {
            amount: data.amount,
            reference: data.reference,
            sku: data.sku,
            transacctionId: Date.now()  // Nota: doble 'c' como lo requiere el API
        };

        const response = await fetch('process_payment.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const result = await response.json();

        // Validar respuesta - el PHP retorna { success: true/false, data: {...}, transactionId: ... }
        if (!result.success) {
            throw new Error(result.error || result.message || 'Error al procesar el servicio');
        }

        // 🔥 NUEVO: Verificar status de la venta cada 2 segundos durante 62 segundos
        if (result.transactionId) {
            const statusResult = await this.pollTransactionStatus(result.transactionId, data);
            return statusResult;
        }

        return result;
    }

    /**
     * 🆕 Consultar status de venta cada 2 segundos durante 62 segundos
     * @param {string} transactionId - ID de la transacción
     * @param {object} serviceData - Datos del servicio para guardar como saldo si falla
     * @returns {Promise} - Resultado de la transacción
     */
    static async pollTransactionStatus(transactionId, serviceData) {
        const POLL_INTERVAL = 2000; // 2 segundos
        const MAX_ATTEMPTS = 31; // 31 intentos = 62 segundos
        let attempts = 0;

        // 💬 Modal de carga con contador
        this.swal({
            title: '🔄 Procesando Servicio',
            html: `
                <div style="text-align: center; padding: 20px;">
                    <div class="spinner-border text-warning mb-4" role="status" style="width: 4rem; height: 4rem;"></div>
                    <p style="font-size: 1.2rem; margin-bottom: 15px;">
                        <strong>Verificando transacción...</strong>
                    </p>
                    <div class="alert alert-info" style="margin: 20px 0;">
                        <p style="margin: 5px 0;"><strong>Servicio:</strong> ${serviceData.serviceName || 'N/A'}</p>
                        <p style="margin: 5px 0;"><strong>Referencia:</strong> ${serviceData.reference}</p>
                        <p style="margin: 5px 0;"><strong>Monto:</strong> $${serviceData.amount.toFixed(2)}</p>
                    </div>
                    <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                        <p style="margin: 5px 0; color: #666;">
                            ⏳ Tiempo transcurrido: <span id="elapsed-time" style="font-weight: bold; color: #007bff;">0</span>s
                        </p>
                        <p style="margin: 5px 0; color: #666;">
                            🔍 Intento: <span id="attempt-count" style="font-weight: bold;">0</span>/${MAX_ATTEMPTS}
                        </p>
                    </div>
                    <p style="margin-top: 15px; font-size: 0.9rem; color: #999;">
                        Por favor espere mientras verificamos el status de su transacción...
                    </p>
                </div>
            `,
            allowOutsideClick: false,
            showConfirmButton: false,
            showCancelButton: false
        });

        return new Promise((resolve, reject) => {
            const pollInterval = setInterval(async () => {
                attempts++;
                
                // Actualizar UI
                const elapsedTime = document.getElementById('elapsed-time');
                const attemptCount = document.getElementById('attempt-count');
                if (elapsedTime) elapsedTime.textContent = (attempts * 2);
                if (attemptCount) attemptCount.textContent = attempts;

                try {
                    // Consultar status
                    const statusResponse = await fetch('check_transaction_status.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ transactionId })
                    });

                    const statusResult = await statusResponse.json();

                    // 📝 Imprimir response en consola
                    console.log(`[⏱️ ${attempts * 2}s] Status Response:`, statusResult);

                    // ✅ Transacción exitosa
                    if (statusResult.success && statusResult.status === 'completed') {
                        clearInterval(pollInterval);
                        Swal.close();
                        
                        // NO mostrar alerta aquí - dejar que el callback lo maneje
                        resolve({ success: true, ...statusResult });
                        return;
                    }

                    // ❌ Transacción fallida
                    if (statusResult.status === 'failed' || statusResult.status === 'error') {
                        clearInterval(pollInterval);
                        Swal.close();
                        
                        // 💰 Guardar como saldo para reintentar
                        await this.saveAsBalance(serviceData);
                        
                        const retryResult = await this.swal({
                            icon: 'warning',
                            title: '⚠️ Transacción Fallida',
                            html: `
                                <div>
                                    <p>La transacción no se completó exitosamente.</p>
                                    <div class="alert alert-info mt-3">
                                        <strong>💰 Se ha guardado tu pago como saldo disponible</strong><br>
                                        Puedes reintentar la compra o usar el saldo para otro servicio.
                                    </div>
                                    <p style="margin-top: 15px;"><strong>Detalles del error:</strong></p>
                                    <pre style="background: #f5f5f5; padding: 10px; border-radius: 5px; text-align: left; font-size: 0.85rem;">${JSON.stringify(statusResult, null, 2)}</pre>
                                </div>
                            `,
                            showCancelButton: true,
                            confirmButtonText: '🔄 Reintentar',
                            cancelButtonText: '🛍️ Usar Saldo en Otro Servicio',
                            confirmButtonColor: '#007bff',
                            cancelButtonColor: '#28a745'
                        });

                        if (retryResult.isConfirmed) {
                            // Reintentar la compra
                            window.location.reload();
                        } else if (retryResult.dismiss === Swal.DismissReason.cancel) {
                            // Redirigir a servicios
                            document.getElementById('servicios-btn')?.click();
                        }
                        
                        reject(new Error('Transacción fallida'));
                        return;
                    }

                    // ⏳ Continuar esperando si aún está pendiente
                    if (attempts >= MAX_ATTEMPTS) {
                        // ⏰ TIMEOUT - 62 segundos alcanzados
                        clearInterval(pollInterval);
                        Swal.close();
                        
                        // 💰 Guardar como saldo
                        await this.saveAsBalance(serviceData);
                        
                        const timeoutResult = await this.swal({
                            icon: 'warning',
                            title: '⏰ Tiempo de Espera Agotado',
                            html: `
                                <div>
                                    <p>No se pudo verificar el status de la transacción en 62 segundos.</p>
                                    <div class="alert alert-info mt-3">
                                        <strong>💰 Tu pago se ha guardado como saldo disponible</strong><br>
                                        Puedes verificar el status más tarde o usar el saldo para otra compra.
                                    </div>
                                    <p style="margin-top: 10px;"><strong>Folio:</strong> ${transactionId}</p>
                                    <p style="font-size: 0.9rem; color: #666;">Consulta el status con este folio más tarde.</p>
                                </div>
                            `,
                            showCancelButton: true,
                            confirmButtonText: '🔄 Verificar Ahora',
                            cancelButtonText: '🛍️ Ir a Servicios',
                            confirmButtonColor: '#007bff',
                            cancelButtonColor: '#28a745'
                        });

                        if (timeoutResult.isConfirmed) {
                            // Reintentar verificación
                            resolve(await this.pollTransactionStatus(transactionId, serviceData));
                        } else {
                            // Ir a servicios
                            document.getElementById('servicios-btn')?.click();
                            reject(new Error('Timeout - Saldo guardado'));
                        }
                    }

                } catch (error) {
                    console.error('Error al consultar status:', error);
                    
                    if (attempts >= MAX_ATTEMPTS) {
                        clearInterval(pollInterval);
                        Swal.close();
                        
                        // Guardar como saldo en caso de error
                        await this.saveAsBalance(serviceData);
                        
                        this.swal({
                            icon: 'error',
                            title: 'Error al Verificar Status',
                            html: `
                                <p>No se pudo verificar el status de la transacción.</p>
                                <div class="alert alert-info mt-3">
                                    <strong>💰 Tu pago se guardó como saldo disponible</strong>
                                </div>
                            `,
                            confirmButtonText: 'Entendido'
                        });
                        
                        reject(error);
                    }
                }
            }, POLL_INTERVAL);
        });
    }

    /**
     * 💰 Guardar pago como saldo disponible para reintentar
     * @param {object} serviceData - Datos del servicio
     */
    static async saveAsBalance(serviceData) {
        try {
            // Obtener saldo actual del localStorage
            let availableBalance = parseFloat(localStorage.getItem('available_balance') || '0');
            
            // Agregar el monto de esta transacción
            const amount = serviceData.amount + (serviceData.commission || 0);
            availableBalance += amount;
            
            // Guardar nuevo saldo
            localStorage.setItem('available_balance', availableBalance.toString());
            
            // Guardar historial de saldo
            const balanceHistory = JSON.parse(localStorage.getItem('balance_history') || '[]');
            balanceHistory.push({
                date: new Date().toISOString(),
                amount: amount,
                serviceName: serviceData.serviceName,
                reference: serviceData.reference,
                reason: 'Transacción no completada'
            });
            localStorage.setItem('balance_history', JSON.stringify(balanceHistory));
            
            console.log(`💰 Saldo guardado: $${amount.toFixed(2)} - Saldo total: $${availableBalance.toFixed(2)}`);
            
            // Actualizar UI si existe indicador de saldo
            const balanceIndicator = document.getElementById('balance-indicator');
            if (balanceIndicator) {
                balanceIndicator.textContent = `$${availableBalance.toFixed(2)}`;
            }
            
        } catch (error) {
            console.error('Error al guardar saldo:', error);
        }
    }

    /**
     * 🪙 Procesar pago con monedas del monedero
     */
    static async processWithCoins(paymentData, saldoMonedero, onSuccess) {
        const { serviceName, reference, amount, commission = 0, sku } = paymentData;
        const total = amount + commission;
        const cambio = saldoMonedero - total;

        // Confirmar pago
        const confirmResult = await this.swal({
            icon: 'question',
            title: '🪙 Confirmar Pago con Monedas',
            html: `
                <div style="text-align: left; padding: 15px;">
                    <div style="background: #f8f9fa; padding: 12px; border-radius: 8px; margin-bottom: 15px;">
                        <p style="margin: 5px 0;"><strong>Servicio:</strong> ${serviceName}</p>
                        <p style="margin: 5px 0;"><strong>Referencia:</strong> ${reference}</p>
                    </div>
                    <table style="width: 100%; margin: 15px 0;">
                        <tr>
                            <td style="padding: 8px 0;"><strong>Total a pagar:</strong></td>
                            <td style="text-align: right; font-size: 1.2rem;">$${total.toFixed(2)}</td>
                        </tr>
                        <tr>
                            <td style="padding: 8px 0;"><strong>Tu saldo:</strong></td>
                            <td style="text-align: right; color: #764ba2; font-size: 1.2rem;">$${saldoMonedero.toFixed(2)}</td>
                        </tr>
                        <tr style="border-top: 2px solid #dee2e6;">
                            <td style="padding: 8px 0;"><strong>Cambio:</strong></td>
                            <td style="text-align: right; color: #28a745; font-size: 1.3rem; font-weight: bold;">$${cambio.toFixed(2)}</td>
                        </tr>
                    </table>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: '✅ Confirmar Pago',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#764ba2',
            cancelButtonColor: '#6c757d'
        });

        if (!confirmResult.isConfirmed) {
            return;
        }

        // Procesar servicio
        this.swal({
            title: 'Procesando...',
            html: '<div class="spinner-border text-primary mb-3"></div><p>Procesando tu recarga</p>',
            allowOutsideClick: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        try {
            // Enviar request al backend con tipo de pago = monedas
            const response = await fetch('process_payment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'pay_with_coins',
                    sku: sku,
                    reference: reference,
                    amount: amount,
                    commission: commission,
                    total: total,
                    saldo_usado: total,
                    cambio: cambio,
                    service_name: serviceName
                })
            });

            const result = await response.json();

            if (result.success) {
                // Resetear saldo del monedero después del pago exitoso
                await fetch('monedero_api.php?action=reset_saldo', { method: 'POST' });

                Swal.close();

                // NO mostrar alerta aquí - dejar que el callback lo maneje
                if (onSuccess && typeof onSuccess === 'function') {
                    onSuccess({ success: true, method: 'coins', cambio: cambio, result: result });
                    onSuccess(result);
                }

            } else {
                throw new Error(result.message || 'Error al procesar el pago');
            }

        } catch (error) {
            this.swal({
                icon: 'error',
                title: 'Error en el Pago',
                text: error.message || 'No se pudo procesar el pago con monedas',
                confirmButtonText: 'Entendido'
            });
        }
    }
}

// Exportar al objeto window para que los servicios puedan acceder
window.ServicePaymentHandler = ServicePaymentHandler;
