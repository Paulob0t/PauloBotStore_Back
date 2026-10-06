let telcelServices = {
    tae: null,
    paquetes: null,
    internet: null,
    postpago: null,
    monederoSaldo: 0,
    saldoInterval: null,
    loadingServices: false,
    hapticEnabled: 'vibrate' in navigator
};

// Montos predefinidos para recargas TAE
const TAE_AMOUNTS = [
    { amount: 20, label: 'Recarga $20' },
    { amount: 30, label: 'Recarga $30' },
    { amount: 50, label: 'Recarga $50' },
    { amount: 100, label: 'Recarga $100' },
    { amount: 150, label: 'Recarga $150' },
    { amount: 200, label: 'Recarga $200' },
    { amount: 300, label: 'Recarga $300' },
    { amount: 500, label: 'Recarga $500' },
];

export class TelcelServices {
    static async init() {
        try {
            this.render();
            this.wire();
            this.obtenerSaldoMonedero();
            this.iniciarActualizacionSaldo();
        } catch (e) {
            Swal.fire('Error', 'No se pudieron cargar los servicios de Telcel', 'error');
        }
    }

    static async obtenerSaldoMonedero() {
        try {
            const response = await fetch('monedero_api.php?action=get_saldo');
            const data = await response.json();
            if (data.success) {
                const nuevoSaldo = parseFloat(data.saldo) || 0;
                telcelServices.monederoSaldo = nuevoSaldo;
                this.actualizarBadgeSaldo();
            }
        } catch (error) {
            telcelServices.monederoSaldo = 0;
            this.actualizarBadgeSaldo();
        }
    }

    static actualizarBadgeSaldo() {
        const badgeElement = document.getElementById('telcel-saldo');
        if (badgeElement) {
            const saldoAnterior = parseFloat(badgeElement.textContent.replace(/[^0-9.]/g, '')) || 0;
            const saldoNuevo = telcelServices.monederoSaldo;
            
            // Animación de conteo progresivo solo para el badge de Telcel
            if (Math.abs(saldoNuevo - saldoAnterior) > 0.01) {
                const duracion = 500; // duración en ms
                const inicio = performance.now();
                const diferencia = saldoNuevo - saldoAnterior;
                
                const animar = (tiempoActual) => {
                    const progreso = Math.min((tiempoActual - inicio) / duracion, 1);
                    const valorActual = saldoAnterior + (diferencia * progreso);
                    
                    badgeElement.textContent = `$${valorActual.toFixed(2)}`;
                    
                    if (progreso < 1) {
                        requestAnimationFrame(animar);
                    } else {
                        badgeElement.textContent = `$${saldoNuevo.toFixed(2)}`;
                        
                        // Efecto de escala al finalizar
                        badgeElement.style.transform = 'scale(1.2)';
                        badgeElement.style.transition = 'transform 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55)';
                        setTimeout(() => {
                            badgeElement.style.transform = 'scale(1)';
                        }, 300);
                    }
                };
                
                requestAnimationFrame(animar);
            } else {
                badgeElement.textContent = `$${saldoNuevo.toFixed(2)}`;
            }
        }
        
        // SOLO actualizar el saldo interno del MonederoIntegration
        // NO tocar sus elementos del DOM - MonederoIntegration se encarga de eso
        if (typeof MonederoIntegration !== 'undefined') {
            // Solo actualizar el valor interno si cambió
            if (MonederoIntegration.saldoActual !== telcelServices.monederoSaldo) {
                MonederoIntegration.saldoActual = telcelServices.monederoSaldo;
            }
        }
    }

