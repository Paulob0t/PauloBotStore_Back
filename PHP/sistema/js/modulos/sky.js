let skyServices = [];

export class SkyService {
    static async init() {
        try {
            await this.loadServices();

            document.addEventListener('viewChange', (e) => {
                const container = document.getElementById('products-container');
                if (e.detail.view === 'servicios') {
                    this.renderServicesContainer();
                    this.setupEventListeners();
                }
            });

            if (document.querySelector('#servicios-btn.active')) {
                this.renderServicesContainer();
                this.setupEventListeners();
            }
        } catch (error) {
            console.error('Error al inicializar servicios Sky:', error);
            Swal.fire({
                title: 'Error',
                text: 'No se pudieron cargar los servicios de Sky',
                icon: 'error'
            });
        }
    }

    static async loadServices() {
        skyServices = [
            {
                sku: 'SKYTV',
                formatted_name: 'TV Sky',
                description: 'Pago de servicio de televisión Sky'
            },
            {
                sku: 'SKYINT',
                formatted_name: 'Sky Internet',
                description: 'Pago de servicio de internet Sky'
            }
        ];
    }

    static renderServicesContainer() {
        const container = document.getElementById('products-container');
        if (!container || container.querySelector('.sky-service-card')) return;

        const serviceCard = document.createElement('div');
        serviceCard.className = 'col-lg-6 col-md-6 col-12 sky-service-card';
        serviceCard.innerHTML = `
            <div class="card product-card" style="margin: 10px; border-radius: 15px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.1); height: 700px;">
                <div style="position: relative;">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/7/74/SKY_Basic_Logo.svg/1920px-SKY_Basic_Logo.svg.png" 
                         alt="Sky" 
                         style="width: 100%; height: 100px; object-fit: contain; padding: 20px;">
                </div>
                <div class="card-body" style="padding: 20px; display: flex; flex-direction: column; gap: 15px;">
                    <h3 class="product-title" style="margin-bottom: 10px; font-size: 1.2rem; font-weight: 600;">
                        Servicios Sky
                    </h3>

                    <div class="service-list" style="max-height: 200px; overflow-y: auto; margin-bottom: 15px;">
                        ${this.renderServiceList(skyServices)}
                    </div>
                    
                    <div class="input-section">
                        <div class="number-input">
                            <input type="text" id="sky-service-number" placeholder="Número de cliente" 
                                   style="width: 100%; padding: 10px; border: 2px solid #f1f1f1; border-radius: 5px; margin-bottom: 10px;"
                                   pattern="[0-9]*" inputmode="numeric" ${skyServices.length === 0 ? 'disabled' : ''}>
                            <div id="sky-number-error" style="color: #ff6b6b; font-size: 0.8rem; margin-top: 5px; display: none;">
                                Número de cliente inválido (8-12 dígitos)
                            </div>
                        </div>
                        <div class="amount-input" style="margin-top: 10px;">
                            <input type="number" id="sky-amount" placeholder="Monto a pagar" 
                                   style="width: 100%; padding: 10px; border: 2px solid #f1f1f1; border-radius: 5px;"
                                   min="0" step="0.01" ${skyServices.length === 0 ? 'disabled' : ''}>
                            <div id="sky-amount-error" style="color: #ff6b6b; font-size: 0.8rem; margin-top: 5px; display: none;">
                                Monto inválido (debe ser mayor a 0)
                            </div>
                        </div>
                        <div class="commission-info" style="font-size: 0.85em; color: #666; margin-top: 5px; text-align: right;">
                            Comisión: $8.00
                        </div>
                    </div>
                    <button class="btn" id="process-sky" style="width: 100%; background: ${skyServices.length === 0 ? '#999999' : '#cccccc'}; color: white; padding: 12px; border: none; border-radius: 5px; font-weight: 600; margin-top: 15px; cursor: ${skyServices.length === 0 ? 'not-allowed' : 'pointer'};" ${skyServices.length === 0 ? 'disabled' : ''}>
                        ${skyServices.length === 0 ? 'Servicio no disponible' : 'Procesar Pago'}
                    </button>
                </div>
            </div>
        `;
        container.appendChild(serviceCard);
    }

    static renderServiceList(services) {
        if (!services || services.length === 0) {
            return '<p style="text-align: center; padding: 20px;">No hay servicios disponibles</p>';
        }

        return services.map(service => `
            <div class="service-item" data-sku="${service.sku}" 
                 style="padding: 10px; border: 1px solid #f1f1f1; margin-bottom: 8px; border-radius: 5px; cursor: pointer; transition: all 0.3s ease;">
                <div class="service-info">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div class="service-name" style="font-weight: 500;">${service.formatted_name}</div>
                    </div>
                    <div style="font-size: 0.9em; color: #666; margin-top: 5px;">
                        ${service.description}
                    </div>
                </div>
            </div>
        `).join('');
    }

