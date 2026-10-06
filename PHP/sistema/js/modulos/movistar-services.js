let movistarData = { 
  recargas: null,
  postpago: null,
  loadingServices: false,
  hapticEnabled: 'vibrate' in navigator,
  monederoSaldo: 0,
  saldoInterval: null
};

export class MovistarServices {
  static async init() {
    try {
      await this.obtenerSaldoMonedero();
      this.render();
      this.wire();
      this.iniciarActualizacionSaldo();
    } catch (e) {
      Swal.fire('Error', 'No se pudieron cargar los servicios de Movistar', 'error');
    }
  }

  static async obtenerSaldoMonedero() {
    try {
      const response = await fetch('monedero_api.php?action=get_saldo');
      const data = await response.json();
      if (data.success) {
        const nuevoSaldo = parseFloat(data.saldo) || 0;
        movistarData.monederoSaldo = nuevoSaldo;
        this.actualizarBadgeSaldo();
      }
    } catch (error) {
      movistarData.monederoSaldo = 0;
      this.actualizarBadgeSaldo();
    }
  }

  static actualizarBadgeSaldo() {
    const badgeElement = document.getElementById('movistar-saldo');
    if (badgeElement) {
      const saldoAnterior = parseFloat(badgeElement.textContent.replace(/[^0-9.]/g, '')) || 0;
      const saldoNuevo = movistarData.monederoSaldo;
      
      badgeElement.textContent = `$${saldoNuevo.toFixed(2)}`;
      
      // Animación si el saldo cambió
      if (saldoNuevo !== saldoAnterior) {
        badgeElement.style.transform = 'scale(1.2)';
        badgeElement.style.transition = 'transform 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55)';
        setTimeout(() => {
          badgeElement.style.transform = 'scale(1)';
        }, 300);
      }
    }
    
    // Actualizar el widget principal (MI SALDO) SIN animación de conteo
    if (typeof MonederoIntegration !== 'undefined') {
      const saldoElement = document.getElementById('monedero-saldo');
      const tabSaldoElement = document.getElementById('wallet-tab-amount');
      
      if (saldoElement) {
        saldoElement.textContent = '$' + movistarData.monederoSaldo.toFixed(2);
      }
      if (tabSaldoElement) {
        tabSaldoElement.textContent = '$' + movistarData.monederoSaldo.toFixed(2);
      }
      
      // Actualizar el saldo interno del MonederoIntegration
      MonederoIntegration.saldoActual = movistarData.monederoSaldo;
    }
  }

  static async descontarSaldo(monto) {
    try {
      const response = await fetch('monedero_api.php?action=reset_saldo', {
        method: 'POST'
      });
      const data = await response.json();
      if (data.success) {
        movistarData.monederoSaldo = 0;
        this.actualizarBadgeSaldo();
        if (typeof MonederoIntegration !== 'undefined') {
          MonederoIntegration.resetearSaldo();
        }
      }
    } catch (error) {
      // Error silencioso
    }
  }

  static iniciarActualizacionSaldo() {
    if (movistarData.saldoInterval) {
      clearInterval(movistarData.saldoInterval);
    }
    
    movistarData.saldoInterval = setInterval(async () => {
      await this.obtenerSaldoMonedero();
      this.marcarServiciosInsuficientes();
    }, 300);
  }

  static detenerActualizacionSaldo() {
    if (movistarData.saldoInterval) {
      clearInterval(movistarData.saldoInterval);
      movistarData.saldoInterval = null;
    }
  }