    static async descontarSaldo(monto) {
        try {
            // Calcular nuevo saldo
            const nuevoSaldo = Math.max(0, telcelServices.monederoSaldo - monto);
            
            // 🔥 ACTUALIZAR INMEDIATAMENTE en UI ANTES de esperar backend
            telcelServices.monederoSaldo = nuevoSaldo;
            this.actualizarBadgeSaldo();
            
            // Actualizar MonederoIntegration inmediatamente
            if (typeof MonederoIntegration !== 'undefined') {
                MonederoIntegration.actualizarSaldo(nuevoSaldo);
            }
            
            // 🔔 DISPARAR EVENTO para que todo se sincronice
            window.dispatchEvent(new CustomEvent('monederoSaldoChanged', {
                detail: { saldo: nuevoSaldo, diferencia: -monto }
            }));
            
            // Establecer el nuevo saldo en el backend (async en background)
            const response = await fetch('monedero_api.php?action=set_saldo', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `saldo=${nuevoSaldo}`
            });
            const data = await response.json();
            
            if (!data.success) {
                console.error('Error al actualizar saldo en backend:', data);
            }
        } catch (error) {
            console.error('Error al descontar saldo:', error);
        }
    }

    static iniciarActualizacionSaldo() {
        if (telcelServices.saldoInterval) {
            clearInterval(telcelServices.saldoInterval);
        }
        
        // Sincronizar con MonederoIntegration en lugar de hacer polling independiente
        telcelServices.saldoInterval = setInterval(() => {
            // Si existe MonederoIntegration, usar su saldo directamente
            if (typeof MonederoIntegration !== 'undefined') {
                const nuevoSaldo = MonederoIntegration.saldoActual;
                
                // Solo actualizar si el saldo cambió
                if (nuevoSaldo !== telcelServices.monederoSaldo) {
                    telcelServices.monederoSaldo = nuevoSaldo;
                    this.actualizarBadgeSaldo();
                    this.marcarServiciosInsuficientes();
                }
            } else {
                // Fallback: si no existe MonederoIntegration, hacer polling normal
                this.obtenerSaldoMonedero().then(() => {
                    this.marcarServiciosInsuficientes();
                });
            }
        }, 200); // Chequeo rápido para estar sincronizado
    }

    static detenerActualizacionSaldo() {
        if (telcelServices.saldoInterval) {
            clearInterval(telcelServices.saldoInterval);
            telcelServices.saldoInterval = null;
        }
    }

    // 🎨 Helper: Mostrar mensaje de saldo insuficiente
    static mostrarSaldoInsuficiente(price) {
        const comision = 2;
        const total = price + comision;
        const faltante = total - telcelServices.monederoSaldo;
        Swal.fire({
            title: '💰 Saldo insuficiente',
            html: `
                <div style="text-align: center; padding: 1rem;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">💸</div>
                    <p style="font-size: 1.1rem; margin-bottom: 0.5rem;">
                        Monto recarga: <strong style="color: #0049a8;">$${price.toFixed(2)}</strong>
                    </p>
                    <p style="font-size: 0.95rem; margin-bottom: 1rem; color: #6b7280;">
                        Comisión: <strong>+$${comision.toFixed(2)}</strong>
                    </p>
                    <hr style="margin: 1rem 0; border: none; border-top: 2px solid #e5e7eb;">
                    <p style="font-size: 1.2rem; margin-bottom: 1rem;">
                        Total necesario: <strong style="color: #dc2626;">$${total.toFixed(2)}</strong>
                    </p>
                    <p style="font-size: 1rem; color: #6b7280; margin-bottom: 1rem;">
                        Tu saldo actual: <strong style="color: #7c3aed;">$${telcelServices.monederoSaldo.toFixed(2)}</strong>
                    </p>
                    <p style="font-size: 1.2rem; font-weight: 700; color: #f59e0b;">
                        Te faltan: $${faltante.toFixed(2)}
                    </p>
                    <div style="margin-top: 1.5rem; padding: 1rem; background: #fef3c7; border-radius: 8px;">
                        <p style="margin: 0; color: #92400e;">
                            🪙 Inserta más monedas o billetes
                        </p>
                    </div>
                </div>
            `,
            icon: 'warning',
            confirmButtonText: 'Entendido',
            confirmButtonColor: '#0049a8'
        });
    }

    // 🎉 Helper: Mostrar mensaje de recarga exitosa
    static async mostrarRecargaExitosa(name, phone, price, codeDesc = null) {
        // Vibración de éxito
        this.hapticFeedback('success');
        
        const comision = 2;
        const totalCobrado = price + comision;
        const saldoInicial = telcelServices.monederoSaldo + totalCobrado; // Saldo antes de descontar
        const cambio = saldoInicial - totalCobrado;
        
        await Swal.fire({
            title: '✅ ¡Recarga exitosa!',
            html: `
                <div style="text-align: left; padding: 1.5rem; background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border-radius: 12px; border: 2px solid #86efac;">
                    <div style="text-align: center; margin-bottom: 1.5rem;">
                        <div style="font-size: 4rem; animation: bounce 1s ease;">📱</div>
                    </div>
                    <p style="margin: 0.75rem 0; font-size: 1.05rem;"><strong>📱 Servicio:</strong> ${name}</p>
                    <p style="margin: 0.75rem 0; font-size: 1.05rem;"><strong>📞 Número:</strong> <span style="color: #0049a8;">${phone}</span></p>
                    ${codeDesc ? `<p style="margin: 0.75rem 0; font-size: 0.95rem; color: #6b7280;"><strong>Estado:</strong> ${codeDesc}</p>` : ''}
                    <hr style="margin: 1.5rem 0; border: none; border-top: 2px dashed #86efac;">
                    <p style="margin: 0.75rem 0; font-size: 1.2rem;"><strong>💰 Saldo insertado:</strong> <span style="color: #7c3aed; font-weight: 700;">$${saldoInicial.toFixed(2)}</span></p>
                    <p style="margin: 0.75rem 0; font-size: 1.2rem;"><strong>💳 Costo recarga:</strong> <span style="color: #dc2626; font-weight: 700;">-$${price.toFixed(2)}</span></p>
                    <p style="margin: 0.75rem 0; font-size: 1rem; color: #6b7280;"><strong>🏷️ Comisión:</strong> <span style="color: #f59e0b; font-weight: 600;">-$${comision.toFixed(2)}</span></p>
                    <hr style="margin: 1.5rem 0; border: none; border-top: 3px solid #22c55e;">
                    <p style="margin: 0.75rem 0; font-size: 1.5rem; text-align: center;"><strong>💵 Tu cambio:</strong> <span style="color: #10b981; font-weight: 800;">$${cambio.toFixed(2)}</span></p>
                </div>
            `,
            icon: 'success',
            confirmButtonText: 'Perfecto',
            confirmButtonColor: '#0049a8',
            timer: 5000,
            timerProgressBar: true
        });
        
        // 💸 DESCONTAR saldo DESPUÉS de mostrar alerta
        await this.descontarSaldo(totalCobrado);
        
        // Confetti effect
        this.showConfetti();
    }

    // 🔔 Helper: Vibración háptica
    static hapticFeedback(type = 'light') {
        if (!telcelServices.hapticEnabled) return;
        
        try {
            switch(type) {
                case 'light':
                    navigator.vibrate(10);
                    break;
                case 'medium':
                    navigator.vibrate(20);
                    break;
                case 'heavy':
                    navigator.vibrate([30, 10, 30]);
                    break;
                case 'success':
                    navigator.vibrate([20, 10, 20, 10, 40]);
                    break;
                case 'error':
                    navigator.vibrate([50, 30, 50]);
                    break;
            }
        } catch (e) {
            // Vibration not supported
        }
    }

    // 🎊 Helper: Efecto confetti
    static showConfetti() {
        const colors = ['#0049a8', '#0066cc', '#00d4ff', '#10b981', '#f59e0b'];
        const confettiCount = 50;
        
        for (let i = 0; i < confettiCount; i++) {
            setTimeout(() => {
                const confetti = document.createElement('div');
                confetti.className = 'confetti';
                confetti.style.left = Math.random() * 100 + '%';
                confetti.style.background = colors[Math.floor(Math.random() * colors.length)];
                confetti.style.animationDelay = Math.random() * 0.5 + 's';
                confetti.style.animationDuration = (Math.random() * 2 + 2) + 's';
                document.body.appendChild(confetti);
                
                setTimeout(() => confetti.remove(), 3000);
            }, i * 30);
        }
    }

    // 🍞 Helper: Toast notification
    static showToast(message, type = 'info', duration = 3000) {
        const icons = {
            success: '✅',
            error: '❌',
            info: 'ℹ️',
            warning: '⚠️'
        };
        
        const toast = document.createElement('div');
        toast.className = `toast-notification ${type}`;
        toast.innerHTML = `
            <div style="font-size: 1.5rem;">${icons[type]}</div>
            <div style="font-weight: 600; color: #1e293b;">${message}</div>
        `;
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.style.animation = 'slideOutRight 0.4s ease';
            setTimeout(() => toast.remove(), 400);
        }, duration);
    }

    // 💀 Helper: Skeleton loader
    static showSkeletonLoader(container) {
        const skeletonHTML = `
            <div class="skeleton-loader">
                ${Array(6).fill(0).map(() => `
                    <div class="skeleton-item">
                        <div class="skeleton-line"></div>
                        <div class="skeleton-line short"></div>
                        <div class="skeleton-line price"></div>
                    </div>
                `).join('')}
            </div>
        `;
        container.innerHTML = skeletonHTML;
    }

    static async loadTAE(phone = '') {
        telcelServices.loadingServices = true;
        const container = document.getElementById('tae-services');
        if (container) this.showSkeletonLoader(container);
        
        try {
            telcelServices.tae = TAE_AMOUNTS.map(item => ({
                sku: '',
                formatted_name: item.label,
                description: 'Recarga Telcel',
                price_display: `$${item.amount.toFixed(2)}`,
                amount: item.amount
            }));
        } catch (err) { 
            telcelServices.tae = []; 
        }
    }

    static async loadPaquetes(phone = '') {
        try {
            const data = await window.ProntiServices?.load();
            if (data && data.length) {
                telcelServices.paquetes = window.ProntiServices.getBySkuPrefix('amigo', 'PA').filter(p => !p.sku.includes('-'));
            } else {
                const r = await fetch('prontipagos_proxy.php?servicio=amigo');
                const json = await r.json();
                telcelServices.paquetes = (Array.isArray(json) ? json : []).filter(p => p.sku && p.sku.startsWith('PA') && !p.sku.includes('-'));
            }
        } catch (err) { 
            telcelServices.paquetes = []; 
        }
    }

    static async loadInternet(phone = '') {
        try {
            const data = await window.ProntiServices?.load();
            if (data && data.length) {
                telcelServices.internet = window.ProntiServices.getBySkuPrefix('amigo', 'INT').filter(p => !p.sku.includes('-'));
            } else {
                const r = await fetch('prontipagos_proxy.php?servicio=amigo');
                const json = await r.json();
                telcelServices.internet = (Array.isArray(json) ? json : []).filter(p => p.sku && p.sku.startsWith('INT') && !p.sku.includes('-'));
            }
        } catch (err) { 
            telcelServices.internet = []; 
        }
    }

    static async loadPostpago() {
        try {
            if (window.ProntiServices) {
                const all = await window.ProntiServices.load();
                const allProds = Array.isArray(all) ? all : (window.ProntiServices.getProducts?.('amigo') || []);
                telcelServices.postpago = allProds.filter(p => /postpago/i.test(p.name || '') && !p.sku.includes('-'));
                if (telcelServices.postpago.length) return;
            }
            const r = await fetch('prontipagos_proxy.php?servicio=amigo');
            const d = await r.json();
            const list = Array.isArray(d) ? d : [];
            telcelServices.postpago = list.filter(p => /postpago/i.test(p.name || '') && p.sku && !p.sku.includes('-'));
        } catch { telcelServices.postpago = []; }
    }

    static render() {
    if (!document.getElementById('telcel-styles')) {
        const style = document.createElement('style');
        style.id = 'telcel-styles';
        style.textContent = `
        /* ===== TELCEL PROFESSIONAL DESIGN ===== */
        #telcel-kiosk-root {
          position: fixed; inset: 0; z-index: 99999;
          background: linear-gradient(135deg, rgba(5, 10, 30, 0.95) 0%, rgba(10, 20, 50, 0.95) 100%);
          display: flex; align-items: center; justify-content: center; padding: 1rem;
          animation: fadeIn 0.4s ease-out; overflow: auto; backdrop-filter: blur(12px);
        }
        #telcel-kiosk-wrapper { width: 98%; max-width: none; max-height: 96vh; display: flex; align-items: center; justify-content: center; }
        .telcel-main-card {
          width: 100%; max-height: 96vh; background: white; border-radius: 24px;
          box-shadow: 0 25px 80px rgba(0, 73, 168, 0.4), 0 0 1px rgba(0, 0, 0, 0.2);
          overflow: hidden; animation: slideUp 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
          border: 1px solid rgba(0, 73, 168, 0.1); display: flex; flex-direction: column; transform-origin: center;
        }
        .telcel-header {
          background: linear-gradient(135deg, #0049a8 0%, #003580 100%); padding: 3rem 3rem;
          text-align: center; position: relative; overflow: hidden; flex-shrink: 0;
        }
        .telcel-header::before {
          content: ""; position: absolute; top: 0; left: 0; right: 0; bottom: 0;
          background: radial-gradient(circle at 20% 30%, rgba(255,255,255,0.15) 0%, transparent 50%),
                      radial-gradient(circle at 80% 70%, rgba(255,255,255,0.1) 0%, transparent 50%);
          animation: headerPulse 4s ease-in-out infinite;
        }
        .telcel-header::after {
          content: ""; position: absolute; top: 0; left: 0; right: 0; height: 5px;
          background: linear-gradient(90deg, #0049a8 0%, #003580 50%, #0049a8 100%);
          box-shadow: 0 0 20px rgba(0, 73, 168, 0.5);
        }
        .telcel-header img {
          max-width: 200px; height: auto; filter: brightness(1.1) drop-shadow(0 4px 20px rgba(0,0,0,0.2));
          position: relative; z-index: 1; animation: logoFloat 3s ease-in-out infinite;
        }
        .telcel-header h2 {
          color: white; margin: 1.5rem 0 0 0; font-weight: 700; font-size: 2.5rem;
          text-shadow: 0 3px 12px rgba(0,0,0,0.2); position: relative; z-index: 1; letter-spacing: -0.5px;
        }
        @keyframes logoFloat { 0%, 100% { transform: translateY(0px); } 50% { transform: translateY(-8px); } }
        .telcel-content { padding: 3.5rem; background: #fafbfc; overflow-y: auto; flex: 1; }
        .step-chip {
          display: inline-flex; align-items: center; gap: 0.5rem; background: white; color: #0049a8;
          font-weight: 600; font-size: 1.05rem; padding: 0.85rem 1.75rem; border-radius: 14px;
          border: 2px solid #b3cce6; margin-bottom: 2.5rem; box-shadow: 0 3px 10px rgba(0, 73, 168, 0.1);
          transition: all 0.3s ease; animation: chipBounce 0.6s ease-out;
        }
        .step-chip:hover { border-color: #0049a8; box-shadow: 0 5px 15px rgba(0, 73, 168, 0.2); transform: translateY(-2px); }
        @keyframes chipBounce { 0% { transform: scale(0.8); opacity: 0; } 50% { transform: scale(1.05); } 100% { transform: scale(1); opacity: 1; } }
        .section-title { font-size: 1.4rem; font-weight: 600; color: #1e293b; margin-bottom: 1.5rem; letter-spacing: -0.3px; }
        .input-wrap { position: relative; margin-bottom: 1.5rem; }
        .input-wrap input {
          width: 100%; padding: 1.25rem 4rem 1.25rem 1.5rem; border: 2px solid #e2e8f0; border-radius: 16px;
          font-size: 1.15rem; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); background: white;
          color: #1e293b; font-weight: 500;
        }
        .input-wrap input::placeholder { color: #94a3b8; font-weight: 400; }
        .input-wrap input:focus {
          outline: none; border-color: #0049a8; background: white;
          box-shadow: 0 0 0 5px rgba(0, 73, 168, 0.1), 0 8px 16px rgba(0, 73, 168, 0.15); transform: translateY(-2px);
        }
        .state-icon {
          position: absolute; right: 1.5rem; top: 50%; transform: translateY(-50%);
          font-weight: 700; font-size: 1.5rem; transition: all 0.3s ease;
        }
        .state-icon.ok { color: #10b981; animation: checkBounce 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55); }
        .state-icon.err { color: #ef4444; animation: errorShake 0.5s ease; }
        @keyframes checkBounce { 0% { transform: translateY(-50%) scale(0); } 50% { transform: translateY(-50%) scale(1.3); } 100% { transform: translateY(-50%) scale(1); } }
        @keyframes errorShake { 0%, 100% { transform: translateY(-50%) translateX(0); } 25% { transform: translateY(-50%) translateX(-8px); } 75% { transform: translateY(-50%) translateX(8px); } }
        .error-message {
          color: #dc2626; font-size: 0.9rem; font-weight: 600; margin-top: 0.5rem; display: none;
          padding: 0.75rem 1rem; background: #fef2f2; border-left: 3px solid #dc2626; border-radius: 8px;
        }
        .hint-card {
          background: white; border: 2px solid #b3cce6; border-radius: 16px; padding: 1.75rem;
          margin-bottom: 2rem; box-shadow: 0 2px 8px rgba(0, 73, 168, 0.06);
        }
        .hint-list { list-style: none; padding: 0; margin: 0; }
        .hint-item {
          display: flex; align-items: flex-start; gap: 1rem; margin-bottom: 1rem;
          font-size: 0.95rem; color: #475569; line-height: 1.6;
        }
        .hint-item:last-child { margin-bottom: 0; }
        .hint-icon {
          display: inline-flex; align-items: center; justify-content: center; min-width: 28px; height: 28px;
          background: linear-gradient(135deg, #0049a8 0%, #003580 100%); color: white; border-radius: 50%;
          font-size: 0.8rem; font-weight: 700; box-shadow: 0 2px 8px rgba(0, 73, 168, 0.25);
        }
        .step-header {
          display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;
          padding-bottom: 1.5rem; border-bottom: 2px solid #e2e8f0; flex-wrap: wrap; gap: 1rem;
        }
        .button-group {
          display: flex; gap: 1rem; margin-top: 2.5rem; padding-top: 2rem; border-top: 2px solid #e2e8f0;
          justify-content: flex-end; align-items: center;
        }
        .modern-btn {
          padding: 1rem 2rem; border: none; border-radius: 12px; font-weight: 600; font-size: 1rem;
          cursor: pointer; transition: all 0.3s ease; position: relative; overflow: hidden;
          min-width: 140px; letter-spacing: 0.2px;
        }
        .modern-btn::before {
          content: ""; position: absolute; top: 50%; left: 50%; width: 0; height: 0; border-radius: 50%;
          background: rgba(255,255,255,0.25); transform: translate(-50%, -50%); transition: width 0.6s, height 0.6s;
        }
        .modern-btn:hover::before { width: 300px; height: 300px; }
        .modern-btn:active { transform: scale(0.98); }
        .btn-primary-telcel {
          background: linear-gradient(135deg, #0049a8 0%, #003580 100%); color: white;
          box-shadow: 0 4px 14px rgba(0, 73, 168, 0.35);
        }
        .btn-primary-telcel:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0, 73, 168, 0.45); }
        .btn-primary-telcel:disabled {
          opacity: 0.5; cursor: not-allowed; transform: none; box-shadow: 0 2px 8px rgba(0, 73, 168, 0.2);
        }
        .btn-secondary-telcel {
          background: white; color: #475569; border: 2px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
        .btn-secondary-telcel:hover {
          background: #f8fafc; border-color: #cbd5e1; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }
        .service-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.25rem; margin-top: 1.5rem; }
        .service-item {
          background: white; border: 2px solid #e2e8f0; border-radius: 16px; padding: 1.5rem; cursor: pointer;
          transition: all 0.3s ease; display: flex; flex-direction: column; gap: 0.75rem;
          position: relative; overflow: hidden; min-height: 150px;
        }
        .service-item::before {
          content: ""; position: absolute; top: 0; left: 0; right: 0; height: 4px;
          background: linear-gradient(90deg, #0049a8 0%, #003580 100%); transform: scaleX(0); transition: transform 0.3s ease;
        }
        .service-item:hover { transform: translateY(-4px); border-color: #0049a8; box-shadow: 0 12px 28px rgba(0, 73, 168, 0.15); }
        .service-item:hover::before { transform: scaleX(1); }
        .service-item.selected {
          border-color: #0049a8; background: linear-gradient(135deg, #e6eeff 0%, #b3cce6 100%);
          box-shadow: 0 12px 32px rgba(0, 73, 168, 0.25);
        }
        .service-item.selected::before { transform: scaleX(1); }
        .service-item .service-name { font-weight: 600; font-size: 1rem; color: #1e293b; line-height: 1.4; }
        .service-item .service-price {
          display: inline-block; background: linear-gradient(135deg, #0049a8 0%, #003580 100%);
          color: white; padding: 0.6rem 1.2rem; border-radius: 10px; font-weight: 700; font-size: 1.3rem;
          box-shadow: 0 4px 12px rgba(0, 73, 168, 0.25); align-self: flex-start;
        }
        .service-item.insufficient-funds { opacity: 0.6; cursor: not-allowed; filter: grayscale(0.5); }
        .service-item.insufficient-funds::after {
          content: "Saldo insuficiente"; position: absolute; top: 50%; left: 50%;
          transform: translate(-50%, -50%); background: rgba(239, 68, 68, 0.9); color: white;
          padding: 0.5rem 1rem; border-radius: 8px; font-weight: 600; font-size: 0.85rem;
          white-space: nowrap; z-index: 2;
        }
        .step-screen { opacity: 1; transform: translateX(0); transition: opacity 0.3s ease, transform 0.3s ease; }
        .hidden-step { display: none !important; }
        .pre-enter-right { opacity: 0; transform: translateX(20px); }
        .pre-enter-left { opacity: 0; transform: translateX(-20px); }
        .slide-out-left { opacity: 0; transform: translateX(-20px); }
        .slide-out-right { opacity: 0; transform: translateX(20px); }
        @keyframes fadeIn { from { opacity: 0; backdrop-filter: blur(0px); } to { opacity: 1; backdrop-filter: blur(12px); } }
        @keyframes slideUp { from { opacity: 0; transform: translateY(60px) scale(0.95); } to { opacity: 1; transform: translateY(0) scale(1); } }
        @keyframes headerPulse { 0%, 100% { transform: scale(1) rotate(0deg); opacity: 1; } 50% { transform: scale(1.08) rotate(1deg); opacity: 0.9; } }
        .monedero-badge {
          display: inline-flex; align-items: center; gap: 0.5rem; background: rgba(255,255,255,0.2);
          padding: 0.6rem 1.25rem; border-radius: 50px; margin-top: 1rem; backdrop-filter: blur(8px);
          position: relative; z-index: 1;
        }
        .monedero-icon { font-size: 1.3rem; }
        .monedero-label { color: rgba(255,255,255,0.85); font-weight: 500; font-size: 0.95rem; }
        .monedero-amount { color: white; font-weight: 700; font-size: 1.15rem; }
        .tab-buttons { display: flex; gap: 0.75rem; margin-bottom: 2rem; flex-wrap: wrap; }
        .tab-btn {
          padding: 0.85rem 1.75rem; border: 2px solid #e2e8f0; border-radius: 12px; background: white;
          cursor: pointer; font-weight: 600; font-size: 0.95rem; color: #64748b; transition: all 0.3s ease;
        }
        .tab-btn:hover { border-color: #0049a8; color: #0049a8; }
        .tab-btn.active { background: #0049a8; color: white; border-color: #0049a8; box-shadow: 0 4px 12px rgba(0, 73, 168, 0.3); }
        .tab-content { margin-top: 1rem; }
        #telcel-kiosk-footer {
          padding: 1.5rem 2rem; background: white; border-top: 2px solid #e2e8f0; display: flex;
          justify-content: center; align-items: center; gap: 1rem; box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.05);
          flex-shrink: 0; border-radius: 0 0 24px 24px;
        }
        #telcel-kiosk-footer .modern-btn { min-width: 200px; }
        @media (max-width: 768px) {
          #telcel-kiosk-root { padding: 0; }
          .telcel-main-card { width: 100%; border-radius: 0; min-height: 100vh; max-width: 100%; max-height: 100vh; }
          .telcel-header { padding: 2.5rem 2rem; }
          .telcel-header img { max-width: 150px; }
          .telcel-header h2 { font-size: 2rem; }
          .telcel-content { padding: 2.5rem 2rem; }
          .service-grid { grid-template-columns: 1fr; gap: 1.25rem; }
          .button-group { flex-direction: column; }
          .modern-btn { width: 100%; min-width: unset; padding: 1.25rem 2rem; font-size: 1.1rem; }
          #telcel-kiosk-footer { padding: 1.25rem 1.5rem; }
          #telcel-kiosk-footer .modern-btn { width: 100%; padding: 1.25rem 2rem; }
        }
        @media (min-width: 769px) and (max-width: 1200px) {
          .telcel-main-card { width: 96%; max-width: none; }
          .service-grid { grid-template-columns: repeat(3, 1fr); }
          .telcel-content { padding: 3rem; }
        }
        @media (min-width: 1201px) {
          .telcel-main-card { width: 96%; max-width: none; }
          .service-grid { grid-template-columns: repeat(3, 1fr); }
          .telcel-content { padding: 3.5rem 4.5rem; }
        }
        @media (min-width: 1600px) {
          .telcel-main-card { width: 94%; max-width: none; }
          .service-grid { grid-template-columns: repeat(4, 1fr); }
          .telcel-content { padding: 4rem 5rem; }
        }
        `;
        document.head.appendChild(style);
    }

        const bodyHTML = `
          <div class="telcel-main-card">
            <div class="telcel-header">
              <img src="images/services/telcel.png" alt="Telcel" />
              <h2>Servicios Telcel</h2>
              <div class="monedero-badge">
                <span class="monedero-icon">💰</span>
                <span class="monedero-label">Saldo:</span>
                <span class="monedero-amount" id="telcel-saldo">$${telcelServices.monederoSaldo.toFixed(2)}</span>
              </div>
            </div>
            
            <div class="telcel-content">
              <!-- Step 1: Captura de número -->
              <div id="phone-step" class="step-screen">
                <div class="step-chip">📱 Paso 1: Ingresa tu número telefónico</div>
                
                <div class="section-title">Número de teléfono</div>
                
                <div class="hint-card">
                  <ul class="hint-list">
                    <li class="hint-item"><span class="hint-icon">①</span><span>Escribe tu número celular de 10 dígitos</span></li>
                    <li class="hint-item"><span class="hint-icon">②</span><span>Confírmalo en el segundo campo</span></li>
                    <li class="hint-item"><span class="hint-icon">③</span><span>Haz clic en "Continuar"</span></li>
                  </ul>
                </div>

                <div class="input-wrap">
                  <input type="tel" id="phone-number" placeholder="Número Telcel (10 dígitos)" inputmode="numeric" pattern="[0-9]{10}" maxlength="10" autofocus />
                  <span class="state-icon" id="phone1-state"></span>
                </div>
                
                <div class="input-wrap">
                  <input type="tel" id="phone-number-confirm" placeholder="Confirma el número" inputmode="numeric" pattern="[0-9]{10}" maxlength="10" />
                  <span class="state-icon" id="phone2-state"></span>
                </div>
                
                <div id="phone-error" class="error-message">Los números no coinciden</div>
                
                <div class="button-group">
                  <button id="close-from-phones" class="modern-btn btn-secondary-telcel">Cerrar</button>
                  <button id="next-step" class="modern-btn btn-primary-telcel" disabled>Continuar →</button>
                </div>
              </div>
              
              <!-- Step 2: Selección de categoría y servicio -->
              <div id="tabs-step" class="service-tabs step-screen hidden-step">
                <div class="step-header">
                  <div class="step-chip">📋 Paso 2: Elige tu servicio</div>
                  <button id="back-from-tabs" class="modern-btn btn-secondary-telcel">← Regresar</button>
                </div>
                
                <div class="hint-card">
                  <div class="hint-item">
                    <span class="hint-icon">ℹ</span>
                    <span>Selecciona una categoría y luego el servicio que necesitas</span>
                  </div>
                </div>
                
                <div class="tab-buttons">
                  <button class="tab-btn" data-tab="tae">💰 Recargas</button>
                  <button class="tab-btn" data-tab="paquetes">📦 Paquetes</button>
                  <button class="tab-btn" data-tab="internet">🌐 Internet</button>
                  <button class="tab-btn" data-tab="postpago">📋 Postpago</button>
                </div>
                
                <div class="tab-content">
                  <div id="tab-empty" style="padding:2rem; text-align:center; color:#6b7280; font-size:1.1rem;">
                    👆 Selecciona una categoría para ver los servicios disponibles
                  </div>
                  <div class="tab-pane" data-tab="tae" style="display:none;">
                    <div class="service-grid"></div>
                  </div>
                  <div class="tab-pane" data-tab="paquetes" style="display:none;">
                    <div class="service-grid"></div>
                  </div>
                  <div class="tab-pane" data-tab="internet" style="display:none;">
                    <div class="service-grid"></div>
                  </div>
                  <div class="tab-pane" data-tab="postpago" style="display:none;">
                    <div class="postpago-content">
                      <div class="hint-card" style="margin-bottom:1.5rem;">
                        <div class="hint-item">
                          <span class="hint-icon">ℹ</span>
                          <span>Ingresa el monto a pagar de tu plan postpago</span>
                        </div>
                      </div>
                      <div class="input-wrap">
                        <input type="number" id="telcel-postpago-amount" class="postpago-amount" placeholder="Monto a pagar" min="0.01" step="0.01" />
                        <span class="state-icon" id="telcel-postpago-icon"></span>
                      </div>
                      <div id="telcel-postpago-error" class="error-message">Monto inválido (debe ser mayor a 0)</div>
                      <div style="font-size:0.95rem; color:#64748b; margin-top:1rem; padding:0.75rem 1rem; background:#f8fafc; border-radius:10px; font-weight:600;">
                        Comisión: $2.00
                      </div>
                      <div class="button-group" style="margin-top:2rem;">
                        <button class="modern-btn btn-primary-telcel" id="telcel-postpago-pay" disabled>💳 Pagar</button>
                      </div>
                    </div>
                  </div>
                </div>
                
                <div class="button-group" style="margin-top: 3rem;">
                  <button class="modern-btn btn-primary-telcel" id="process-service" style="display:none;" disabled>💳 Pagar</button>
                </div>
              </div>
            </div>
            
            <div id="telcel-kiosk-footer">
              <button class="modern-btn btn-secondary-telcel" id="cancel-service">Cerrar</button>
            </div>
          </div>
        `;

        const footerHTML = ``;

        TelcelServices.openOverlay({ title: 'Servicios Telcel', bodyHTML, footerHTML });
    }

    static renderList(list) {
        if (list === null) return '<p style="text-align:center; padding:2rem; color:#6b7280; font-size:1.1rem;">⏳ Cargando servicios...</p>';
        if (!Array.isArray(list) || list.length === 0) return '<p style="text-align:center; padding:2rem; color:#6b7280; font-size:1.1rem;">📭 No hay servicios disponibles</p>';
        return list
            .sort((a, b) => parseFloat(a.maxAmount || a.amount || 0) - parseFloat(b.maxAmount || b.amount || 0))
            .map(s => `
            <div class="service-item" data-sku="${s.sku}">
                <div class="service-name">${s.name || s.formatted_name}</div>
                ${s.description ? `<div style="font-size:0.9rem; color:#6b7280; margin-top:0.5rem;">${s.description.replace(/\$\d+(?:\.\d+)?\s*(?:MXN)?/gi, '').trim()}</div>` : ''}
                <div class="service-price">${s.maxAmount ? `$${parseFloat(s.maxAmount).toFixed(2)}` : s.price_display}</div>
                <div style="font-size:0.85rem; color:#9ca3af; margin-top:0.5rem;">SKU: ${s.sku}</div>
            </div>
        `).join('');
    }

    static updateList(tab, list) {
        const target = document.querySelector(`.tab-pane[data-tab="${tab}"] .service-grid`);
        if (target) {
            target.innerHTML = this.renderList(list);
            this.marcarServiciosInsuficientes();
        }
    }

    static marcarServiciosInsuficientes() {
        const items = document.querySelectorAll('.service-item');
        if (items.length === 0) return; // No hay servicios cargados aún
        
        const comision = 2;
        
        items.forEach(item => {
            const priceElement = item.querySelector('.service-price');
            if (!priceElement) return;
            
            const price = parseFloat(priceElement.textContent.replace(/[^0-9.]/g, '')) || 0;
            const total = price + comision;
            
            if (total > telcelServices.monederoSaldo) {
                if (!item.classList.contains('insufficient-funds')) {
                    item.classList.add('insufficient-funds');
                }
            } else {
                if (item.classList.contains('insufficient-funds')) {
                    item.classList.remove('insufficient-funds');
                }
            }
        });
    }

    static wire() {
        const body = document.querySelector('.telcel-main-card');
        const footer = document.getElementById('telcel-kiosk-footer');
        if (!body || !footer) return;

        if (TelcelServices._h) {
            const { c1, c2, c3, i1, i2 } = TelcelServices._h;
            if (c1) body.removeEventListener('click', c1);
            if (c2) body.removeEventListener('input', c2);
            if (c3) footer.removeEventListener('click', c3);
            try {
                const p = body.querySelector('#phone-number');
                const c = body.querySelector('#phone-number-confirm');
                if (p && i1) { ['input', 'keyup', 'change', 'paste'].forEach(ev => p.removeEventListener(ev, i1)); }
                if (c && i2) { ['input', 'keyup', 'change', 'paste'].forEach(ev => c.removeEventListener(ev, i2)); }
            } catch { }
        }

        const isValid10 = v => /^[0-9]{10}$/.test(v || '');
        const showStep = (fromEl, toEl, direction = 'right') => {
            if (!fromEl || !toEl) return;
            toEl.classList.remove('hidden-step');
            toEl.classList.add('pre-enter-' + (direction === 'right' ? 'right' : 'left'));
            fromEl.classList.add(direction === 'right' ? 'slide-out-left' : 'slide-out-right');
            setTimeout(() => {
                fromEl.classList.add('hidden-step');
                fromEl.classList.remove('slide-out-left', 'slide-out-right');
                toEl.classList.remove('pre-enter-right', 'pre-enter-left');
            }, 200);
        };
        const validate = () => {
            const phone = body.querySelector('#phone-number');
            const confirm = body.querySelector('#phone-number-confirm');
            const err = body.querySelector('#phone-error');
            const tabs = body.querySelector('#service-tabs');
            const hint = body.querySelector('#gating-hint');
            const next = body.querySelector('#next-step');
            const btn = body.querySelector('#process-service');
            const selected = body.querySelector('.service-item.selected');
            const s1 = body.querySelector('#phone1-state');
            const s2 = body.querySelector('#phone2-state');
            const p = (phone?.value || '').replace(/\D/g, '');
            const c = (confirm?.value || '').replace(/\D/g, '');
            if (phone && phone.value !== p) phone.value = p.slice(0, 10);
            if (confirm && confirm.value !== c) confirm.value = c.slice(0, 10);
            const ok = isValid10(p);
            const cok = isValid10(c);
            const match = ok && cok && p === c;
            if (phone) phone.style.borderColor = ok ? '#4CAF50' : (p.length ? '#ff6b6b' : '#f1f1f1');
            if (confirm) confirm.style.borderColor = (cok && match) ? '#4CAF50' : (c.length ? '#ff6b6b' : '#f1f1f1');
            if (err) err.style.display = (c.length > 0 && !match) ? 'block' : 'none';
            if (s1) { 
                s1.textContent = ok ? '✓' : (p.length ? '!' : ''); 
                s1.className = 'state-icon ' + (ok ? 'ok' : (p.length ? 'err' : '')); 
            }
            if (s2) { 
                s2.textContent = (c.length ? (match ? '✓' : '!') : ''); 
                s2.className = 'state-icon ' + ((c.length && match) ? 'ok' : (c.length ? 'err' : '')); 
            }
            if (next) next.disabled = !match;
            if (hint) {
                hint.textContent = !ok
                    ? ''
                    : (!cok ? 'Confirma tu número para continuar.' : (match ? 'Presiona “Siguiente” para ver los servicios.' : 'Los números no coinciden.'));
            }
            if (!match && tabs) {
                tabs.style.display = 'none';
                body.querySelectorAll('.service-item.selected').forEach(i => { i.classList.remove('selected'); i.style.background = 'white'; });
            }
            if (btn) {
                // Si es tab de recargas (tae), validar que haya un monto seleccionado
                const activeTab = body.querySelector('.tab-btn.active');
                const isTaeTab = activeTab?.dataset.tab === 'tae';
                
                // Mostrar botón si hay un servicio seleccionado
                btn.style.display = selected ? 'inline-block' : 'none';
                btn.disabled = !(selected && match);
            }
            
            // Postpago validation
            const postpagoAmount = body.querySelector('#telcel-postpago-amount');
            const postpagoPay = body.querySelector('#telcel-postpago-pay');
            const postpagoErr = body.querySelector('#telcel-postpago-error');
            const activeTab = body.querySelector('.tab-btn.active');
            const isPostpagoTab = activeTab?.dataset.tab === 'postpago';
            if (postpagoPay) {
                const amt = parseFloat(postpagoAmount?.value);
                const validAmt = !isNaN(amt) && amt > 0;
                const hasSku = Array.isArray(telcelServices.postpago) && telcelServices.postpago.length > 0;
                if (postpagoErr) postpagoErr.style.display = (postpagoAmount?.value && !validAmt) ? 'block' : 'none';
                postpagoPay.disabled = !(hasSku && validAmt && match);
            }
            if (match) {
                const ref = body.querySelector('#phone-number').value;
                const sp = new URLSearchParams({ saldo: '1', svc: 'telcel', ref }); if (window.ProntiServices?.data) sp.set('data', JSON.stringify(window.ProntiServices.data)); fetch('prontipagos_proxy.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: sp }).then(res => res.json()).then(data => {
                    let texto = data['additionalInfo'];
                    let posicion = texto.indexOf("$");
                    if (posicion !== -1) {
                        let monto = texto.slice(posicion + 1);
                        body.querySelector('#telcel-postpago-amount').value = parseFloat(monto).toFixed(2);
                    }
                });
            }
        };

        const onClick = (e) => {
            const tab = e.target.closest('.tab-btn');
            if (tab) {
                // Vibración al cambiar de tab
                TelcelServices.hapticFeedback('light');
                
                const id = tab.dataset.tab;
                body.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                tab.classList.add('active');
                body.querySelector('#tab-empty')?.setAttribute('style', 'display:none;');
                body.querySelectorAll('.tab-pane').forEach(p => { p.style.display = (p.dataset.tab === id) ? 'block' : 'none'; });
                body.querySelectorAll('.service-item.selected').forEach(i => { i.classList.remove('selected'); i.style.background = 'white'; });
                
                const paneGrid = body.querySelector(`.tab-pane[data-tab="${id}"] .service-grid`);
                const phone = (body.querySelector('#phone-number')?.value || '').replace(/\D/g, '').slice(0, 10);
                const ensureLoaded = async () => {
                    if (id === 'tae' && telcelServices.tae === null) { await TelcelServices.loadTAE(phone); }
                    if (id === 'paquetes' && telcelServices.paquetes === null) { await TelcelServices.loadPaquetes(phone); }
                    if (id === 'internet' && telcelServices.internet === null) { await TelcelServices.loadInternet(phone); }
                    if (id === 'postpago' && telcelServices.postpago === null) { await TelcelServices.loadPostpago(); }
                    if (id !== 'postpago') TelcelServices.updateList(id, telcelServices[id] || []);
                    setTimeout(() => TelcelServices.marcarServiciosInsuficientes(), 100);
                };
                if ((id === 'postpago' && telcelServices.postpago === null) || (paneGrid && !paneGrid.innerHTML.trim())) {
                    if (paneGrid) paneGrid.innerHTML = '<p style="text-align:center; color:#777; padding:14px;">Cargando...</p>';
                    ensureLoaded().then(() => { });
                }
                
                validate();
                return;
            }
            const item = e.target.closest('.service-item');
            if (item) {
                if (item.classList.contains('insufficient-funds')) {
                    // Vibración de error
                    TelcelServices.hapticFeedback('error');
                    const price = parseFloat(item.querySelector('.service-price')?.textContent.replace(/[^0-9.]/g, '')) || 0;
                    TelcelServices.mostrarSaldoInsuficiente(price);
                    return;
                }
                // Vibración de selección
                TelcelServices.hapticFeedback('light');
                body.querySelectorAll('.service-item.selected').forEach(i => { i.classList.remove('selected'); i.style.background = 'white'; });
                item.classList.add('selected');
                item.style.background = '#fff9d8';
                validate();
                return;
            }
            
            // Manejar click en botón de pagar
            if (e.target.closest('#process-service')) {
                // Vibración media al procesar
                TelcelServices.hapticFeedback('medium');
                const selected = body.querySelector('.service-item.selected');
                const phone = (body.querySelector('#phone-number')?.value || '').slice(0, 10);
                
                if (!selected || !isValid10(phone)) { 
                    Swal.fire('Error', 'Selecciona un servicio y número válido', 'error'); 
                    return; 
                }
                
                const name = selected.querySelector('.service-name')?.textContent?.trim() || 'Servicio Telcel';
                const priceTxt = selected.querySelector('.service-price')?.textContent || '$0';
                const sku = selected.dataset.sku || ''; // SKU puede estar vacío para recargas
                const price = parseFloat(priceTxt.replace(/[^0-9.]/g, '')) || 0;
                const comision = 2;
                const total = price + comision;
                
                // Validar saldo del monedero (incluyendo comisión)
                if (total > telcelServices.monederoSaldo) {
                    TelcelServices.mostrarSaldoInsuficiente(price);
                    return;
                }
                
                // 🚀 Usar el nuevo payment handler con Point + Efectivo
                if (window.ServicePaymentHandler) {
                    window.ServicePaymentHandler.showPaymentOptions({
                        serviceName: name,
                        reference: phone,
                        amount: price,
                        commission: comision,
                        sku: sku,
                        requiresRegionalization: true
                    }, async (paymentResult) => {
                        // Solo mostrar alerta si el pago fue exitoso
                        if (paymentResult && paymentResult.success) {
                            // mostrarRecargaExitosa ya descuenta el saldo internamente
                            await TelcelServices.mostrarRecargaExitosa(name, phone, price);
                            TelcelServices.closeOverlay();
                        }
                    });
                } else {
                    // Fallback: método directo (legacy)
                    Swal.fire({
                        title: '💳 Confirmar pago',
                        html: `
                            <div style="text-align: left; padding: 1.5rem; background: #f9fafb; border-radius: 12px;">
                                <p style="margin: 0.75rem 0; font-size: 1.1rem;"><strong>📱 Servicio:</strong> ${name}</p>
                                <p style="margin: 0.75rem 0; font-size: 1.1rem;"><strong>📞 Número:</strong> <span style="color: #0049a8;">${phone}</span></p>
                                <hr style="margin: 1rem 0; border: none; border-top: 2px solid #e5e7eb;">
                                <p style="margin: 0.75rem 0; font-size: 1.1rem;"><strong>Monto recarga:</strong> <span style="color: #0049a8;">$${price.toFixed(2)}</span></p>
                                <p style="margin: 0.75rem 0; font-size: 1rem; color: #6b7280;"><strong>Comisión:</strong> +$${comision.toFixed(2)}</p>
                                <hr style="margin: 1rem 0; border: none; border-top: 3px solid #0049a8;">
                                <p style="margin: 0.75rem 0; font-size: 1.4rem; text-align: center;"><strong>Total a pagar:</strong> <span style="color: #7c3aed; font-weight: 700;">$${total.toFixed(2)}</span></p>
                            </div>
                        `,
                        icon: 'question', 
                        showCancelButton: true, 
                        confirmButtonText: '✅ Confirmar',
                        confirmButtonColor: '#0049a8',
                        cancelButtonText: '❌ Cancelar',
                        cancelButtonColor: '#dc2626'
                    }).then((res) => {
                        if (res.isConfirmed) {
                            Swal.fire({ 
                                title: '⏳ Procesando tu recarga...', 
                                html: `
                                    <div style="padding: 1rem;">
                                        <p style="color: #64748b; margin-bottom: 1.5rem;">Por favor espera un momento</p>
                                        <div class="progress-bar">
                                            <div class="progress-fill"></div>
                                        </div>
                                        <div style="margin-top: 1.5rem; text-align: center;">
                                            <div class="telcel-spinner"></div>
                                        </div>
                                    </div>
                                `,
                                allowOutsideClick: false, 
                                showConfirmButton: false, 
                                didOpen: () => Swal.showLoading() 
                            });
                            
                            const payload = { 
                                sku, 
                                amount: price, 
                                reference: phone, 
                                requiresRegionalization: true, 
                                transactionId: Date.now() 
                            };
                            
                            const params = new URLSearchParams({ comprar: '1', svc: sku, ref: phone, amount: price });
                            console.log(params.toString());
                            fetch(`prontipagos_proxy.php?${params}`, { 
                                headers: { 'Accept': 'application/json' }, 
                                cache: 'no-store' 
                            })
                            .then(async (r) => await r.json())
                            .then(async (d) => {
                                Swal.close();
                                
                                if (d?.codeTransaction === '00') {
                                    const codeDesc = d?.codeDescription;
                                    await TelcelServices.mostrarRecargaExitosa(name, phone, price, codeDesc);
                                    TelcelServices.closeOverlay();
                                } else {
                                    let errorMessage = d?.codeDescription || 'Ocurrió un error al procesar tu recarga';
                                    
                                    Swal.fire({
                                        title: '❌ Error en la recarga',
                                        text: errorMessage,
                                        icon: 'error',
                                        confirmButtonText: 'Entendido',
                                        confirmButtonColor: '#0049a8'
                                    });
                                }
                            })
                            .catch(err => {
                                console.log(err)
                                Swal.close();
                                Swal.fire({
                                    title: '⚠️ Error de conexión',
                                    text: 'No se pudo conectar con el servidor. Por favor intenta de nuevo.',
                                    icon: 'error',
                                    confirmButtonText: 'Entendido',
                                    confirmButtonColor: '#0049a8'
                                });
                            });
                        }
                    });
                }
                return;
            }
            
            // Postpago pay
            if (e.target.closest('#telcel-postpago-pay')) {
                TelcelServices.hapticFeedback('medium');
                const phone = (body.querySelector('#phone-number')?.value || '').slice(0, 10);
                const svc = Array.isArray(telcelServices.postpago) && telcelServices.postpago.length ? telcelServices.postpago[0] : null;
                if (!svc || !isValid10(phone)) { Swal.fire('Error', 'No se pudo cargar el servicio o número inválido', 'error'); return; }
                const name = svc.formatted_name || svc.name || 'Postpago Telcel';
                const amount = parseFloat(body.querySelector('#telcel-postpago-amount')?.value);
                const comision = 2;
                const total = amount + comision;
                if (isNaN(amount) || amount <= 0) { Swal.fire('Error', 'Ingresa un monto válido', 'error'); return; }
                if (total > telcelServices.monederoSaldo) { TelcelServices.mostrarSaldoInsuficiente(amount); return; }
                const sku = svc.sku || '';
                if (window.ServicePaymentHandler) {
                    window.ServicePaymentHandler.showPaymentOptions({
                        serviceName: name, reference: phone, amount, commission: comision, sku, requiresRegionalization: true
                    }, async (paymentResult) => {
                        if (paymentResult && paymentResult.success) { await TelcelServices.mostrarRecargaExitosa(name, phone, amount); TelcelServices.closeOverlay(); }
                    });
                } else {
                    Swal.fire({ title: '💳 Confirmar pago', html: `<div style="text-align:left;padding:1.5rem;background:#f9fafb;border-radius:12px;"><p><strong>Servicio:</strong> ${name}</p><p><strong>Número:</strong> ${phone}</p><hr><p><strong>Monto:</strong> $${amount.toFixed(2)}</p><p><strong>Comisión:</strong> +$${comision.toFixed(2)}</p><hr><p style="font-size:1.4rem;text-align:center;"><strong>Total:</strong> $${total.toFixed(2)}</p></div>`, icon: 'question', showCancelButton: true, confirmButtonText: 'Pagar', cancelButtonText: 'Cancelar' }).then((res) => {
                        if (res.isConfirmed) {
                            Swal.fire({ title: 'Procesando...', allowOutsideClick: false, showConfirmButton: false, didOpen: () => Swal.showLoading() });
                            fetch(`prontipagos_proxy.php?comprar=1&svc=${sku}&ref=${phone}&amount=${amount}`, { headers: { 'Accept': 'application/json' } })
                                .then(r => r.json())
                                .then(async d => {
                                    Swal.close();
                                    if (d?.codeTransaction === '00') { await TelcelServices.mostrarRecargaExitosa(name, phone, amount); TelcelServices.closeOverlay(); }
                                    else { Swal.fire('Error', d?.codeDescription || 'Error', 'error'); }
                                }).catch(() => { Swal.close(); Swal.fire('Error', 'Error de conexión', 'error'); });
                        }
                    });
                }
                return;
            }
        };

        const onInput = (e) => {
            if (e && e.key === 'Enter') {
                const next = body.querySelector('#next-step');
                if (next && next.style.display !== 'none') next.click();
            }
            validate();
        };

        const onFooter = (e) => {
            if (e.target.closest('#cancel-service')) { 
                TelcelServices.closeOverlay(); 
                return; 
            }
        };

        body.addEventListener('click', onClick);
        body.addEventListener('input', onInput);
        body.addEventListener('keydown', onInput);
        const pEl = body.querySelector('#phone-number');
        const cEl = body.querySelector('#phone-number-confirm');
        const direct = (e) => onInput(e);
        ['input', 'keyup', 'change', 'paste'].forEach(ev => { 
            pEl?.addEventListener(ev, direct); 
            cEl?.addEventListener(ev, direct); 
        });
        const next = body.querySelector('#next-step');
        if (next) {
            next.addEventListener('click', (ev) => {
                ev.preventDefault();
                const phoneStep = body.querySelector('#phone-step');
                const tabsStep = body.querySelector('#tabs-step');
                const p = (body.querySelector('#phone-number')?.value || '').replace(/\D/g, '').slice(0, 10);
                const c = (body.querySelector('#phone-number-confirm')?.value || '').replace(/\D/g, '').slice(0, 10);
                const ok = /^[0-9]{10}$/.test(p);
                const cok = /^[0-9]{10}$/.test(c);
                const match = ok && cok && p === c;
                if (!match) return;
                if (telcelServices.tae === null) TelcelServices.loadTAE(p);
                if (telcelServices.paquetes === null) TelcelServices.loadPaquetes(p);
                if (telcelServices.internet === null) TelcelServices.loadInternet(p);
                if (telcelServices.postpago === null) TelcelServices.loadPostpago();
                showStep(phoneStep, tabsStep, 'right');
                const displayedPhone = body.querySelector('#displayed-phone');
                if (displayedPhone) displayedPhone.textContent = p;
                const phoneDisplay = body.querySelector('#phone-display');
                if (phoneDisplay) phoneDisplay.style.display = 'block';
            });
        }
        const backBtn = body.querySelector('#back-from-tabs');
        if (backBtn) {
            backBtn.addEventListener('click', (ev) => {
                ev.preventDefault();
                const phoneStep = body.querySelector('#phone-step');
                const tabsStep = body.querySelector('#tabs-step');
                showStep(tabsStep, phoneStep, 'left');
                const phoneDisplay = body.querySelector('#phone-display');
                if (phoneDisplay) phoneDisplay.style.display = 'none';
            });
        }
        body.querySelector('#close-from-phones')?.addEventListener('click', () => { TelcelServices.closeOverlay(); });
        footer.addEventListener('click', onFooter);
        TelcelServices._h = { c1: onClick, c2: onInput, c3: onFooter, i1: direct, i2: direct };
        validate();
    }
}

