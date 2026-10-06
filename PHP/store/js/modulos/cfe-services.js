let cfeData = { services: null, procesandoPago: false, saldoListener: null };

export class CFEServices {
    static async init() {
        try {
            await this.loadServices(); // 🔥 CRITICAL: Cargar servicios ANTES de renderizar
            this.render();
            this.wire();
            this.iniciarSuscripcionSaldo();
        } catch (e) {
            Swal.fire('Error', 'No se pudieron cargar los servicios de CFE', 'error');
        }
    }

    static getSaldoActual() {
        return window.monederoSaldoActual || 0;
    }

    static iniciarSuscripcionSaldo() {
        cfeData.saldoListener = (e) => {
            if (!cfeData.procesandoPago) {
                this.actualizarBadgeSaldo();
            }
        };
        window.addEventListener('monederoSaldoChanged', cfeData.saldoListener);
        this.actualizarBadgeSaldo();
    }
    
    static detenerSuscripcionSaldo() {
        if (cfeData.saldoListener) {
            window.removeEventListener('monederoSaldoChanged', cfeData.saldoListener);
            cfeData.saldoListener = null;
        }
    }

    static actualizarBadgeSaldo() {
        const saldoNuevo = this.getSaldoActual();
        const badgeElement = document.getElementById('cfe-saldo');
        if (badgeElement) {
            const saldoAnterior = parseFloat(badgeElement.textContent.replace(/[^0-9.]/g, '')) || 0;
            badgeElement.textContent = `$${saldoNuevo.toFixed(2)}`;
            
            if (Math.abs(saldoNuevo - saldoAnterior) > 0.01) {
                badgeElement.style.transform = 'scale(1.2)';
                badgeElement.style.transition = 'transform 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55)';
                setTimeout(() => {
                    badgeElement.style.transform = 'scale(1)';
                }, 300);
            }
        }
    }