  // 🔔 Helper: Vibración háptica
  static hapticFeedback(type = 'light') {
    if (!movistarData.hapticEnabled) return;
    
    try {
      switch(type) {
        case 'light':
          navigator.vibrate(10);
          break;
        case 'medium':
          navigator.vibrate(20);
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
    const colors = ['#019DF4', '#00C9FF', '#5FD800', '#10b981', '#f59e0b'];
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

  // 💰 Helper: Mostrar mensaje de saldo insuficiente
  static mostrarSaldoInsuficiente(price) {
    const faltante = price - movistarData.monederoSaldo;
    Swal.fire({
      title: '💰 Saldo insuficiente',
      html: `
        <div style="text-align: center; padding: 1rem;">
          <div style="font-size: 3rem; margin-bottom: 1rem;">💸</div>
          <p style="font-size: 1.1rem; margin-bottom: 1rem;">
            Necesitas <strong style="color: #dc2626;">$${price.toFixed(2)}</strong>
          </p>
          <p style="font-size: 1rem; color: #6b7280; margin-bottom: 1rem;">
            Tu saldo actual: <strong style="color: #019DF4;">$${movistarData.monederoSaldo.toFixed(2)}</strong>
          </p>
          <p style="font-size: 1.2rem; font-weight: 700; color: #f59e0b;">
            Te faltan: $${faltante.toFixed(2)}
          </p>
          <div style="margin-top: 1.5rem; padding: 1rem; background: #e6faff; border-radius: 8px;">
            <p style="margin: 0; color: #0c4a6e;">
              💡 Deposita más dinero en el monedero para continuar
            </p>
          </div>
        </div>
      `,
      icon: 'warning',
      confirmButtonText: 'Entendido',
      confirmButtonColor: '#019DF4'
    });
  }

  static async loadRecargas() {
    movistarData.loadingServices = true;
    const container = document.querySelector('.movistar-main-card .service-grid');
    if (container) this.showSkeletonLoader(container);
    
    try {
      if (window.ProntiServices) {
        await window.ProntiServices.load();
        const data = window.ProntiServices.getProducts('movistar');
        if (data && data.length) {
          movistarData.recargas = data.filter(p => !/telefonia/i.test(p.name || ''));
          movistarData.loadingServices = false;
          return;
        }
      }
      const cached = window.ServiceCache?.get('movistar_recargas');
      if (cached) { 
        movistarData.recargas = cached;
        movistarData.loadingServices = false;
        return; 
      }
      const r = await fetch('movistar_recargas_functional.php');
      const d = await r.json();
      const list = d?.services || [];
      movistarData.recargas = list;
      window.ServiceCache?.set('movistar_recargas', list);
      movistarData.loadingServices = false;
    } catch { 
      movistarData.recargas = [];
      movistarData.loadingServices = false;
    }
  }

  static async loadPostpago() {
    try {
      if (window.ProntiServices) {
        await window.ProntiServices.load();
        const data = window.ProntiServices.getProducts('movistar');
        if (data && data.length) {
          movistarData.postpago = data.filter(p => /telefonia/i.test(p.name));
          return;
        }
      }
      const r = await fetch('prontipagos_proxy.php?servicio=movistar');
      const d = await r.json();
      const list = Array.isArray(d) ? d : (d?.services || []);
      movistarData.postpago = list.filter(p => /telefonia/i.test(p.name));
    } catch {
      movistarData.postpago = [];
    }
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

  static updateList(list) {
    const target = document.querySelector(`.movistar-main-card .service-grid`);
    if (target) {
      target.innerHTML = this.renderList(list);
      this.marcarServiciosInsuficientes();
    }
  }

  static marcarServiciosInsuficientes() {
    const items = document.querySelectorAll('.service-item');
    if (items.length === 0) return;
    
    items.forEach(item => {
      const priceElement = item.querySelector('.service-price');
      if (!priceElement) return;
      
      const price = parseFloat(priceElement.textContent.replace(/[^0-9.]/g, '')) || 0;
      
      if (price > movistarData.monederoSaldo) {
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

  static render() {
    if (!document.getElementById('movistar-styles')) {
        const style = document.createElement('style');
        style.id = 'movistar-styles';
        style.textContent = `
        /* ===== MOVISTAR PROFESSIONAL DESIGN ===== */
        #movistar-kiosk-root {
          position: fixed; inset: 0; z-index: 99999;
          background: linear-gradient(135deg, rgba(5, 20, 40, 0.95) 0%, rgba(10, 35, 60, 0.95) 100%);
          display: flex; align-items: center; justify-content: center; padding: 1rem;
          animation: fadeIn 0.4s ease-out; overflow: auto; backdrop-filter: blur(12px);
        }
        #movistar-kiosk-wrapper { width: 98%; max-width: none; max-height: 96vh; display: flex; align-items: center; justify-content: center; }
        .movistar-main-card {
          width: 100%; max-height: 96vh; background: white; border-radius: 24px;
          box-shadow: 0 25px 80px rgba(1, 157, 244, 0.4), 0 0 1px rgba(0, 0, 0, 0.2);
          overflow: hidden; animation: slideUp 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
          border: 1px solid rgba(1, 157, 244, 0.1); display: flex; flex-direction: column; transform-origin: center;
        }
        .movistar-header {
          background: linear-gradient(135deg, #019DF4 0%, #0173C2 100%); padding: 3rem 3rem;
          text-align: center; position: relative; overflow: hidden; flex-shrink: 0;
        }
        .movistar-header::before {
          content: ""; position: absolute; top: 0; left: 0; right: 0; bottom: 0;
          background: radial-gradient(circle at 20% 30%, rgba(255,255,255,0.15) 0%, transparent 50%),
                      radial-gradient(circle at 80% 70%, rgba(255,255,255,0.1) 0%, transparent 50%);
          animation: headerPulse 4s ease-in-out infinite;
        }
        .movistar-header::after {
          content: ""; position: absolute; top: 0; left: 0; right: 0; height: 5px;
          background: linear-gradient(90deg, #019DF4 0%, #0173C2 50%, #019DF4 100%);
          box-shadow: 0 0 20px rgba(1, 157, 244, 0.5);
        }
        .movistar-header img {
          max-width: 200px; height: auto; filter: brightness(1.1) drop-shadow(0 4px 20px rgba(0,0,0,0.2));
          position: relative; z-index: 1; animation: logoFloat 3s ease-in-out infinite;
        }
        .movistar-header h2 {
          color: white; margin: 1.5rem 0 0 0; font-weight: 700; font-size: 2.5rem;
          text-shadow: 0 3px 12px rgba(0,0,0,0.2); position: relative; z-index: 1; letter-spacing: -0.5px;
        }
        @keyframes logoFloat { 0%, 100% { transform: translateY(0px); } 50% { transform: translateY(-8px); } }
        .movistar-content { padding: 3.5rem; background: #fafbfc; overflow-y: auto; flex: 1; }
        .step-chip {
          display: inline-flex; align-items: center; gap: 0.5rem; background: white; color: #019DF4;
          font-weight: 600; font-size: 1.05rem; padding: 0.85rem 1.75rem; border-radius: 14px;
          border: 2px solid #b3e0ff; margin-bottom: 2.5rem; box-shadow: 0 3px 10px rgba(1, 157, 244, 0.1);
          transition: all 0.3s ease; animation: chipBounce 0.6s ease-out;
        }
        .step-chip:hover { border-color: #019DF4; box-shadow: 0 5px 15px rgba(1, 157, 244, 0.2); transform: translateY(-2px); }
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
          outline: none; border-color: #019DF4; background: white;
          box-shadow: 0 0 0 5px rgba(1, 157, 244, 0.1), 0 8px 16px rgba(1, 157, 244, 0.15); transform: translateY(-2px);
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
          background: white; border: 2px solid #b3e0ff; border-radius: 16px; padding: 1.75rem;
          margin-bottom: 2rem; box-shadow: 0 2px 8px rgba(1, 157, 244, 0.06);
        }
        .hint-list { list-style: none; padding: 0; margin: 0; }
        .hint-item {
          display: flex; align-items: flex-start; gap: 1rem; margin-bottom: 1rem;
          font-size: 0.95rem; color: #475569; line-height: 1.6;
        }
        .hint-item:last-child { margin-bottom: 0; }
        .hint-icon {
          display: inline-flex; align-items: center; justify-content: center; min-width: 28px; height: 28px;
          background: linear-gradient(135deg, #019DF4 0%, #0173C2 100%); color: white; border-radius: 50%;
          font-size: 0.8rem; font-weight: 700; box-shadow: 0 2px 8px rgba(1, 157, 244, 0.25);
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
        .btn-primary-movistar {
          background: linear-gradient(135deg, #019DF4 0%, #0173C2 100%); color: white;
          box-shadow: 0 4px 14px rgba(1, 157, 244, 0.35);
        }
        .btn-primary-movistar:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(1, 157, 244, 0.45); }
        .btn-primary-movistar:disabled {
          opacity: 0.5; cursor: not-allowed; transform: none; box-shadow: 0 2px 8px rgba(1, 157, 244, 0.2);
        }
        .btn-secondary-movistar {
          background: white; color: #475569; border: 2px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
        .btn-secondary-movistar:hover {
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
          background: linear-gradient(90deg, #019DF4 0%, #0173C2 100%); transform: scaleX(0); transition: transform 0.3s ease;
        }
        .service-item:hover { transform: translateY(-4px); border-color: #019DF4; box-shadow: 0 12px 28px rgba(1, 157, 244, 0.15); }
        .service-item:hover::before { transform: scaleX(1); }
        .service-item.selected {
          border-color: #019DF4; background: linear-gradient(135deg, #e6f4ff 0%, #b3e0ff 100%);
          box-shadow: 0 12px 32px rgba(1, 157, 244, 0.25);
        }
        .service-item.selected::before { transform: scaleX(1); }
        .service-item .service-name { font-weight: 600; font-size: 1rem; color: #1e293b; line-height: 1.4; }
        .service-item .service-price {
          display: inline-block; background: linear-gradient(135deg, #019DF4 0%, #0173C2 100%);
          color: white; padding: 0.6rem 1.2rem; border-radius: 10px; font-weight: 700; font-size: 1.3rem;
          box-shadow: 0 4px 12px rgba(1, 157, 244, 0.25); align-self: flex-start;
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
        .tab-btn:hover { border-color: #019DF4; color: #019DF4; }
        .tab-btn.active { background: #019DF4; color: white; border-color: #019DF4; box-shadow: 0 4px 12px rgba(1, 157, 244, 0.3); }
        .tab-content { margin-top: 1rem; }
        #movistar-kiosk-footer {
          padding: 1.5rem 2rem; background: white; border-top: 2px solid #e2e8f0; display: flex;
          justify-content: center; align-items: center; gap: 1rem; box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.05);
          flex-shrink: 0; border-radius: 0 0 24px 24px;
        }
        #movistar-kiosk-footer .modern-btn { min-width: 200px; }
        @media (max-width: 768px) {
          #movistar-kiosk-root { padding: 0; }
          .movistar-main-card { width: 100%; border-radius: 0; min-height: 100vh; max-width: 100%; max-height: 100vh; }
          .movistar-header { padding: 2.5rem 2rem; }
          .movistar-header img { max-width: 150px; }
          .movistar-header h2 { font-size: 2rem; }
          .movistar-content { padding: 2.5rem 2rem; }
          .service-grid { grid-template-columns: 1fr; gap: 1.25rem; }
          .button-group { flex-direction: column; }
          .modern-btn { width: 100%; min-width: unset; padding: 1.25rem 2rem; font-size: 1.1rem; }
          #movistar-kiosk-footer { padding: 1.25rem 1.5rem; }
          #movistar-kiosk-footer .modern-btn { width: 100%; padding: 1.25rem 2rem; }
        }
        @media (min-width: 769px) and (max-width: 1200px) {
          .movistar-main-card { width: 96%; max-width: none; }
          .service-grid { grid-template-columns: repeat(3, 1fr); }
          .movistar-content { padding: 3rem; }
        }
        @media (min-width: 1201px) {
          .movistar-main-card { width: 96%; max-width: none; }
          .service-grid { grid-template-columns: repeat(3, 1fr); }
          .movistar-content { padding: 3.5rem 4.5rem; }
        }
        @media (min-width: 1600px) {
          .movistar-main-card { width: 94%; max-width: none; }
          .service-grid { grid-template-columns: repeat(4, 1fr); }
          .movistar-content { padding: 4rem 5rem; }
        }
        `;
        document.head.appendChild(style);
    }

    const bodyHTML = `
      <div class="movistar-main-card">
        <div class="movistar-header">
          <img src="images/services/movistar.png" alt="Movistar" />
          <h2>Servicios Movistar</h2>
          <div class="monedero-badge">
            <span class="monedero-icon">💰</span>
            <span class="monedero-label">Saldo:</span>
            <span class="monedero-amount" id="movistar-saldo">$${movistarData.monederoSaldo.toFixed(2)}</span>
          </div>
        </div>
        
        <div class="movistar-content">
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
              <input type="tel" id="mv-phone" placeholder="Número Movistar (10 dígitos)" inputmode="numeric" pattern="[0-9]{10}" maxlength="10" autofocus />
              <span class="state-icon" id="mv-s1"></span>
            </div>
            
            <div class="input-wrap">
              <input type="tel" id="mv-phone2" placeholder="Confirma el número" inputmode="numeric" pattern="[0-9]{10}" maxlength="10" />
              <span class="state-icon" id="mv-s2"></span>
            </div>
            
            <div id="mv-err" class="error-message">Los números no coinciden</div>
            
            <div class="button-group">
              <button id="mv-next" class="modern-btn btn-primary-movistar" disabled>Continuar →</button>
            </div>
          </div>
          
          <div id="tabs-step" class="service-tabs step-screen hidden-step">
            <div class="step-header">
              <div class="step-chip">📋 Paso 2: Elige tu servicio</div>
              <button id="mv-back" class="modern-btn btn-secondary-movistar">← Regresar</button>
            </div>
            
            <div class="hint-card">
              <div class="hint-item">
                <span class="hint-icon">ℹ</span>
                <span>Selecciona el servicio que necesitas de la lista de recargas disponibles</span>
              </div>
            </div>
            
            <div class="tab-buttons">
              <button class="tab-btn active" data-tab="recargas">💰 Recargas Movistar</button>
              <button class="tab-btn" data-tab="postpago">📋 Postpago</button>
            </div>
            
            <div class="tab-content">
              <div class="tab-pane" data-tab="recargas" style="display:block;">
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
                    <input type="number" id="movistar-postpago-amount" placeholder="Monto a pagar" min="0.01" step="0.01" />
                  </div>
                  <div id="movistar-postpago-error" class="error-message">Monto inválido (debe ser mayor a 0)</div>
                  <div style="font-size:0.95rem; color:#64748b; margin-top:1rem; padding:0.75rem 1rem; background:#f8fafc; border-radius:10px; font-weight:600;">
                    Comisión: $2.00
                  </div>
                  <div class="button-group" style="margin-top:2rem;">
                    <button class="modern-btn btn-primary-movistar" id="movistar-postpago-pay" disabled>💳 Pagar</button>
                  </div>
                </div>
              </div>
            </div>
            
            <div class="button-group" style="margin-top: 3rem;">
              <button class="modern-btn btn-primary-movistar" id="mv-pay" style="display:none;" disabled>💳 Pagar</button>
            </div>
          </div>
        </div>
        
        <div id="movistar-kiosk-footer">
          <button class="modern-btn btn-secondary-movistar" id="mv-cancel">Cerrar</button>
        </div>
      </div>
    `;

    const footerHTML = ``;

    MovistarServices.openOverlay({ title: 'Servicios Movistar', bodyHTML, footerHTML });
  }

  static wire() {
    const body = document.querySelector('.movistar-main-card');
    const footer = document.getElementById('movistar-kiosk-footer');
    if (!body || !footer) return;

    const isValid10 = v => /^[0-9]{10}$/.test(v || '');
    const validate = () => {
      const p1 = (body.querySelector('#mv-phone')?.value || '').replace(/\D/g, '').slice(0, 10);
      const p2 = (body.querySelector('#mv-phone2')?.value || '').replace(/\D/g, '').slice(0, 10);
      const ok = isValid10(p1), cok = isValid10(p2), match = ok && cok && p1 === p2;
      const s1 = body.querySelector('#mv-s1'), s2 = body.querySelector('#mv-s2');
      const err = body.querySelector('#mv-err');
      const next = body.querySelector('#mv-next');
      if (body.querySelector('#mv-phone').value !== p1) body.querySelector('#mv-phone').value = p1;
      if (body.querySelector('#mv-phone2').value !== p2) body.querySelector('#mv-phone2').value = p2;
      if (s1) { s1.textContent = ok ? '✓' : (p1 ? '!' : ''); s1.className = 'state-icon ' + (ok ? 'ok' : (p1 ? 'err' : '')); }
      if (s2) { s2.textContent = cok ? (match ? '✓' : '!') : (p2 ? '!' : ''); s2.className = 'state-icon ' + (match ? 'ok' : (p2 ? 'err' : '')); }
      if (err) err.style.display = (p2 && !match) ? 'block' : 'none';
      if (next) next.disabled = !match;
      const pay = body.querySelector('#mv-pay');
      const selected = body.querySelector('.service-item.selected');
      if (pay) { pay.style.display = selected ? 'inline-block' : 'none'; pay.disabled = !selected || !match; }
      const postpagoAmount = body.querySelector('#movistar-postpago-amount');
      const postpagoPay = body.querySelector('#movistar-postpago-pay');
      const postpagoErr = body.querySelector('#movistar-postpago-error');
      const activeTab = body.querySelector('.tab-btn.active');
      const isPostpagoTab = activeTab?.dataset.tab === 'postpago';
      if (postpagoPay) {
        const amt = parseFloat(postpagoAmount?.value);
        const validAmt = !isNaN(amt) && amt > 0;
        const hasSku = Array.isArray(movistarData.postpago) && movistarData.postpago.length > 0;
        if (postpagoErr) postpagoErr.style.display = (postpagoAmount?.value && !validAmt) ? 'block' : 'none';
        postpagoPay.disabled = !(hasSku && validAmt && match);
      }
    };

    const toTabs = () => {
      const phoneStep = body.querySelector('#phone-step');
      const tabsStep = body.querySelector('#tabs-step');
      tabsStep?.classList.add('pre-enter-right');
      phoneStep?.classList.add('slide-out-left');
      setTimeout(() => {
        phoneStep?.classList.add('hidden-step');
        phoneStep?.classList.remove('slide-out-left');
        tabsStep?.classList.remove('hidden-step');
        setTimeout(() => tabsStep?.classList.remove('pre-enter-right'), 10);
      }, 180);
      if (movistarData.recargas === null) this.loadRecargas().then(() => this.updateList(movistarData.recargas));
      else this.updateList(movistarData.recargas);
    };

    body.addEventListener('input', validate);
    body.addEventListener('keyup', (e) => { if (e.key === 'Enter' && !body.querySelector('#mv-next')?.disabled) toTabs(); });
    body.querySelector('#mv-next')?.addEventListener('click', (e) => { e.preventDefault(); if (!e.currentTarget.disabled) toTabs(); });
    body.querySelector('#mv-back')?.addEventListener('click', () => {
      const phoneStep = body.querySelector('#phone-step');
      const tabsStep = body.querySelector('#tabs-step');
      phoneStep?.classList.add('pre-enter-left');
      tabsStep?.classList.add('slide-out-right');
      setTimeout(() => {
        tabsStep?.classList.add('hidden-step');
        tabsStep?.classList.remove('slide-out-right');
        phoneStep?.classList.remove('hidden-step');
        setTimeout(() => phoneStep?.classList.remove('pre-enter-left'), 10);
      }, 180);
    });

    body.addEventListener('click', (e) => {
      const item = e.target.closest('.service-item');
      if (item) {
        if (item.classList.contains('insufficient-funds')) {
          // Vibración de error
          MovistarServices.hapticFeedback('error');
          const price = parseFloat(item.querySelector('.service-price')?.textContent.replace(/[^0-9.]/g, '')) || 0;
          MovistarServices.mostrarSaldoInsuficiente(price);
          return;
        }
        // Vibración al seleccionar
        MovistarServices.hapticFeedback('light');
        body.querySelectorAll('.service-item.selected').forEach(i => i.classList.remove('selected'));
        item.classList.add('selected');
        validate();
      }
    });

    body.addEventListener('click', (e) => {
      if (e.target.closest('#mv-pay')) {
        const selected = body.querySelector('.service-item.selected');
        const phone = (body.querySelector('#mv-phone')?.value || '').slice(0, 10);
        if (!selected || !isValid10(phone)) { Swal.fire('Error', 'Selecciona un servicio y número válido', 'error'); return; }
        const name = selected.querySelector('.service-name')?.textContent?.trim() || 'Servicio Movistar';
        const priceTxt = selected.querySelector('.service-price')?.textContent || '$0';
        const sku = selected.dataset.sku;
        const price = parseFloat(priceTxt.replace(/[^0-9.]/g, '')) || 0;
        const commission = 2;
        
        // Validar saldo del monedero
        if (price > movistarData.monederoSaldo) {
          MovistarServices.hapticFeedback('error');
          MovistarServices.mostrarSaldoInsuficiente(price);
          return;
        }
        
        // 🚀 Usar payment handler con Point + Efectivo
        if (window.ServicePaymentHandler) {
          window.ServicePaymentHandler.showPaymentOptions({
            serviceName: name,
            reference: phone,
            amount: price,
            commission: commission,
            sku: sku,
            requiresRegionalization: false
          }, () => {
            MovistarServices.closeOverlay();
          });
        } else {
          // Fallback legacy
          const total = price + commission;
          Swal.fire({ title: '¿Confirmar pago?', html: `<div style="text-align:left; padding:10px;"><p><strong>Servicio:</strong> ${name}</p><p><strong>Número:</strong> ${phone}</p><p><strong>Monto:</strong> $${price.toFixed(2)}</p><p><strong>Comisión:</strong> $${commission.toFixed(2)}</p><p><strong>Total:</strong> $${total.toFixed(2)}</p></div>`, icon: 'question', showCancelButton: true, confirmButtonText: 'Pagar', cancelButtonText: 'Cancelar' }).then((res) => {
            if (res.isConfirmed) {
              // Vibración al confirmar
              MovistarServices.hapticFeedback('medium');
              Swal.fire({ 
                title: '⏳ Procesando tu recarga...', 
                html: `
                  <div style="padding: 1rem;">
                    <p style="color: #64748b; margin-bottom: 1.5rem;">Por favor espera un momento</p>
                    <div class="progress-bar">
                      <div class="progress-fill"></div>
                    </div>
                    <div style="margin-top: 1.5rem; text-align: center;">
                      <div class="movistar-spinner"></div>
                    </div>
                  </div>
                `,
                allowOutsideClick: false, 
                showConfirmButton: false
              });
              const payParams = new URLSearchParams({ comprar: '1', svc: sku, ref: phone, amount: price });
              fetch(`prontipagos_proxy.php?${payParams}`, { headers: { 'Accept': 'application/json' }, cache: 'no-store' })
                .then(r => r.json())
                .then(async d => { 
                  if (d?.codeTransaction === '00') {
                    // Descontar saldo del monedero
                    await MovistarServices.descontarSaldo(price);
                    // Vibración de éxito
                    MovistarServices.hapticFeedback('success');
                    // Confetti effect
                    MovistarServices.showConfetti();
                    Swal.fire({
                      title: '✅ ¡Pago exitoso!',
                      html: `<div style="animation: bounce 1s ease;">📱</div>`,
                      icon: 'success',
                      timer: 5000,
                      timerProgressBar: true
                    }).then(() => MovistarServices.closeOverlay()); 
                  } else { 
                    MovistarServices.hapticFeedback('error');
                    Swal.fire('Error', d?.codeDescription || 'Error', 'error'); 
                  } 
                });
            }
          });
        }
      }
    });

    // Tab switching
    body.addEventListener('click', (e) => {
      const tabBtn = e.target.closest('.tab-btn');
      if (!tabBtn) return;
      const id = tabBtn.dataset.tab;
      body.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
      tabBtn.classList.add('active');
      body.querySelectorAll('.tab-pane').forEach(p => p.style.display = 'none');
      const pane = body.querySelector(`.tab-pane[data-tab="${id}"]`);
      if (pane) pane.style.display = 'block';
      if (id === 'postpago') {
        if (movistarData.postpago === null) this.loadPostpago();
      } else {
        if (movistarData.recargas === null) this.loadRecargas().then(() => this.updateList(movistarData.recargas));
        else this.updateList(movistarData.recargas);
      }
    });

    // Postpago pay
    body.addEventListener('click', (e) => {
      if (e.target.closest('#movistar-postpago-pay')) {
        const phone = (body.querySelector('#mv-phone')?.value || '').slice(0, 10);
        if (!isValid10(phone)) { Swal.fire('Error', 'Ingresa un número válido', 'error'); return; }
        const product = Array.isArray(movistarData.postpago) && movistarData.postpago.length > 0 ? movistarData.postpago[0] : null;
        if (!product) { Swal.fire('Error', 'No hay productos de postpago disponibles', 'error'); return; }
        const sku = product.sku;
        const name = product.name || 'Pago Postpago Movistar';
        const amountInput = body.querySelector('#movistar-postpago-amount');
        const amount = parseFloat(amountInput?.value);
        if (isNaN(amount) || amount <= 0) { Swal.fire('Error', 'Monto inválido', 'error'); return; }
        const commission = 2;

        if (window.ServicePaymentHandler) {
          window.ServicePaymentHandler.showPaymentOptions({
            serviceName: name,
            reference: phone,
            amount: amount,
            commission: commission,
            sku: sku,
            requiresRegionalization: false
          }, () => {
            MovistarServices.closeOverlay();
          });
        } else {
          const total = amount + commission;
          Swal.fire({
            title: '¿Confirmar pago?',
            html: `<div style="text-align:left;padding:10px;"><p><strong>Servicio:</strong> ${name}</p><p><strong>Número:</strong> ${phone}</p><p><strong>Monto:</strong> $${amount.toFixed(2)}</p><p><strong>Comisión:</strong> $${commission.toFixed(2)}</p><p><strong>Total:</strong> $${total.toFixed(2)}</p></div>`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Pagar',
            cancelButtonText: 'Cancelar'
          }).then((res) => {
            if (res.isConfirmed) {
              MovistarServices.hapticFeedback('medium');
              Swal.fire({
                title: '⏳ Procesando tu pago...',
                html: `<div style="padding:1rem;"><p style="color:#64748b;margin-bottom:1.5rem;">Por favor espera un momento</p><div class="progress-bar"><div class="progress-fill"></div></div><div style="margin-top:1.5rem;text-align:center;"><div class="movistar-spinner"></div></div></div>`,
                allowOutsideClick: false,
                showConfirmButton: false
              });
              const payParams = new URLSearchParams({ comprar: '1', svc: sku, ref: phone, amount: amount });
              fetch(`prontipagos_proxy.php?${payParams}`, { headers: { 'Accept': 'application/json' }, cache: 'no-store' })
                .then(r => r.json())
                .then(async d => {
                  if (d?.codeTransaction === '00') {
                    await MovistarServices.descontarSaldo(amount);
                    MovistarServices.hapticFeedback('success');
                    MovistarServices.showConfetti();
                    Swal.fire({
                      title: '✅ ¡Pago exitoso!',
                      html: `<div style="animation:bounce 1s ease;">📱</div>`,
                      icon: 'success',
                      timer: 5000,
                      timerProgressBar: true
                    }).then(() => MovistarServices.closeOverlay());
                  } else {
                    MovistarServices.hapticFeedback('error');
                    Swal.fire('Error', d?.codeDescription || 'Error', 'error');
                  }
                });
            }
          });
        }
      }
    });

    footer.addEventListener('click', (e) => {
      if (e.target.closest('#mv-cancel')) { MovistarServices.closeOverlay(); return; }
    });
    validate();
  }
}

MovistarServices.openOverlay = ({ title, bodyHTML, footerHTML }) => {
  if (window.ServiceModal) {
    window.ServiceModal.open({ title, bodyHTML, footerHTML });
  } else {
    let root = document.getElementById('movistar-kiosk-root');
    if (!root) {
      root = document.createElement('div');
      root.id = 'movistar-kiosk-root';
      document.body.appendChild(root);
      root.innerHTML = `<div id="movistar-kiosk-wrapper">${bodyHTML}</div>`;
      try { MovistarServices._prev = { h: document.documentElement.style.overflow, b: document.body.style.overflow }; document.documentElement.style.overflow = 'hidden'; document.body.style.overflow = 'hidden'; } catch {}
    }
  }
};

MovistarServices.closeOverlay = () => {
  MovistarServices.detenerActualizacionSaldo();
  if (window.ServiceModal) {
    window.ServiceModal.close();
    try { if (MovistarServices._prev) { document.documentElement.style.overflow = MovistarServices._prev.h || ''; document.body.style.overflow = MovistarServices._prev.b || ''; MovistarServices._prev = null; } } catch {}
  } else {
    const root = document.getElementById('movistar-kiosk-root');
    if (root) root.remove();
    try { if (MovistarServices._prev) { document.documentElement.style.overflow = MovistarServices._prev.h || ''; document.body.style.overflow = MovistarServices._prev.b || ''; MovistarServices._prev = null; } } catch {}
  }
};