TelcelServices.openOverlay = ({ title, bodyHTML, footerHTML }) => {
    if (window.ServiceModal) {
        window.ServiceModal.open({ title, bodyHTML, footerHTML });
    } else {
        // Fallback: crear overlay propio
        let root = document.getElementById('telcel-kiosk-root');
        if (!root) {
            root = document.createElement('div');
            root.id = 'telcel-kiosk-root';
            document.body.appendChild(root);
            root.innerHTML = `<div id="telcel-kiosk-wrapper">${bodyHTML}</div>`;
            try { TelcelServices._prev = { h: document.documentElement.style.overflow, b: document.body.style.overflow }; document.documentElement.style.overflow = 'hidden'; document.body.style.overflow = 'hidden'; } catch { }
        }
    }
};

TelcelServices.closeOverlay = () => {
    TelcelServices.detenerActualizacionSaldo();
    if (window.ServiceModal) {
        window.ServiceModal.close();
        try { if (TelcelServices._prev) { document.documentElement.style.overflow = TelcelServices._prev.h || ''; document.body.style.overflow = TelcelServices._prev.b || ''; TelcelServices._prev = null; } } catch { }
    } else {
        const root = document.getElementById('telcel-kiosk-root');
        if (root) root.remove();
        try { if (TelcelServices._prev) { document.documentElement.style.overflow = TelcelServices._prev.h || ''; document.body.style.overflow = TelcelServices._prev.b || ''; TelcelServices._prev = null; } } catch { }
    }
};



