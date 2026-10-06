let telmexData = { services: null };
let _loadingTelmex = null;

export class TelmexService {
    static async init() {
        try {
            this.render();
            this.wire();
            this.loadServices();
        } catch (e) {
            Swal.fire('Error', 'No se pudieron cargar los servicios de Telmex', 'error');
        }
    }

    static async loadServices() {
        if (_loadingTelmex) return _loadingTelmex;
        telmexData.services = [];
        _loadingTelmex = (async () => {
            try {
                if (window.ProntiServices) {
                    await window.ProntiServices.load();
                    const raw = window.ProntiServices.getProducts('telmex');
                    const found = raw.find(p => (p.name || '').toUpperCase().includes('TELEFONIA TELMEX'));
                    if (found) {
                        telmexData.services = [found];
                        return;
                    }
                }
                const r = await fetch('prontipagos_proxy.php?servicio=telmex');
                const d = await r.json();
                const list = Array.isArray(d) ? d : [];
                const found = list.find(p => p.sku === 'TELMEXINT' || p.sku === 'TELMEXTEL');
                telmexData.services = found ? [found] : [];
            } catch {} finally { _loadingTelmex = null; }
            if (!telmexData.services || telmexData.services.length === 0) {
                telmexData.services = [{ sku: 'TELMEXINT', name: 'SERVICIO TELMEX' }];
            }
        })();
        return _loadingTelmex;
    }

    static async load() {
        if (telmexData.services === null || telmexData.services.length === 0) await this.loadServices();
    }