    static validateFields() {
        if (skyServices.length === 0) return;

        const serviceNumberInput = document.getElementById('sky-service-number');
        const amountInput = document.getElementById('sky-amount');
        const serviceNumberError = document.getElementById('sky-number-error');
        const amountError = document.getElementById('sky-amount-error');
        const processButton = document.getElementById('process-sky');
        const selectedService = document.querySelector('.sky-service-card .service-item.selected');

        if (!serviceNumberInput || !amountInput || !processButton) return;

        const serviceNumber = serviceNumberInput.value.trim();
        const amount = parseFloat(amountInput.value);

        const isValidServiceNumber = /^\d{8,12}$/.test(serviceNumber);
        const isValidAmount = !isNaN(amount) && amount > 0;

        serviceNumberError.style.display = serviceNumber && !isValidServiceNumber ? 'block' : 'none';
        amountError.style.display = amountInput.value && !isValidAmount ? 'block' : 'none';

        serviceNumberInput.style.borderColor = serviceNumber
            ? (isValidServiceNumber ? '#4CAF50' : '#ff6b6b')
            : '#f1f1f1';

        amountInput.style.borderColor = amountInput.value
            ? (isValidAmount ? '#4CAF50' : '#ff6b6b')
            : '#f1f1f1';

        const isValidForm = isValidServiceNumber && isValidAmount && selectedService;
        processButton.disabled = !isValidForm;
        processButton.style.backgroundColor = processButton.disabled ? '#cccccc' : '#FF0000';
        processButton.style.cursor = processButton.disabled ? 'not-allowed' : 'pointer';
    }

    static setupEventListeners() {
        if (skyServices.length === 0) return;

        const serviceNumberInput = document.getElementById('sky-service-number');
        const amountInput = document.getElementById('sky-amount');
        const processButton = document.getElementById('process-sky');

        document.querySelectorAll('.sky-service-card .service-item').forEach(item => {
            item.addEventListener('click', () => {
                document.querySelectorAll('.sky-service-card .service-item').forEach(i => {
                    i.classList.remove('selected');
                    i.style.backgroundColor = '';
                });
                item.classList.add('selected');
                item.style.backgroundColor = '#E7F7EB';
                this.validateFields();
            });
            item.addEventListener('mouseenter', function() {
                if (!this.classList.contains('selected')) {
                    this.style.backgroundColor = '#f8f9fa';
                }
            });
            
            item.addEventListener('mouseleave', function() {
                if (!this.classList.contains('selected')) {
                    this.style.backgroundColor = '';
                }
            });
        });

        if (serviceNumberInput && amountInput) {
            serviceNumberInput.addEventListener('input', () => this.validateFields());
            amountInput.addEventListener('input', () => this.validateFields());
        }

        if (processButton) {
            processButton.addEventListener('click', () => this.processPayment());
        }

        this.validateFields();
    }