    static async descontarSaldo(monto) {
        try {
            const saldoActual = this.getSaldoActual();
            const nuevoSaldo = Math.max(0, saldoActual - monto);
            
            const response = await fetch('monedero_api.php?action=set_saldo', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `saldo=${nuevoSaldo}`
            });
            const data = await response.json();
            
            if (!data.success) {
                console.error('❌ [CFE] Error al actualizar saldo en backend:', data);
                throw new Error('Error al actualizar backend');
            }
            
            window.monederoSaldoActual = nuevoSaldo;
            if (typeof MonederoIntegration !== 'undefined') {
                MonederoIntegration.actualizarSaldo(nuevoSaldo);
            }
            
            console.log('✅ [CFE] Saldo actualizado:', { anterior: saldoActual, nuevo: nuevoSaldo, descontado: monto });
        } catch (error) {
            console.error('❌ [CFE] Error al descontar saldo:', error);
            throw error;
        }
    }

    static async mostrarRecargaExitosa(serviceName, reference, amount, codeDesc = null, metodoPago = 'cash') {
        console.log('🎉 [CFE] mostrarRecargaExitosa llamada:', { serviceName, reference, amount, codeDesc, metodoPago });
        
        const commission = 12;
        const totalCobrado = amount + commission;
        
        let saldoInicial = 0;
        let cambio = 0;
        
        if (metodoPago === 'cash') {
            saldoInicial = this.getSaldoActual();
            cambio = Math.max(0, saldoInicial - totalCobrado);
            
            console.log('💰 [CFE] Valores calculados con efectivo:', { saldoInicial, totalCobrado, cambio });
            
            if (saldoInicial < totalCobrado) {
                Swal.fire('⚠️ Saldo insuficiente', `Te faltan $${(totalCobrado - saldoInicial).toFixed(2)}`, 'warning');
                throw new Error('Saldo insuficiente');
            }
            
            await this.descontarSaldo(totalCobrado);
        } else {
            console.log('💳 [CFE] Pago con tarjeta Point, NO se descuenta del monedero');
        }
        
        console.log('🖨️ [CFE] Iniciando impresión de ticket...');
        this.imprimirTicketRecarga({
            servicio: serviceName,
            proveedor: 'CFE',
            telefono: reference,
            monto: amount,
            comision: commission,
            total: totalCobrado,
            saldoInicial: saldoInicial,
            cambio: cambio,
            fecha: new Date().toLocaleString('es-MX'),
            folio: Date.now(),
            metodoPago: metodoPago === 'cash' ? 'Efectivo' : 'Tarjeta (Point)',
            status: codeDesc || 'Exitosa'
        });
        
        const mensajePago = metodoPago === 'cash' 
            ? `
                <p style="margin: 0.75rem 0; font-size: 1.2rem;"><strong>💰 Saldo insertado:</strong> <span style="color: #7c3aed; font-weight: 700;">$${saldoInicial.toFixed(2)}</span></p>
                <p style="margin: 0.75rem 0; font-size: 1.2rem;"><strong>💳 Costo servicio:</strong> <span style="color: #dc2626; font-weight: 700;">-$${amount.toFixed(2)}</span></p>
                <p style="margin: 0.75rem 0; font-size: 1rem; color: #6b7280;"><strong>🏷️ Comisión:</strong> <span style="color: #f59e0b; font-weight: 600;">-$${commission.toFixed(2)}</span></p>
                <hr style="margin: 1.5rem 0; border: none; border-top: 3px solid #22c55e;">
                <p style="margin: 0.75rem 0; font-size: 1.5rem; text-align: center;"><strong>💵 Tu cambio:</strong> <span style="color: #10b981; font-weight: 800;">$${cambio.toFixed(2)}</span></p>
            `
            : `
                <p style="margin: 0.75rem 0; font-size: 1.2rem;"><strong>💳 Costo servicio:</strong> <span style="color: #dc2626; font-weight: 700;">$${amount.toFixed(2)}</span></p>
                <p style="margin: 0.75rem 0; font-size: 1rem; color: #6b7280;"><strong>🏷️ Comisión:</strong> <span style="color: #f59e0b; font-weight: 600;">$${commission.toFixed(2)}</span></p>
                <hr style="margin: 1.5rem 0; border: none; border-top: 3px solid #22c55e;">
                <p style="margin: 0.75rem 0; font-size: 1.5rem; text-align: center;"><strong>💰 Total cobrado:</strong> <span style="color: #10b981; font-weight: 800;">$${totalCobrado.toFixed(2)}</span></p>
                <p style="margin: 0.75rem 0; text-align: center; font-size: 0.95rem; color: #7c3aed;">💳 <strong>Pagado con tarjeta Point</strong></p>
            `;
        
        await Swal.fire({
            title: '✅ ¡Pago procesado!',
            html: `
                <div style="text-align: left; padding: 1.5rem; background: linear-gradient(135deg, #d0f5dd 0%, #b3e5cc 100%); border-radius: 12px; border: 2px solid #00A758;">
                    <div style="text-align: center; margin-bottom: 1.5rem;">
                        <div style="font-size: 4rem; animation: bounce 1s ease;">💡</div>
                    </div>
                    <p style="margin: 0.75rem 0; font-size: 1.05rem;"><strong>📱 Servicio:</strong> ${serviceName}</p>
                    <p style="margin: 0.75rem 0; font-size: 1.05rem;"><strong>📞 Número:</strong> <span style="color: #00A758;">${reference}</span></p>
                    ${codeDesc ? `<p style="margin: 0.75rem 0; font-size: 0.95rem; color: #6b7280;"><strong>Estado:</strong> ${codeDesc}</p>` : ''}
                    <hr style="margin: 1.5rem 0; border: none; border-top: 2px dashed #00A758;">
                    ${mensajePago}
                    <p style="margin: 1rem 0; text-align: center; font-size: 0.9rem; color: #10b981;">🖨️ <strong>Imprimiendo ticket...</strong></p>
                </div>
            `,
            icon: 'success',
            confirmButtonText: '✅ Perfecto',
            confirmButtonColor: '#00A758',
            timer: 6000,
            timerProgressBar: true
        });
    }

    static async imprimirTicketRecarga(recargaData) {
        console.log('🖨️ [CFE] Iniciando proceso de impresión de ticket...', recargaData);
        
        try {
            if (typeof PrintTicket === 'undefined') {
                console.error('❌ [CFE] PrintTicket NO está disponible');
                return false;
            }
            console.log('✅ [CFE] PrintTicket disponible');

            if (typeof qz === 'undefined') {
                console.error('❌ [CFE] QZ Tray NO está disponible');
                return false;
            }
            console.log('✅ [CFE] QZ Tray disponible');

            console.log('📤 [CFE] Llamando a PrintTicket.imprimirTicketRecarga...');
            const resultado = await PrintTicket.imprimirTicketRecarga(recargaData);
            
            if (resultado) {
                console.log('✅ [CFE] Ticket impreso exitosamente!');
            } else {
                console.warn('⚠️ [CFE] PrintTicket retornó false - no se pudo imprimir');
            }
            
            return resultado;
        } catch (error) {
            console.error('❌ [CFE] Error al imprimir ticket:', error);
            console.error('Stack trace:', error.stack);
            return false;
        }
    }

    static async loadServices() {
        try {
            const cached = window.ServiceCache?.get('cfe');
            if (cached && Array.isArray(cached) && cached.length > 0) { 
                cfeData.services = cached; 
                return; 
            }
            const r = await fetch('cfe_luz_functional.php');
            const d = await r.json();
            if (!d.success || !Array.isArray(d.services)) throw new Error('CFE no disponible');
            const svc = d.services.find(s => s.sku && (s.sku.includes('S3LUZCFEONLINEMXN') || s.sku.includes('S3LUZCFEMXNV') || s.sku.includes('S3LUZCFEMXN')));
            if (!svc) throw new Error('Servicio CFE no encontrado');
            svc.name = 'Pago de Luz CFE';
            svc.description = 'Servicio de pago de luz';
            cfeData.services = [svc];
            window.ServiceCache?.set('cfe', [svc]);
        } catch (err) { 
            console.error('CFE loadServices error:', err);
            // 🔒 Fallback: Usar SKU por defecto si falla la carga
            cfeData.services = [{
                sku: 'S3LUZCFEONLINEMXN',
                name: 'Pago de Luz CFE',
                description: 'Servicio de pago de luz'
            }];
        }
    }

    static render() {
      // Evitar que se mezclen estilos entre servicios
      try {
        const currentKey = 'cfe';
        document
          .querySelectorAll('link[data-service-styles], style[data-service-styles]')
          .forEach((el) => {
            const key = el?.dataset?.serviceStyles;
            if (key && key !== currentKey) el.remove();
          });
        // Limpieza legacy por id (por si existe cache viejo)
        ['movistar-styles', 'telcel-styles', 'spotify-styles', 'netflix-styles', 'megacable-styles']
          .forEach((id) => {
            const el = document.getElementById(id);
            if (el) el.remove();
          });
      } catch { }

        if (!document.getElementById('cfe-styles')) {
            const style = document.createElement('style');
            style.id = 'cfe-styles';
        style.dataset.serviceStyles = 'cfe';
            style.textContent = `
        /* ===== CFE PROFESSIONAL DESIGN - ESTILO MOVISTAR ===== */
        
        /* Modal overlay con animación */
        #cfe-kiosk-root {
          position: fixed;
          inset: 0;
          z-index: 9998;
          background: linear-gradient(135deg, rgba(10, 25, 10, 0.95) 0%, rgba(20, 40, 15, 0.95) 100%);
          display: flex;
          align-items: center;
          justify-content: center;
          padding: 1rem;
          animation: fadeIn 0.4s ease-out;
          overflow: auto;
          backdrop-filter: blur(12px);
        }
        
        #cfe-kiosk-wrapper {
          width: 98%;
          max-width: none;
          max-height: 96vh;
          display: flex;
          align-items: center;
          justify-content: center;
        }
        
        /* Card principal */
        .cfe-main-card {
          width: 100%;
          max-height: 96vh;
          background: white;
          border-radius: 24px;
          box-shadow: 0 25px 80px rgba(126, 188, 18, 0.4), 0 0 1px rgba(0, 0, 0, 0.2);
          overflow: hidden;
          animation: slideUp 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
          border: 1px solid rgba(126, 188, 18, 0.1);
          display: flex;
          flex-direction: column;
          transform-origin: center;
        }
        
        /* Header con gradiente CFE verde */
        .cfe-header {
          background: linear-gradient(135deg, #7EBC12 0%, #9FD84D 100%);
          padding: 3rem 3rem;
          text-align: center;
          position: relative;
          overflow: hidden;
          flex-shrink: 0;
        }
        .cfe-header::before {
          content: '';
          position: absolute;
          top: 0;
          left: 0;
          right: 0;
          bottom: 0;
          background: 
            radial-gradient(circle at 20% 30%, rgba(255,255,255,0.15) 0%, transparent 50%),
            radial-gradient(circle at 80% 70%, rgba(255,255,255,0.1) 0%, transparent 50%);
          animation: headerPulse 4s ease-in-out infinite;
        }
        .cfe-header::after {
          content: '';
          position: absolute;
          top: 0;
          left: 0;
          right: 0;
          height: 5px;
          background: linear-gradient(90deg, #9FD84D 0%, #7EBC12 50%, #6BA410 100%);
          box-shadow: 0 0 20px rgba(159, 216, 77, 0.5);
        }
        .cfe-header img {
          max-width: 200px;
          height: auto;
          filter: brightness(1.1) drop-shadow(0 4px 20px rgba(0,0,0,0.2));
          position: relative;
          z-index: 1;
          animation: logoFloat 3s ease-in-out infinite;
        }
        .cfe-header h2 {
          color: white;
          margin: 1.5rem 0 0 0;
          font-weight: 700;
          font-size: 2.5rem;
          text-shadow: 0 3px 12px rgba(0,0,0,0.2);
          position: relative;
          z-index: 1;
          letter-spacing: -0.5px;
        }
        
        /* Badge de saldo monedero */
        .monedero-badge {
          position: absolute;
          top: 1.5rem;
          right: 2rem;
          background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
          padding: 0.75rem 1.5rem;
          border-radius: 12px;
          display: flex;
          align-items: center;
          gap: 0.5rem;
          box-shadow: 0 4px 20px rgba(255, 215, 0, 0.4);
          z-index: 2;
          border: 2px solid rgba(255, 255, 255, 0.3);
        }
        .monedero-icon {
          font-size: 1.5rem;
          filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
        }
        .monedero-label {
          color: #000;
          font-weight: 700;
          font-size: 0.95rem;
          text-shadow: 0 1px 2px rgba(255,255,255,0.5);
          letter-spacing: 0.3px;
        }
        .monedero-amount {
          color: #000;
          font-weight: 800;
          font-size: 1.3rem;
          text-shadow: 0 1px 2px rgba(255,255,255,0.5);
          font-family: 'SF Pro Display', -apple-system, system-ui, sans-serif;
        }
        
        @keyframes logoFloat {
          0%, 100% { transform: translateY(0px); }
          50% { transform: translateY(-8px); }
        }
        
        /* Body con scroll */
        .cfe-content {
          padding: 3.5rem;
          background: #fafbfc;
          overflow-y: auto;
          flex: 1;
        }
        
        /* Step indicator */
        .step-chip { 
          display: inline-flex;
          align-items: center;
          gap: 0.5rem;
          background: white;
          color: #7EBC12;
          font-weight: 600;
          font-size: 1.05rem;
          padding: 0.85rem 1.75rem;
          border-radius: 14px;
          border: 2px solid #d0f5dd;
          margin-bottom: 2.5rem;
          box-shadow: 0 3px 10px rgba(126, 188, 18, 0.1);
          transition: all 0.3s ease;
          animation: chipBounce 0.6s ease-out;
        }
        .step-chip:hover {
          border-color: #7EBC12;
          box-shadow: 0 5px 15px rgba(126, 188, 18, 0.2);
          transform: translateY(-2px);
        }
        
        @keyframes chipBounce {
          0% { transform: scale(0.8); opacity: 0; }
          50% { transform: scale(1.05); }
          100% { transform: scale(1); opacity: 1; }
        }
        
        .section-title {
          font-size: 1.4rem;
          font-weight: 600;
          color: #1e293b;
          margin-bottom: 1.5rem;
          letter-spacing: -0.3px;
        }
        
        /* Input groups */
        .input-wrap { 
          position: relative;
          margin-bottom: 1.5rem;
        }
        .input-wrap input {
          width: 100%;
          padding: 1.25rem 4rem 1.25rem 1.5rem;
          border: 2px solid #e2e8f0;
          border-radius: 16px;
          font-size: 1.15rem;
          transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
          background: white;
          color: #1e293b;
          font-weight: 500;
        }
        .input-wrap input::placeholder {
          color: #94a3b8;
          font-weight: 400;
        }
        .input-wrap input:focus {
          outline: none;
          border-color: #7EBC12;
          background: white;
          box-shadow: 0 0 0 5px rgba(126, 188, 18, 0.1), 0 8px 16px rgba(126, 188, 18, 0.15);
          transform: translateY(-2px);
        }
        .state-icon {
          position: absolute;
          right: 1.5rem;
          top: 50%;
          transform: translateY(-50%);
          font-weight: 700;
          font-size: 1.5rem;
          transition: all 0.3s ease;
        }
        .state-icon.ok { 
          color: #10b981; 
          animation: checkBounce 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }
        .state-icon.err { 
          color: #ef4444;
          animation: errorShake 0.5s ease;
        }
        
        @keyframes checkBounce {
          0% { transform: translateY(-50%) scale(0); }
          50% { transform: translateY(-50%) scale(1.3); }
          100% { transform: translateY(-50%) scale(1); }
        }
        @keyframes errorShake {
          0%, 100% { transform: translateY(-50%) translateX(0); }
          25% { transform: translateY(-50%) translateX(-8px); }
          75% { transform: translateY(-50%) translateX(8px); }
        }
        
        /* Error message */
        .error-message {
          color: #dc2626;
          font-size: 0.9rem;
          font-weight: 600;
          margin-top: 0.5rem;
          display: none;
          padding: 0.75rem 1rem;
          background: #fef2f2;
          border-left: 3px solid #dc2626;
          border-radius: 8px;
        }
        
        /* Hint card */
        .hint-card {
          background: white;
          border: 2px solid #d0f5dd;
          border-radius: 16px;
          padding: 1.75rem;
          margin-bottom: 2rem;
          box-shadow: 0 2px 8px rgba(126, 188, 18, 0.06);
        }
        .hint-list {
          list-style: none;
          padding: 0;
          margin: 0;
        }
        .hint-item {
          display: flex;
          align-items: flex-start;
          gap: 1rem;
          margin-bottom: 1rem;
          font-size: 0.95rem;
          color: #475569;
          line-height: 1.6;
        }
        .hint-item:last-child { margin-bottom: 0; }
        .hint-icon {
          display: inline-flex;
          align-items: center;
          justify-content: center;
          min-width: 28px;
          height: 28px;
          background: linear-gradient(135deg, #7EBC12 0%, #9FD84D 100%);
          color: white;
          border-radius: 50%;
          font-size: 0.8rem;
          font-weight: 700;
          box-shadow: 0 2px 8px rgba(126, 188, 18, 0.25);
        }
        
        /* Step header */
        .step-header {
          display: flex;
          justify-content: space-between;
          align-items: center;
          margin-bottom: 2rem;
          padding-bottom: 1.5rem;
          border-bottom: 2px solid #e2e8f0;
          flex-wrap: wrap;
          gap: 1rem;
        }
        
        /* Button group */
        .button-group {
          display: flex;
          gap: 1rem;
          margin-top: 2.5rem;
          padding-top: 2rem;
          border-top: 2px solid #e2e8f0;
          justify-content: flex-end;
          align-items: center;
        }
        
        /* Modern buttons */
        .modern-btn {
          padding: 1rem 2rem;
          border: none;
          border-radius: 12px;
          font-weight: 600;
          font-size: 1rem;
          cursor: pointer;
          transition: all 0.3s ease;
          position: relative;
          overflow: hidden;
          min-width: 140px;
          letter-spacing: 0.2px;
        }
        .modern-btn::before {
          content: '';
          position: absolute;
          top: 50%;
          left: 50%;
          width: 0;
          height: 0;
          border-radius: 50%;
          background: rgba(255,255,255,0.25);
          transform: translate(-50%, -50%);
          transition: width 0.6s, height 0.6s;
        }
        .modern-btn:hover::before {
          width: 300px;
          height: 300px;
        }
        .modern-btn:active {
          transform: scale(0.98);
        }
        
        .btn-primary-cfe {
          background: linear-gradient(135deg, #7EBC12 0%, #9FD84D 100%);
          color: white;
          box-shadow: 0 4px 14px rgba(126, 188, 18, 0.35);
        }
        .btn-primary-cfe:hover {
          transform: translateY(-2px);
          box-shadow: 0 6px 20px rgba(126, 188, 18, 0.45);
        }
        .btn-primary-cfe:disabled {
          opacity: 0.5;
          cursor: not-allowed;
          transform: none;
          box-shadow: 0 2px 8px rgba(126, 188, 18, 0.2);
        }
        
        .btn-secondary-cfe {
          background: white;
          color: #475569;
          border: 2px solid #e2e8f0;
          box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
        .btn-secondary-cfe:hover {
          background: #f8fafc;
          border-color: #cbd5e1;
          transform: translateY(-1px);
          box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }
        
        /* Step screens */
        .step-screen {
          opacity: 1;
          transform: translateX(0);
          transition: opacity 0.3s ease, transform 0.3s ease;
        }
        .hidden-step {
          display: none !important;
        }
        .pre-enter-right {
          opacity: 0;
          transform: translateX(20px);
        }
        .pre-enter-left {
          opacity: 0;
          transform: translateX(-20px);
        }
        .slide-out-left {
          opacity: 0;
          transform: translateX(-20px);
        }
        .slide-out-right {
          opacity: 0;
          transform: translateX(20px);
        }
        
        /* Animations */
        @keyframes fadeIn {
          from {
            opacity: 0;
            backdrop-filter: blur(0px);
          }
          to {
            opacity: 1;
            backdrop-filter: blur(12px);
          }
        }
        @keyframes slideUp {
          from {
            opacity: 0;
            transform: translateY(60px) scale(0.95);
          }
          to {
            opacity: 1;
            transform: translateY(0) scale(1);
          }
        }
        @keyframes headerPulse {
          0%, 100% {
            transform: scale(1) rotate(0deg);
            opacity: 1;
          }
          50% {
            transform: scale(1.08) rotate(1deg);
            opacity: 0.9;
          }
        }
        
        /* Responsive */
        @media (max-width: 768px) {
          #cfe-kiosk-root {
            padding: 0;
          }
          .cfe-main-card {
            width: 100%;
            border-radius: 0;
            min-height: 100vh;
            max-width: 100%;
            max-height: 100vh;
          }
          .cfe-header {
            padding: 2.5rem 2rem;
          }
          .cfe-header img {
            max-width: 150px;
          }
          .cfe-header h2 {
            font-size: 2rem;
          }
          .cfe-content {
            padding: 2.5rem 2rem;
          }
          .button-group {
            flex-direction: column;
          }
          .modern-btn { 
            width: 100%; 
            min-width: unset;
            padding: 1.25rem 2rem;
            font-size: 1.1rem;
          }
          .step-header {
            flex-direction: column;
            align-items: stretch;
          }
          .section-title {
            font-size: 1.3rem;
          }
        }
        
        @media (min-width: 769px) and (max-width: 1200px) {
          .cfe-main-card {
            width: 96%;
            max-width: none;
          }
          .cfe-content {
            padding: 3rem;
          }
        }
        
        @media (min-width: 1201px) {
          .cfe-main-card {
            width: 96%;
            max-width: none;
          }
          .cfe-content {
            padding: 3.5rem 4.5rem;
          }
        }
        
        @media (min-width: 1600px) {
          .cfe-main-card {
            width: 94%;
            max-width: none;
          }
          .cfe-content {
            padding: 4rem 5rem;
          }
        }
        
        @media (max-width: 900px) and (orientation: landscape) {
          .cfe-main-card {
            max-height: 98vh;
            width: 100%;
          }
          .cfe-header {
            padding: 1.75rem 2rem;
          }
          .cfe-header img {
            max-width: 120px;
          }
          .cfe-header h2 {
            font-size: 1.6rem;
            margin-top: 0.8rem;
          }
          .cfe-content {
            padding: 2rem;
          }
        }
        
        @media (max-width: 480px) {
          .cfe-header h2 {
            font-size: 1.8rem;
          }
          .section-title {
            font-size: 1.2rem;
          }
        }
        
        /* Footer styles */
        #cfe-kiosk-footer {
          padding: 1.5rem 2rem;
          background: white;
          border-top: 2px solid #e2e8f0;
          display: flex;
          justify-content: center;
          align-items: center;
          gap: 1rem;
          box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.05);
          flex-shrink: 0;
          border-radius: 0 0 24px 24px;
        }
        
        #cfe-kiosk-footer .modern-btn {
          min-width: 200px;
        }
        
        @media (max-width: 768px) {
          #cfe-kiosk-footer {
            padding: 1.25rem 1.5rem;
          }
          #cfe-kiosk-footer .modern-btn {
            width: 100%;
            padding: 1.25rem 2rem;
          }
        }
      `;
            document.head.appendChild(style);
        }

        const bodyHTML = `
          <div class="cfe-main-card">
            <div class="cfe-header">
              <img src="https://fbi.cults3d.com/uploaders/30216226/illustration-file/9fc55ced-5c0e-49dc-b294-3186f7f5336f/Comisi%C3%B3n_Federal_de_Electricidad_-logo-_.svg.png" alt="CFE" />
              <h2>Pago de Luz CFE</h2>
              <div class="monedero-badge">
                <span class="monedero-icon">💰</span>
                <span class="monedero-label">Saldo:</span>
                <span class="monedero-amount" id="cfe-saldo">$${CFEServices.getSaldoActual().toFixed(2)}</span>
              </div>
            </div>
            
            <div class="cfe-content">
              <div id="cfe-step-1" class="step-screen">
                <div class="step-chip">⚡ Paso 1: Ingresa tu número de servicio</div>
                
                <div class="section-title">Número de servicio</div>
                
                <div class="hint-card">
                  <ul class="hint-list">
                    <li class="hint-item"><span class="hint-icon">①</span><span>Escribe tu número de servicio (10-27 dígitos)</span></li>
                    <li class="hint-item"><span class="hint-icon">②</span><span>Confírmalo en el segundo campo</span></li>
                    <li class="hint-item"><span class="hint-icon">③</span><span>Haz clic en "Continuar"</span></li>
                  </ul>
                </div>

                <div class="input-wrap">
                  <input type="tel" id="cfe-service-number" placeholder="Número de servicio (10-27 dígitos)" inputmode="numeric" pattern="[0-9]*" autocomplete="off" autofocus />
                  <span class="state-icon" id="cfe-s1"></span>
                </div>
                
                <div class="input-wrap">
                  <input type="tel" id="cfe-service-number-2" placeholder="Confirma el número" inputmode="numeric" pattern="[0-9]*" />
                  <span class="state-icon" id="cfe-s2"></span>
                </div>
                
                <div id="cfe-number-error" class="error-message">Los números no coinciden</div>
                
                <div class="button-group">
                  <button id="cfe-next" class="modern-btn btn-primary-cfe" disabled>Continuar →</button>
                </div>
              </div>
              
              <div id="cfe-step-2" class="step-screen hidden-step">
                <div class="step-header">
                  <div class="step-chip">💰 Paso 2: Ingresa el monto a pagar</div>
                  <button id="cfe-back" class="modern-btn btn-secondary-cfe">← Regresar</button>
                </div>
                
                <div class="hint-card">
                  <div class="hint-item">
                    <span class="hint-icon">ℹ</span>
                    <span>Ingresa el monto exacto que aparece en tu recibo de CFE</span>
                  </div>
                </div>

                <div class="input-wrap">
                  <input type="number" id="cfe-amount" placeholder="Monto a pagar" min="1" step="0.01" />
                </div>
                
                <div id="cfe-amount-error" class="error-message">Monto inválido (debe ser mayor a 0)</div>
                
                <div style="font-size:0.95rem; color:#64748b; margin-top:1rem; padding:0.75rem 1rem; background:#f8fafc; border-radius:10px; font-weight:600;">
                  Comisión: $12.00
                </div>
                
                <div class="button-group" style="margin-top: 3rem;">
                  <button class="modern-btn btn-primary-cfe" id="process-cfe" disabled>💳 Pagar</button>
                </div>
              </div>
            </div>
            
            <div id="cfe-kiosk-footer">
              <button class="modern-btn btn-secondary-cfe" id="cancel-service">Cerrar</button>
            </div>
          </div>
        `;

        const footerHTML = ``;

        CFEServices.openOverlay({ title: 'Pago de Luz CFE', bodyHTML, footerHTML });
        
        // 🎯 FOCUS AUTOMÁTICO: Focus en el primer input después de que se renderice
        setTimeout(() => {
            const firstInput = document.querySelector('#cfe-service-number');
            if (firstInput) {
                firstInput.focus();
                firstInput.select(); // Selecciona todo si ya hay algo
            }
        }, 100);
    }

    static wire() {
        const body = document.querySelector('.cfe-main-card');
        const footer = document.getElementById('cfe-kiosk-footer');
        if (!body || !footer) return;

        const isValid = v => /^\d{12,27}$/.test(v || '');
        const validate = () => {
            const p1 = (body.querySelector('#cfe-service-number')?.value || '').replace(/\D/g, '');
            const p2 = (body.querySelector('#cfe-service-number-2')?.value || '').replace(/\D/g, '');
            const ok = isValid(p1), cok = isValid(p2), match = ok && cok && p1 === p2;
            const s1 = body.querySelector('#cfe-s1'), s2 = body.querySelector('#cfe-s2');
            const err = body.querySelector('#cfe-number-error');
            const next = body.querySelector('#cfe-next');
            const input1 = body.querySelector('#cfe-service-number');
            const input2 = body.querySelector('#cfe-service-number-2');
            
            if (input1 && input1.value !== p1) input1.value = p1;
            if (input2 && input2.value !== p2) input2.value = p2;
            if (s1) { s1.textContent = ok ? '✓' : (p1 ? '!' : ''); s1.className = 'state-icon ' + (ok ? 'ok' : (p1 ? 'err' : '')); }
            if (s2) { s2.textContent = cok ? (match ? '✓' : '!') : (p2 ? '!' : ''); s2.className = 'state-icon ' + (match ? 'ok' : (p2 ? 'err' : '')); }
            if (err) err.style.display = (p2 && !match) ? 'block' : 'none';
            if (match) {
              next.innerText = 'Espere...';
              const ref = body.querySelector('#cfe-service-number').value;
              const params = new URLSearchParams({ saldo: '1', svc: 'cfe', ref });
              if (window.ProntiServices?.data) params.set('data', JSON.stringify(window.ProntiServices.data));
              fetch('prontipagos_proxy.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: params }).then(res => res.json()).then(data => {
                console.log(data);
                let texto = data['additionalInfo'];
                let posicion = texto.indexOf("$");
                let monto = texto.slice(posicion + 1); 
                console.log('Datos de servicios CFE recibidos:', monto);
                body.querySelector('#cfe-amount').value = parseFloat(monto).toFixed(2);
                // cfeData = data; // Guardar datos para uso posterior
                next.disabled = !match;
                next.innerText = 'Continuar →';
              });
            }
            
            // 🎯 AUTO-FOCUS: Cuando el primer input es válido, mover al segundo
            if (ok && p1.length >= 12 && document.activeElement === input1 && input2) {
                setTimeout(() => {
                    input2.focus();
                    input2.select();
                }, 100);
            }
            
            const amountInput = body.querySelector('#cfe-amount');
            const amtErr = body.querySelector('#cfe-amount-error');
            const payBtn = body.querySelector('#process-cfe');
            const amt = parseFloat(amountInput?.value);
            const validAmt = !isNaN(amt) && amt > 0;
            if (amtErr) amtErr.style.display = amountInput?.value && !validAmt ? 'block' : 'none';
            if (payBtn) payBtn.disabled = !validAmt || !match;
        };

        const toStep2 = () => {
            const step1 = body.querySelector('#cfe-step-1');
            const step2 = body.querySelector('#cfe-step-2');
            step2?.classList.add('pre-enter-right');
            step1?.classList.add('slide-out-left');
            setTimeout(() => {
                step1?.classList.add('hidden-step');
                step1?.classList.remove('slide-out-left');
                step2?.classList.remove('hidden-step');
                setTimeout(() => {
                    step2?.classList.remove('pre-enter-right');
                    // 🎯 FOCUS: Al pasar al paso 2, focus en el input del monto
                    const amountInput = body.querySelector('#cfe-amount');
                    if (amountInput) {
                        amountInput.focus();
                        amountInput.select();
                    }
                }, 10);
            }, 180);
        };

        body.addEventListener('input', validate);
        body.addEventListener('keyup', (e) => { 
            if (e.key === 'Enter' && !body.querySelector('#cfe-next')?.disabled) toStep2(); 
        });
        body.querySelector('#cfe-next')?.addEventListener('click', (e) => { 
            e.preventDefault(); 
            if (!e.currentTarget.disabled) toStep2(); 
        });
        
        // 🎯 ENTER en el segundo input: avanzar al siguiente paso
        body.querySelector('#cfe-service-number-2')?.addEventListener('keyup', (e) => {
            if (e.key === 'Enter' && !body.querySelector('#cfe-next')?.disabled) {
                toStep2();
            }
        });
        body.querySelector('#cfe-back')?.addEventListener('click', () => {
            const step1 = body.querySelector('#cfe-step-1');
            const step2 = body.querySelector('#cfe-step-2');
            step1?.classList.add('pre-enter-left');
            step2?.classList.add('slide-out-right');
            setTimeout(() => {
                step2?.classList.add('hidden-step');
                step2?.classList.remove('slide-out-right');
                step1?.classList.remove('hidden-step');
                setTimeout(() => {
                    step1?.classList.remove('pre-enter-left');
                    // 🎯 FOCUS: Al regresar, volver al primer input
                    const firstInput = body.querySelector('#cfe-service-number');
                    if (firstInput) {
                        firstInput.focus();
                        firstInput.select();
                    }
                }, 10);
            }, 180);
        });

        body.addEventListener('click', (e) => {
            if (e.target.closest('#process-cfe')) {
                const serviceNumber = (body.querySelector('#cfe-service-number')?.value || '').replace(/\D/g, '');
                const amount = parseFloat(body.querySelector('#cfe-amount')?.value);
                if (!/^\d{12,27}$/.test(serviceNumber) || isNaN(amount) || amount <= 0) { Swal.fire('Error', 'Completa todos los campos correctamente', 'error'); return; }
                
                const commission = 12;
                const total = amount + commission;
                
                // 🔒 Obtener SKU de forma segura
                const cfeSku = (cfeData.services && Array.isArray(cfeData.services) && cfeData.services.length > 0 && cfeData.services[0]?.sku) 
                    ? cfeData.services[0].sku 
                    : 'S3LUZCFEONLINEMXN'; // Fallback SKU
                
                const saldoActual = CFEServices.getSaldoActual();

                // 💰 Si no tiene saldo suficiente, mostrar opciones
                if (saldoActual < total) {
                  const faltante = total - saldoActual;
                  Swal.fire({
                    title: '⚠️ Saldo insuficiente',
                    html: `
                      <div style="text-align: left; padding: 1.5rem;">
                        <div style="background: #fff3cd; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 2px solid #00A758;">
                          <p style="margin: 5px 0;"><strong>💰 Saldo actual:</strong> <span style="color: #dc2626;">$${saldoActual.toFixed(2)}</span></p>
                          <p style="margin: 5px 0;"><strong>💳 Total necesario:</strong> <span style="color: #7c3aed;">$${total.toFixed(2)}</span></p>
                          <hr style="margin: 10px 0; border-top: 2px dashed #00A758;">
                          <p style="margin: 5px 0; font-size: 1.2rem;"><strong>❌ Te faltan:</strong> <span style="color: #00A758; font-weight: 800;">$${faltante.toFixed(2)}</span></p>
                        </div>
                        <p style="text-align: center; color: #666; margin-bottom: 15px;">Elige una opción para continuar:</p>
                        <div style="display: grid; gap: 10px;">
                          <button id="insert-coins-btn" class="swal2-confirm swal2-styled" style="background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%); color: #000;">
                            🪙 Insertar $${faltante.toFixed(2)} más en monedas
                          </button>
                          <button id="pay-card-btn" class="swal2-confirm swal2-styled" style="background: linear-gradient(135deg, #7c3aed 0%, #6366f1 100%);">
                            💳 Pagar con tarjeta Point
                          </button>
                        </div>
                      </div>
                    `,
                    icon: 'warning',
                    showConfirmButton: false,
                    showCancelButton: true,
                    cancelButtonText: 'Cancelar',
                    didOpen: () => {
                      document.getElementById('insert-coins-btn').addEventListener('click', () => {
                        Swal.close();
                        Swal.fire({
                          title: '🪙 Insertando monedas...',
                          html: `
                            <p style="font-size: 1.1rem; margin: 20px 0;">Inserta <strong style="color: #00A758;">$${faltante.toFixed(2)}</strong> más en monedas o billetes</p>
                            <p style="color: #666;">El saldo se actualizará automáticamente</p>
                            <p style="margin-top: 20px;"><strong>Saldo actual:</strong> <span id="live-saldo" style="color: #7c3aed; font-size: 1.3rem;">$${saldoActual.toFixed(2)}</span></p>
                          `,
                          icon: 'info',
                          showCancelButton: true, 
                          showConfirmButton: true,
                          confirmButtonText: 'Ya inserté el dinero',
                          cancelButtonText: 'Cancelar',
                          allowOutsideClick: false,
                          didOpen: () => {
                            const updateSaldo = () => {
                              const nuevoSaldo = CFEServices.getSaldoActual();
                              const liveSaldoEl = document.getElementById('live-saldo');
                              if (liveSaldoEl) {
                                liveSaldoEl.textContent = `$${nuevoSaldo.toFixed(2)}`;
                                if (nuevoSaldo >= total) {
                                  liveSaldoEl.style.color = '#10b981';
                                }
                              }
                            };
                            window.addEventListener('monederoSaldoChanged', updateSaldo);
                            Swal.getPopup().addEventListener('DOMNodeRemoved', () => {
                              window.removeEventListener('monederoSaldoChanged', updateSaldo);
                            });
                          }
                        }).then((result) => {
                          if (result.isConfirmed) {
                            const nuevoSaldo = CFEServices.getSaldoActual();
                            if (nuevoSaldo >= total) {
                              e.target.closest("#process-cfe").click();
                            } else {
                              Swal.fire('⚠️ Saldo insuficiente', `Aún te faltan $${(total - nuevoSaldo).toFixed(2)}`, 'warning');
                            }
                          }
                        });
                      });
                      
                      document.getElementById('pay-card-btn').addEventListener('click', () => {
                        Swal.close();
                        cfeData.procesandoPago = true;
                        window.ServicePaymentHandler.processWithPoint({
                          serviceName: 'Pago de Luz CFE',
                          reference: serviceNumber,
                          amount: amount,
                          commission: commission,
                          sku: cfeSku,
                          requiresRegionalization: false
                        }, async (paymentResult) => {
                          if (paymentResult && paymentResult.success) {
                            const codeDesc = paymentResult?.data?.payload?.codeDescription || 
                                           paymentResult?.result?.data?.payload?.codeDescription ||
                                           'Exitosa';
                            const metodoPago = paymentResult?.method || 'point';
                            await Swal.fire('✅ ¡Pago exitoso!', `Método: ${metodoPago === 'point' ? 'Tarjeta Point' : 'Efectivo'}`, 'success');
                            CFEServices.closeOverlay();
                          } else {
                            cfeData.procesandoPago = false;
                          }
                        });
                      });
                    }
                  });
                  return;
                }
                
                // 🚀 Usar payment handler con Point + Efectivo
                if (window.ServicePaymentHandler) {
                    cfeData.procesandoPago = true;
                    
                    window.ServicePaymentHandler.showPaymentOptions({
                        serviceName: 'Pago de Luz CFE',
                        reference: serviceNumber,
                        amount: amount,
                        commission: commission,
                        sku: cfeSku,
                        requiresRegionalization: false
                    }, async (paymentResult) => {
                        console.log('💳 [CFE] Callback del payment handler recibido:', paymentResult);
                        
                        if (paymentResult && paymentResult.success) {
                            const codeDesc = paymentResult?.data?.payload?.codeDescription || 
                                           paymentResult?.result?.data?.payload?.codeDescription ||
                                           'Exitosa';
                            const metodoPago = paymentResult?.method || 'cash';
                            
                            console.log('✅ [CFE] Pago exitoso! Procesando...', { metodoPago });
                            
                            await CFEServices.mostrarRecargaExitosa('Pago de Luz CFE', serviceNumber, amount, codeDesc, metodoPago);
                            CFEServices.actualizarBadgeSaldo();
                            CFEServices.closeOverlay();
                        } else {
                            console.error('❌ [CFE] Pago no exitoso o callback sin success:', paymentResult);
                            cfeData.procesandoPago = false;
                        }
                    });
                } else {
                    // Fallback legacy
                    const totalAmount = amount + commission;
                    const cfeSku = (cfeData.services && cfeData.services[0]) ? cfeData.services[0].sku : '';
                    console.log(cfeSku);
                    Swal.fire({ title: '¿Confirmar pago?', html: `<div style="text-align:left; padding:10px;"><p><strong>Servicio:</strong> Pago de Luz CFE</p><p><strong>Número:</strong> ${serviceNumber}</p><p><strong>Monto:</strong> $${amount.toFixed(2)}</p><p><strong>Comisión:</strong> $${commission.toFixed(2)}</p><p><strong>Total:</strong> $${totalAmount.toFixed(2)}</p></div>`, icon: 'question', showCancelButton: true, confirmButtonText: 'Pagar', cancelButtonText: 'Cancelar' }).then((res) => {
                        if (res.isConfirmed) {
                            Swal.fire({ title: 'Procesando...', allowOutsideClick: false, showConfirmButton: false, didOpen: () => Swal.showLoading() });
                            const payParams = new URLSearchParams({ comprar: '1', svc: cfeSku, ref: serviceNumber, amount });
                            fetch(`prontipagos_proxy.php?${payParams}`, { headers: { 'Accept': 'application/json' }, cache: 'no-store' })
                                .then(r => r.json())
                                .then(d => { if (d?.codeTransaction === '00') { Swal.fire('¡Pago exitoso!', '', 'success').then(() => CFEServices.closeOverlay()); } else { Swal.fire('Error', d?.codeDescription || 'Error', 'error'); } });
                        }
                    });
                }
            }
        });

        footer.addEventListener('click', (e) => {
            if (e.target.closest('#cancel-service')) { CFEServices.closeOverlay(); return; }
        });
        validate();
    }
}

CFEServices.openOverlay = ({ title, bodyHTML, footerHTML }) => {
    let root = document.getElementById('cfe-kiosk-root');
    if (!root) {
        root = document.createElement('div');
        root.id = 'cfe-kiosk-root';
        document.body.appendChild(root);
        root.innerHTML = `
          <div id="cfe-kiosk-wrapper">
            ${bodyHTML}
          </div>
        `;
        try { CFEServices._prev = { h: document.documentElement.style.overflow, b: document.body.style.overflow }; document.documentElement.style.overflow = 'hidden'; document.body.style.overflow = 'hidden'; } catch { }
    }
};

CFEServices.closeOverlay = () => {
    CFEServices.detenerSuscripcionSaldo();
    const root = document.getElementById('cfe-kiosk-root');
    if (root) root.remove();
  // Quitar CSS para no contaminar otros servicios/pantallas
  try {
    const styles = document.getElementById('cfe-styles');
    if (styles) styles.remove();
  } catch { }
    try { if (CFEServices._prev) { document.documentElement.style.overflow = CFEServices._prev.h || ''; document.body.style.overflow = CFEServices._prev.b || ''; CFEServices._prev = null; } } catch { }
};