    static render() {
        if (!document.getElementById('telmex-styles')) {
            const style = document.createElement('style');
            style.id = 'telmex-styles';
            style.textContent = `
        #telmex-kiosk-root {
          position: fixed;
          inset: 0;
          z-index: 99999;
          background: linear-gradient(135deg, rgba(30, 10, 10, 0.95) 0%, rgba(50, 15, 15, 0.95) 100%);
          display: flex;
          align-items: center;
          justify-content: center;
          padding: 1rem;
          animation: fadeIn 0.4s ease-out;
          overflow: auto;
          backdrop-filter: blur(12px);
        }

        #telmex-kiosk-wrapper {
          width: 98%;
          max-width: none;
          max-height: 96vh;
          display: flex;
          align-items: center;
          justify-content: center;
        }

        .telmex-main-card {
          width: 100%;
          max-height: 96vh;
          background: white;
          border-radius: 24px;
          box-shadow: 0 25px 80px rgba(237, 28, 36, 0.4), 0 0 1px rgba(0, 0, 0, 0.2);
          overflow: hidden;
          animation: slideUp 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
          border: 1px solid rgba(237, 28, 36, 0.1);
          display: flex;
          flex-direction: column;
          transform-origin: center;
        }

        .telmex-header {
          background: linear-gradient(135deg, #0099CC 0%, #007799 100%);
          padding: 3rem 3rem;
          text-align: center;
          position: relative;
          overflow: hidden;
          flex-shrink: 0;
        }
        .telmex-header::before {
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
        .telmex-header::after {
          content: '';
          position: absolute;
          top: 0;
          left: 0;
          right: 0;
          height: 5px;
          background: linear-gradient(90deg, #ff6b6b 0%, #0099CC 50%, #007799 100%);
          box-shadow: 0 0 20px rgba(237, 28, 36, 0.5);
        }
        .telmex-header img {
          max-width: 200px;
          height: auto;
          filter: brightness(1.1) drop-shadow(0 4px 20px rgba(0,0,0,0.2));
          position: relative;
          z-index: 1;
          animation: logoFloat 3s ease-in-out infinite;
        }
        .telmex-header h2 {
          color: white;
          margin: 1.5rem 0 0 0;
          font-weight: 700;
          font-size: 2.5rem;
          text-shadow: 0 3px 12px rgba(0,0,0,0.2);
          position: relative;
          z-index: 1;
          letter-spacing: -0.5px;
        }

        .telmex-content {
          padding: 3.5rem;
          background: #fafbfc;
          overflow-y: auto;
          flex: 1;
        }

        .step-chip {
          display: inline-flex;
          align-items: center;
          gap: 0.5rem;
          background: white;
          color: #0099CC;
          font-weight: 600;
          font-size: 1.05rem;
          padding: 0.85rem 1.75rem;
          border-radius: 14px;
          border: 2px solid #d6f0ff;
          margin-bottom: 2.5rem;
          box-shadow: 0 3px 10px rgba(237, 28, 36, 0.1);
          transition: all 0.3s ease;
          animation: chipBounce 0.6s ease-out;
        }
        .step-chip:hover {
          border-color: #0099CC;
          box-shadow: 0 5px 15px rgba(237, 28, 36, 0.2);
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
          border-color: #0099CC;
          background: white;
          box-shadow: 0 0 0 5px rgba(237, 28, 36, 0.1), 0 8px 16px rgba(237, 28, 36, 0.15);
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

        .hint-card {
          background: white;
          border: 2px solid #d6f0ff;
          border-radius: 16px;
          padding: 1.75rem;
          margin-bottom: 2rem;
          box-shadow: 0 2px 8px rgba(237, 28, 36, 0.06);
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
          background: linear-gradient(135deg, #0099CC 0%, #007799 100%);
          color: white;
          border-radius: 50%;
          font-size: 0.8rem;
          font-weight: 700;
          box-shadow: 0 2px 8px rgba(237, 28, 36, 0.25);
        }

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

        .button-group {
          display: flex;
          gap: 1rem;
          margin-top: 2.5rem;
          padding-top: 2rem;
          border-top: 2px solid #e2e8f0;
          justify-content: flex-end;
          align-items: center;
        }

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

        .btn-primary-telmex {
          background: linear-gradient(135deg, #0099CC 0%, #007799 100%);
          color: white;
          box-shadow: 0 4px 14px rgba(237, 28, 36, 0.35);
        }
        .btn-primary-telmex:hover {
          transform: translateY(-2px);
          box-shadow: 0 6px 20px rgba(237, 28, 36, 0.45);
        }
        .btn-primary-telmex:disabled {
          opacity: 0.5;
          cursor: not-allowed;
          transform: none;
          box-shadow: 0 2px 8px rgba(237, 28, 36, 0.2);
        }

        .btn-secondary-telmex {
          background: white;
          color: #475569;
          border: 2px solid #e2e8f0;
          box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
        .btn-secondary-telmex:hover {
          background: #f8fafc;
          border-color: #cbd5e1;
          transform: translateY(-1px);
          box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

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

        @keyframes fadeIn {
          from { opacity: 0; backdrop-filter: blur(0px); }
          to { opacity: 1; backdrop-filter: blur(12px); }
        }
        @keyframes slideUp {
          from { opacity: 0; transform: translateY(60px) scale(0.95); }
          to { opacity: 1; transform: translateY(0) scale(1); }
        }
        @keyframes headerPulse {
          0%, 100% { transform: scale(1) rotate(0deg); opacity: 1; }
          50% { transform: scale(1.08) rotate(1deg); opacity: 0.9; }
        }

        @media (max-width: 768px) {
          #telmex-kiosk-root { padding: 0; }
          .telmex-main-card { width: 100%; border-radius: 0; min-height: 100vh; max-width: 100%; max-height: 100vh; }
          .telmex-header { padding: 2.5rem 2rem; }
          .telmex-header img { max-width: 150px; }
          .telmex-header h2 { font-size: 2rem; }
          .telmex-content { padding: 2.5rem 2rem; }
          .button-group { flex-direction: column; }
          .modern-btn { width: 100%; min-width: unset; padding: 1.25rem 2rem; font-size: 1.1rem; }
          .step-header { flex-direction: column; align-items: stretch; }
          .section-title { font-size: 1.3rem; }
        }

        @media (min-width: 769px) and (max-width: 1200px) {
          .telmex-main-card { width: 96%; max-width: none; }
          .telmex-content { padding: 3rem; }
        }

        @media (min-width: 1201px) {
          .telmex-main-card { width: 96%; max-width: none; }
          .telmex-content { padding: 3.5rem 4.5rem; }
        }

        @media (min-width: 1600px) {
          .telmex-main-card { width: 94%; max-width: none; }
          .telmex-content { padding: 4rem 5rem; }
        }

        @media (max-width: 900px) and (orientation: landscape) {
          .telmex-main-card { max-height: 98vh; width: 100%; }
          .telmex-header { padding: 1.75rem 2rem; }
          .telmex-header img { max-width: 120px; }
          .telmex-header h2 { font-size: 1.6rem; margin-top: 0.8rem; }
          .telmex-content { padding: 2rem; }
        }

        @media (max-width: 480px) {
          .telmex-header h2 { font-size: 1.8rem; }
          .section-title { font-size: 1.2rem; }
        }

        #telmex-kiosk-footer {
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

        #telmex-kiosk-footer .modern-btn {
          min-width: 200px;
        }

        @media (max-width: 768px) {
          #telmex-kiosk-footer { padding: 1.25rem 1.5rem; }
          #telmex-kiosk-footer .modern-btn { width: 100%; padding: 1.25rem 2rem; }
        }
      `;
            document.head.appendChild(style);
        }

        const bodyHTML = `
          <div class="telmex-main-card">
            <div class="telmex-header">
              <img src="images/services/telmex.png" alt="Telmex" />
              <h2>Servicios Telmex</h2>
            </div>

            <div class="telmex-content">
              <div id="telmex-step-1" class="step-screen">
                <div class="step-chip">📞 Paso 1: Ingresa tu número telefónico</div>

                <div class="section-title">Número telefónico</div>

                <div class="hint-card">
                  <ul class="hint-list">
                    <li class="hint-item"><span class="hint-icon">①</span><span>Escribe tu número telefónico (10 dígitos)</span></li>
                    <li class="hint-item"><span class="hint-icon">②</span><span>Confírmalo en el segundo campo</span></li>
                    <li class="hint-item"><span class="hint-icon">③</span><span>Haz clic en "Continuar"</span></li>
                  </ul>
                </div>

                <div class="input-wrap">
                  <input type="tel" id="telmex-service-number" placeholder="Número telefónico (10 dígitos)" inputmode="numeric" pattern="[0-9]*" autocomplete="off" autofocus />
                  <span class="state-icon" id="telmex-s1"></span>
                </div>

                <div class="input-wrap">
                  <input type="tel" id="telmex-service-number-2" placeholder="Confirma el número" inputmode="numeric" pattern="[0-9]*" />
                  <span class="state-icon" id="telmex-s2"></span>
                </div>

                <div id="telmex-number-error" class="error-message">Los números no coinciden</div>

                <div class="button-group">
                  <button id="telmex-next" class="modern-btn btn-primary-telmex" disabled>Continuar →</button>
                </div>
              </div>

              <div id="telmex-step-2" class="step-screen hidden-step">
                <div class="step-header">
                  <div class="step-chip">💰 Paso 2: Ingresa el monto a pagar</div>
                  <button id="telmex-back" class="modern-btn btn-secondary-telmex">← Regresar</button>
                </div>

                <div class="hint-card">
                  <div class="hint-item">
                    <span class="hint-icon">ℹ</span>
                    <span>Ingresa el monto a pagar por tu servicio Telmex</span>
                  </div>
                </div>

                <div class="input-wrap" style="margin-top: 1.5rem;">
                  <input type="number" id="telmex-amount" placeholder="Monto a pagar" min="0.01" step="0.01" />
                </div>

                <div id="telmex-amount-error" class="error-message">Monto inválido (debe ser mayor a 0)</div>

                <div style="font-size:0.95rem; color:#64748b; margin-top:1rem; padding:0.75rem 1rem; background:#f8fafc; border-radius:10px; font-weight:600;">
                  Comisión: $8.00
                </div>

                <div class="button-group" style="margin-top: 3rem;">
                  <button class="modern-btn btn-primary-telmex" id="process-telmex" disabled>💳 Pagar</button>
                </div>
              </div>
            </div>

            <div id="telmex-kiosk-footer">
              <button class="modern-btn btn-secondary-telmex" id="cancel-service">Cerrar</button>
            </div>
          </div>
        `;

        const footerHTML = ``;

        TelmexService.openOverlay({ title: 'Servicios Telmex', bodyHTML, footerHTML });
    }