    static async processPayment() {
        console.log('Iniciando proceso de pago Sky');
        if (!skyServices.length) {
            console.error('No hay servicios de Sky disponibles');
            Swal.fire('Error', 'El servicio de Sky no está disponible en este momento', 'error');
            return;
        }

        const selectedService = document.querySelector('.sky-service-card .service-item.selected');
        if (!selectedService) {
            console.error('No se ha seleccionado ningún servicio');
            Swal.fire('Error', 'Por favor selecciona un servicio', 'error');
            return;
        }

        const sku = selectedService.dataset.sku;
        const serviceName = selectedService.querySelector('.service-name').textContent;
        
        const serviceNumber = document.getElementById('sky-service-number')?.value.trim();
        const amount = parseFloat(document.getElementById('sky-amount')?.value);
        const totalAmount = amount + 8; // Comisión de $8

        console.log('Datos de pago:', { sku, serviceName, serviceNumber, amount, totalAmount });

        if (!serviceNumber || isNaN(amount)) {
            console.error('Campos incompletos o inválidos');
            Swal.fire('Error', 'Por favor completa todos los campos correctamente', 'error');
            return;
        }

        const result = await Swal.fire({
            title: '¿Confirmar pago?',
            html: `
                <div style="text-align: left; padding: 10px;">
                    <p><strong>Servicio:</strong> ${serviceName}</p>
                    <p><strong>Número de cliente:</strong> ${serviceNumber}</p>
                    <p><strong>Monto:</strong> $${amount.toFixed(2)}</p>
                    <p><strong>Comisión:</strong> $8.00</p>
                    <p><strong>Total a pagar:</strong> $${totalAmount.toFixed(2)}</p>
                </div>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#FF0000',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Confirmar',
            cancelButtonText: 'Cancelar'
        });

        if (!result.isConfirmed) {
            console.log('Pago cancelado por el usuario');
            return;
        }

        Swal.fire({
            title: 'Procesando pago...',
            text: 'Por favor espere...',
            allowOutsideClick: false,
            allowEscapeKey: false,
            allowEnterKey: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        try {
            console.log('Enviando solicitud de pago al servidor...');
            const response = await fetch('sky_saldo_functional.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    numero_cliente: serviceNumber,
                    monto: amount,
                    sku: sku,
                    accion: 'pagar'
                })
            });

            const data = await response.json();
            console.log('Respuesta del servidor:', data);
            
            if (!data.success) {
                throw new Error(data.error || 'Error al procesar el pago');
            }

            Swal.fire({
                title: '¿Confirmar servicio?',
                html: `
                    <div style="text-align: left; padding: 10px;">
                        <p><strong>Servicio:</strong> ${serviceName}</p>
                        <p><strong>Número de cliente:</strong> ${serviceNumber}</p>
                        <p><strong>Monto:</strong> $${amount.toFixed(2)}</p>
                        <p><strong>Comisión:</strong> $8.00</p>
                        <p><strong>Total a pagar:</strong> $${totalAmount.toFixed(2)}</p>
                    </div>
                `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#FF0000',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Confirmar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Procesando pago...',
                        text: 'Por favor espere...',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        allowEnterKey: false,
                        showConfirmButton: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    console.log('Iniciando proceso de pago...');
                    console.log('Datos del servicio:', {
                        sku: sku,
                        amount: amount,
                        reference: serviceNumber,
                        serviceName: serviceName
                    });

///////////////////////////////////////////// Procesar el pago///////////////////////////////////////////////
                    const payParams = new URLSearchParams({ comprar: '1', svc: sku, ref: serviceNumber, amount });
                    fetch(`prontipagos_proxy.php?${payParams}`, {
                        headers: {
                            'Accept': 'application/json'
                        },
                        cache: 'no-store'
                    })
                    .then(response => {
                        console.log('Respuesta del servidor:', {
                            status: response.status,
                            statusText: response.statusText
                        });
                        return response.json();
                    })
                    .then(data => {
                        console.log('Datos de respuesta:', data);
                        
                        if (data.codeTransaction === '00') {
                            console.log('Pago exitoso:', data);
                            Swal.fire({
                                title: '¡Pago Exitoso!',
                                html: `
                                    <div style="text-align: left;">
                                        <p><strong>Servicio:</strong> ${serviceName}</p>
                                        <p><strong>Número de cliente:</strong> ${serviceNumber}</p>
                                        <p><strong>Monto:</strong> $${amount.toFixed(2)}</p>
                                        <p><strong>Folio:</strong> ${data.transactionId}</p>
                                    </div>
                                `,
                                icon: 'success'
                            }).then(() => {
                                document.getElementById('sky-service-number').value = '';
                                document.getElementById('sky-amount').value = '';
                                document.querySelectorAll('.sky-service-card .service-item').forEach(i => {
                                    i.classList.remove('selected');
                                    i.style.backgroundColor = '';
                                });
                                document.getElementById('process-sky').disabled = true;
                            });
                        } else {
                            console.error('Error en respuesta:', data);
                            let errorMessage = data.codeDescription || 'Error al procesar el pago';
                            throw new Error(errorMessage);
                        }
                    })
                    .catch(error => {
                        console.error('Error en la transacción:', error);
                        Swal.fire({
                            title: 'Error en la Transacción',
                            html: `
                                <div style="text-align: left;">
                                    <p>Ocurrió un error al procesar el pago:</p>
                                    <p>${error.message}</p>
                                    <p>Por favor, intenta nuevamente o contacta a soporte.</p>
                                </div>
                            `,
                            icon: 'error'
                        });
                    });
                }
            });

        } catch (error) {
            console.error('Error en el proceso:', error);
            Swal.fire({
                title: 'Error',
                html: `
                    <div style="text-align: left;">
                        <p>Ocurrió un error:</p>
                        <p>${error.message}</p>
                    </div>
                `,
                icon: 'error'
            });
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    setTimeout(() => SkyService.init(), 100);
});