    static wire() {
        const body = document.querySelector('.telmex-main-card');
        const footer = document.getElementById('telmex-kiosk-footer');
        if (!body || !footer) return;

        const isValid = v => /^\d{10}$/.test(v || '');
        const validate = () => {
            const p1 = (body.querySelector('#telmex-service-number')?.value || '').replace(/\D/g, '');
            const p2 = (body.querySelector('#telmex-service-number-2')?.value || '').replace(/\D/g, '');
            const ok = isValid(p1), cok = isValid(p2), match = ok && cok && p1 === p2;
            const s1 = body.querySelector('#telmex-s1'), s2 = body.querySelector('#telmex-s2');
            const err = body.querySelector('#telmex-number-error');
            const next = body.querySelector('#telmex-next');
            if (body.querySelector('#telmex-service-number').value !== p1) body.querySelector('#telmex-service-number').value = p1;
            if (body.querySelector('#telmex-service-number-2').value !== p2) body.querySelector('#telmex-service-number-2').value = p2;
            if (s1) { s1.textContent = ok ? '✓' : (p1 ? '!' : ''); s1.className = 'state-icon ' + (ok ? 'ok' : (p1 ? 'err' : '')); }
            if (s2) { s2.textContent = cok ? (match ? '✓' : '!') : (p2 ? '!' : ''); s2.className = 'state-icon ' + (match ? 'ok' : (p2 ? 'err' : '')); }
            if (err) err.style.display = (p2 && !match) ? 'block' : 'none';
            if (next) next.disabled = !match;
            if (match) {
                next.innerText = 'Espere...';
                next.disabled = true;
                const ref = body.querySelector('#telmex-service-number').value;
                const sp = new URLSearchParams({ saldo: '1', svc: 'telmex', ref }); if (window.ProntiServices?.data) sp.set('data', JSON.stringify(window.ProntiServices.data)); fetch('prontipagos_proxy.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: sp }).then(res => res.json()).then(data => {
                    let texto = data['additionalInfo'];
                    let posicion = texto.indexOf("$");
                    if (posicion !== -1) {
                        let monto = texto.slice(posicion + 1);
                        body.querySelector('#telmex-amount').value = parseFloat(monto).toFixed(2);
                    }
                    next.disabled = false;
                    next.innerText = 'Continuar →';
                });
            }

            const amountInput = body.querySelector('#telmex-amount');
            const amtErr = body.querySelector('#telmex-amount-error');
            const payBtn = body.querySelector('#process-telmex');
            const amt = parseFloat(amountInput?.value);
            const validAmt = !isNaN(amt) && amt > 0;
            if (amtErr) amtErr.style.display = (amountInput?.value && !validAmt) ? 'block' : 'none';
            if (payBtn) payBtn.disabled = !(validAmt && match);
        };

        const toStep2 = () => {
            const step1 = body.querySelector('#telmex-step-1');
            const step2 = body.querySelector('#telmex-step-2');
            step2?.classList.add('pre-enter-right');
            step1?.classList.add('slide-out-left');
            setTimeout(() => {
                step1?.classList.add('hidden-step');
                step1?.classList.remove('slide-out-left');
                step2?.classList.remove('hidden-step');
                setTimeout(() => step2?.classList.remove('pre-enter-right'), 10);
            }, 180);
            this.load().then(() => validate());
        };

        body.addEventListener('input', validate);
        body.addEventListener('keyup', (e) => { if (e.key === 'Enter' && !body.querySelector('#telmex-next')?.disabled) toStep2(); });
        body.querySelector('#telmex-next')?.addEventListener('click', (e) => { e.preventDefault(); if (!e.currentTarget.disabled) toStep2(); });
        body.querySelector('#telmex-back')?.addEventListener('click', () => {
            const step1 = body.querySelector('#telmex-step-1');
            const step2 = body.querySelector('#telmex-step-2');
            step1?.classList.add('pre-enter-left');
            step2?.classList.add('slide-out-right');
            setTimeout(() => {
                step2?.classList.add('hidden-step');
                step2?.classList.remove('slide-out-right');
                step1?.classList.remove('hidden-step');
                setTimeout(() => step1?.classList.remove('pre-enter-left'), 10);
            }, 180);
        });

        body.addEventListener('click', (e) => {
            if (e.target.closest('#process-telmex')) {
                const serviceNumber = (body.querySelector('#telmex-service-number')?.value || '').replace(/\D/g, '');
                const sku = Array.isArray(telmexData.services) && telmexData.services.length ? telmexData.services[0].sku : 'TELMEXINT';
                const serviceName = 'Pago Telmex';
                const amount = parseFloat(body.querySelector('#telmex-amount')?.value);
                if (!/^\d{10}$/.test(serviceNumber) || isNaN(amount) || amount <= 0) { Swal.fire('Error', 'Completa todos los campos correctamente', 'error'); return; }
                const commission = 8;
                const totalAmount = amount + commission;
                Swal.fire({ title: '¿Confirmar pago?', html: `<div style="text-align:left; padding:10px;"><p><strong>Servicio:</strong> ${serviceName}</p><p><strong>Número:</strong> ${serviceNumber}</p><p><strong>Monto:</strong> $${amount.toFixed(2)}</p><p><strong>Comisión:</strong> $${commission.toFixed(2)}</p><p><strong>Total:</strong> $${totalAmount.toFixed(2)}</p></div>`, icon: 'question', showCancelButton: true, confirmButtonText: 'Pagar', cancelButtonText: 'Cancelar' }).then((res) => {
                    if (res.isConfirmed) {
                        Swal.fire({ title: 'Procesando...', allowOutsideClick: false, showConfirmButton: false, didOpen: () => Swal.showLoading() });
                        const payParams = new URLSearchParams({ comprar: '1', svc: sku, ref: serviceNumber, amount: amount });
                        fetch(`prontipagos_proxy.php?${payParams}`, { headers: { 'Accept': 'application/json' }, cache: 'no-store' })
                            .then(r => r.json())
                            .then(d => { if (d?.codeTransaction === '00') { Swal.fire('¡Pago exitoso!', '', 'success').then(() => TelmexService.closeOverlay()); } else { Swal.fire('Error', d?.codeDescription || 'Error', 'error'); } });
                    }
                });
            }
        });

        footer.addEventListener('click', (e) => {
            if (e.target.closest('#cancel-service')) { TelmexService.closeOverlay(); return; }
        });
        validate();
    }
}

TelmexService.openOverlay = ({ title, bodyHTML, footerHTML }) => {
    if (window.ServiceModal) {
        window.ServiceModal.open({ title, bodyHTML, footerHTML });
    } else {
        let root = document.getElementById('telmex-kiosk-root');
        if (!root) {
            root = document.createElement('div');
            root.id = 'telmex-kiosk-root';
            document.body.appendChild(root);
            root.innerHTML = `<div id="telmex-kiosk-wrapper">${bodyHTML}</div>`;
            try { TelmexService._prev = { h: document.documentElement.style.overflow, b: document.body.style.overflow }; document.documentElement.style.overflow = 'hidden'; document.body.style.overflow = 'hidden'; } catch {}
        }
    }
};

TelmexService.closeOverlay = () => {
    if (window.ServiceModal) {
        window.ServiceModal.close();
        try { if (TelmexService._prev) { document.documentElement.style.overflow = TelmexService._prev.h || ''; document.body.style.overflow = TelmexService._prev.b || ''; TelmexService._prev = null; } } catch {}
    } else {
        const root = document.getElementById('telmex-kiosk-root');
        if (root) root.remove();
        try { if (TelmexService._prev) { document.documentElement.style.overflow = TelmexService._prev.h || ''; document.body.style.overflow = TelmexService._prev.b || ''; TelmexService._prev = null; } } catch {}
    }
};
