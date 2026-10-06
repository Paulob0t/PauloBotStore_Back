let netflixData = { servicios: null };

export class NetflixServices {
  static async init() {
    try { this.render(); this.wire(); } catch { Swal.fire("Error","No se pudieron cargar los servicios de Netflix","error"); }
  }

  static async load() {
    try {
      if (window.ProntiServices) {
        await window.ProntiServices.load();
        const raw = window.ProntiServices.getProducts('netflix');
        const servicios = (raw || []).filter(s => !/saldo/i.test(s.name || ''));
        if (servicios.length) {
          netflixData.servicios = servicios;
          return;
        }
      }
      const cached = window.ServiceCache?.get("netflix_services");
      if (cached) { netflixData.servicios = cached; return; }
      const r = await fetch("netflix_functional.php");
      const d = await r.json();
      const list = d?.services || [];
      netflixData.servicios = list;
      window.ServiceCache?.set("netflix_services", list);
    } catch { netflixData.servicios = []; }
  }

  static renderList(list) {
    if (list === null) return "<p style=\"text-align:center; padding:2rem; color:#6b7280; font-size:1.1rem;\"> Cargando servicios...</p>";
    if (!Array.isArray(list) || list.length === 0) return "<p style=\"text-align:center; padding:2rem; color:#6b7280; font-size:1.1rem;\"> No hay servicios disponibles</p>";
    return list
      .sort((a, b) => parseFloat(a.maxAmount || a.amount || 0) - parseFloat(b.maxAmount || b.amount || 0))
      .map(s => `
      <div class="service-item" data-sku="${s.sku}">
        <div class="service-name">${s.name || s.formatted_name}</div>
        ${s.description ? `<div style="font-size:0.9rem; color:#6b7280; margin-top:0.5rem;">${s.description.replace(/\$\d+(?:\.\d+)?\s*(?:MXN)?/gi, '').trim()}</div>` : ""}
        <div class="service-price">${s.maxAmount ? `$${parseFloat(s.maxAmount).toFixed(2)}` : s.price_display}</div>
        <div style="font-size:0.85rem; color:#9ca3af; margin-top:0.5rem;">SKU: ${s.sku}</div>
      </div>
    `).join("");
  }

  static updateList(list) {
    const target = document.querySelector(`.netflix-main-card .service-grid`);
    if (target) target.innerHTML = this.renderList(list);
  }

  static render() {
    if (!document.getElementById("netflix-styles")) {
        const style = document.createElement("style");
        style.id = "netflix-styles";
        style.textContent = `
        /* ===== NETFLIX PROFESSIONAL DESIGN ===== */
        #netflix-kiosk-root {
          position: fixed; inset: 0; z-index: 99999;
          background: linear-gradient(135deg, rgba(20, 5, 5, 0.95) 0%, rgba(35, 8, 8, 0.95) 100%);
          display: flex; align-items: center; justify-content: center; padding: 1rem;
          animation: fadeIn 0.4s ease-out; overflow: auto; backdrop-filter: blur(12px);
        }
        #netflix-kiosk-wrapper { width: 98%; max-width: none; max-height: 96vh; display: flex; align-items: center; justify-content: center; }
        .netflix-main-card {
          width: 100%; max-height: 96vh; background: white; border-radius: 24px;
          box-shadow: 0 25px 80px rgba(229, 9, 20, 0.4), 0 0 1px rgba(0, 0, 0, 0.2);
          overflow: hidden; animation: slideUp 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
          border: 1px solid rgba(229, 9, 20, 0.1); display: flex; flex-direction: column; transform-origin: center;
        }
        .netflix-header {
          background: linear-gradient(135deg, #E50914 0%, #B20710 100%); padding: 3rem 3rem;
          text-align: center; position: relative; overflow: hidden; flex-shrink: 0;
        }
        .netflix-header::before {
          content: ""; position: absolute; top: 0; left: 0; right: 0; bottom: 0;
          background: radial-gradient(circle at 20% 30%, rgba(255,255,255,0.15) 0%, transparent 50%),
                      radial-gradient(circle at 80% 70%, rgba(255,255,255,0.1) 0%, transparent 50%);
          animation: headerPulse 4s ease-in-out infinite;
        }
        .netflix-header::after {
          content: ""; position: absolute; top: 0; left: 0; right: 0; height: 5px;
          background: linear-gradient(90deg, #E50914 0%, #B20710 50%, #E50914 100%);
          box-shadow: 0 0 20px rgba(229, 9, 20, 0.5);
        }
        .netflix-header img {
          max-width: 200px; height: auto; filter: brightness(1.1) drop-shadow(0 4px 20px rgba(0,0,0,0.2));
          position: relative; z-index: 1; animation: logoFloat 3s ease-in-out infinite;
        }
        .netflix-header h2 {
          color: white; margin: 1.5rem 0 0 0; font-weight: 700; font-size: 2.5rem;
          text-shadow: 0 3px 12px rgba(0,0,0,0.2); position: relative; z-index: 1; letter-spacing: -0.5px;
        }
        @keyframes logoFloat { 0%, 100% { transform: translateY(0px); } 50% { transform: translateY(-8px); } }
        .netflix-content { padding: 3.5rem; background: #fafbfc; overflow-y: auto; flex: 1; }
        .step-chip {
          display: inline-flex; align-items: center; gap: 0.5rem; background: white; color: #E50914;
          font-weight: 600; font-size: 1.05rem; padding: 0.85rem 1.75rem; border-radius: 14px;
          border: 2px solid #fecaca; margin-bottom: 2.5rem; box-shadow: 0 3px 10px rgba(229, 9, 20, 0.1);
          transition: all 0.3s ease; animation: chipBounce 0.6s ease-out;
        }
        .step-chip:hover { border-color: #E50914; box-shadow: 0 5px 15px rgba(229, 9, 20, 0.2); transform: translateY(-2px); }
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
          outline: none; border-color: #E50914; background: white;
          box-shadow: 0 0 0 5px rgba(229, 9, 20, 0.1), 0 8px 16px rgba(229, 9, 20, 0.15); transform: translateY(-2px);
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
          background: white; border: 2px solid #fecaca; border-radius: 16px; padding: 1.75rem;
          margin-bottom: 2rem; box-shadow: 0 2px 8px rgba(229, 9, 20, 0.06);
        }
        .hint-list { list-style: none; padding: 0; margin: 0; }
        .hint-item {
          display: flex; align-items: flex-start; gap: 1rem; margin-bottom: 1rem;
          font-size: 0.95rem; color: #475569; line-height: 1.6;
        }
        .hint-item:last-child { margin-bottom: 0; }
        .hint-icon {
          display: inline-flex; align-items: center; justify-content: center; min-width: 28px; height: 28px;
          background: linear-gradient(135deg, #E50914 0%, #B20710 100%); color: white; border-radius: 50%;
          font-size: 0.8rem; font-weight: 700; box-shadow: 0 2px 8px rgba(229, 9, 20, 0.25);
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
        .btn-primary-netflix {
          background: linear-gradient(135deg, #E50914 0%, #B20710 100%); color: white;
          box-shadow: 0 4px 14px rgba(229, 9, 20, 0.35);
        }
        .btn-primary-netflix:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(229, 9, 20, 0.45); }
        .btn-primary-netflix:disabled {
          opacity: 0.5; cursor: not-allowed; transform: none; box-shadow: 0 2px 8px rgba(229, 9, 20, 0.2);
        }
        .btn-secondary-netflix {
          background: white; color: #475569; border: 2px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
        .btn-secondary-netflix:hover {
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
          background: linear-gradient(90deg, #E50914 0%, #B20710 100%); transform: scaleX(0); transition: transform 0.3s ease;
        }
        .service-item:hover {
          transform: translateY(-4px); border-color: #E50914; box-shadow: 0 12px 28px rgba(229, 9, 20, 0.15);
        }
        .service-item:hover::before { transform: scaleX(1); }
        .service-item.selected {
          border-color: #E50914; background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
          box-shadow: 0 12px 32px rgba(229, 9, 20, 0.25);
        }
        .service-item.selected::before { transform: scaleX(1); }
        .service-item .service-name { font-weight: 600; font-size: 1rem; color: #1e293b; line-height: 1.4; }
        .service-item .service-price {
          display: inline-block; background: linear-gradient(135deg, #E50914 0%, #B20710 100%);
          color: white; padding: 0.6rem 1.2rem; border-radius: 10px; font-weight: 700; font-size: 1.3rem;
          box-shadow: 0 4px 12px rgba(229, 9, 20, 0.25); align-self: flex-start;
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
        #netflix-kiosk-footer {
          padding: 1.5rem 2rem; background: white; border-top: 2px solid #e2e8f0; display: flex;
          justify-content: center; align-items: center; gap: 1rem; box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.05);
          flex-shrink: 0; border-radius: 0 0 24px 24px;
        }
        #netflix-kiosk-footer .modern-btn { min-width: 200px; }
        @media (max-width: 768px) {
          #netflix-kiosk-root { padding: 0; }
          .netflix-main-card { width: 100%; border-radius: 0; min-height: 100vh; max-width: 100%; max-height: 100vh; }
          .netflix-header { padding: 2.5rem 2rem; }
          .netflix-header img { max-width: 150px; }
          .netflix-header h2 { font-size: 2rem; }
          .netflix-content { padding: 2.5rem 2rem; }
          .service-grid { grid-template-columns: 1fr; gap: 1.25rem; }
          .button-group { flex-direction: column; }
          .modern-btn { width: 100%; min-width: unset; padding: 1.25rem 2rem; font-size: 1.1rem; }
          #netflix-kiosk-footer { padding: 1.25rem 1.5rem; }
          #netflix-kiosk-footer .modern-btn { width: 100%; padding: 1.25rem 2rem; }
        }
        @media (min-width: 769px) and (max-width: 1200px) {
          .netflix-main-card { width: 96%; max-width: none; }
          .service-grid { grid-template-columns: repeat(3, 1fr); }
          .netflix-content { padding: 3rem; }
        }
        @media (min-width: 1201px) {
          .netflix-main-card { width: 96%; max-width: none; }
          .service-grid { grid-template-columns: repeat(3, 1fr); }
          .netflix-content { padding: 3.5rem 4.5rem; }
        }
        @media (min-width: 1600px) {
          .netflix-main-card { width: 94%; max-width: none; }
          .service-grid { grid-template-columns: repeat(4, 1fr); }
          .netflix-content { padding: 4rem 5rem; }
        }
        `;
        document.head.appendChild(style);
    }

    const bodyHTML = `
      <div class="netflix-main-card">
        <div class="netflix-header">
          <img src="images/services/netflix.png" alt="Netflix" />
          <h2>Servicios Netflix</h2>
        </div>
        <div class="netflix-content">
          <div id="phone-step" class="step-screen">
            <div class="step-chip"> Paso 1: Ingresa tu número telefónico</div>
            <div class="section-title">Número de teléfono</div>
            <div class="hint-card">
              <ul class="hint-list">
                <li class="hint-item"><span class="hint-icon"></span><span>Escribe tu número celular de 10 dígitos</span></li>
                <li class="hint-item"><span class="hint-icon"></span><span>Confírmalo en el segundo campo</span></li>
                <li class="hint-item"><span class="hint-icon"></span><span>Haz clic en "Continuar"</span></li>
              </ul>
            </div>
            <div class="input-wrap">
              <input type="tel" id="nf-ref" placeholder="Número telefónico (10 dígitos)" inputmode="numeric" pattern="[0-9]{10}" maxlength="10" autofocus />
              <span class="state-icon" id="nf-s1"></span>
            </div>
            <div class="input-wrap">
              <input type="tel" id="nf-ref2" placeholder="Confirma el número" inputmode="numeric" pattern="[0-9]{10}" maxlength="10" />
              <span class="state-icon" id="nf-s2"></span>
            </div>
            <div id="nf-err" class="error-message">Los números no coinciden</div>
            <div class="button-group">
              <button id="nf-next" class="modern-btn btn-primary-netflix" disabled>Continuar </button>
            </div>
          </div>
          <div id="services-step" class="step-screen hidden-step">
            <div class="step-header">
              <div class="step-chip"> Paso 2: Elige tu servicio</div>
              <button id="nf-back" class="modern-btn btn-secondary-netflix"> Regresar</button>
            </div>
            <div class="hint-card">
              <div class="hint-item">
                <span class="hint-icon">ℹ</span>
                <span>Selecciona el servicio que necesitas de la lista disponible</span>
              </div>
            </div>
            <div class="service-grid"></div>
            <div class="button-group" style="margin-top: 3rem;">
              <button class="modern-btn btn-primary-netflix" id="nf-pay" style="display:none;" disabled> Pagar</button>
            </div>
          </div>
        </div>
        <div id="netflix-kiosk-footer">
          <button class="modern-btn btn-secondary-netflix" id="nf-cancel">Cerrar</button>
        </div>
      </div>
    `;

    NetflixServices.openOverlay({ title: "Servicios Netflix", bodyHTML });
  }

  static wire() {
    const body = document.querySelector(".netflix-main-card");
    const footer = document.getElementById("netflix-kiosk-footer");
    if (!body || !footer) return;

    const isValid10 = v => /^[0-9]{10}$/.test(v || "");
    const validate = () => {
      const p1 = (body.querySelector("#nf-ref")?.value || "").replace(/\D/g, "").slice(0, 10);
      const p2 = (body.querySelector("#nf-ref2")?.value || "").replace(/\D/g, "").slice(0, 10);
      const ok = isValid10(p1), cok = isValid10(p2), match = ok && cok && p1 === p2;
      const s1 = body.querySelector("#nf-s1"), s2 = body.querySelector("#nf-s2");
      const err = body.querySelector("#nf-err");
      const next = body.querySelector("#nf-next");
      if (body.querySelector("#nf-ref").value !== p1) body.querySelector("#nf-ref").value = p1;
      if (body.querySelector("#nf-ref2").value !== p2) body.querySelector("#nf-ref2").value = p2;
      if (s1) { s1.textContent = ok ? "" : (p1 ? "!" : ""); s1.className = "state-icon " + (ok ? "ok" : (p1 ? "err" : "")); }
      if (s2) { s2.textContent = cok ? (match ? "" : "!") : (p2 ? "!" : ""); s2.className = "state-icon " + (match ? "ok" : (p2 ? "err" : "")); }
      if (err) err.style.display = (p2 && !match) ? "block" : "none";
      if (next) next.disabled = !match;
      const pay = body.querySelector("#nf-pay");
      const selected = body.querySelector(".service-item.selected");
      if (pay) { pay.style.display = selected ? "inline-block" : "none"; pay.disabled = !selected || !match; }
    };

    const toServices = () => {
      const phoneStep = body.querySelector("#phone-step");
      const servicesStep = body.querySelector("#services-step");
      servicesStep?.classList.add("pre-enter-right");
      phoneStep?.classList.add("slide-out-left");
      setTimeout(() => {
        phoneStep?.classList.add("hidden-step");
        phoneStep?.classList.remove("slide-out-left");
        servicesStep?.classList.remove("hidden-step");
        setTimeout(() => servicesStep?.classList.remove("pre-enter-right"), 10);
      }, 180);
      if (netflixData.servicios === null) this.load().then(() => this.updateList(netflixData.servicios));
      else this.updateList(netflixData.servicios);
    };

    body.addEventListener("input", validate);
    body.addEventListener("keyup", (e) => { if (e.key === "Enter" && !body.querySelector("#nf-next")?.disabled) toServices(); });
    body.querySelector("#nf-next")?.addEventListener("click", (e) => { e.preventDefault(); if (!e.currentTarget.disabled) toServices(); });
    body.querySelector("#nf-back")?.addEventListener("click", () => {
      const phoneStep = body.querySelector("#phone-step");
      const servicesStep = body.querySelector("#services-step");
      phoneStep?.classList.add("pre-enter-left");
      servicesStep?.classList.add("slide-out-right");
      setTimeout(() => {
        servicesStep?.classList.add("hidden-step");
        servicesStep?.classList.remove("slide-out-right");
        phoneStep?.classList.remove("hidden-step");
        setTimeout(() => phoneStep?.classList.remove("pre-enter-left"), 10);
      }, 180);
    });

    body.addEventListener("click", (e) => {
      const item = e.target.closest(".service-item");
      if (item) {
        body.querySelectorAll(".service-item.selected").forEach(i => i.classList.remove("selected"));
        item.classList.add("selected");
        validate();
      }
    });

    body.addEventListener("click", (e) => {
      if (e.target.closest("#nf-pay")) {
        const selected = body.querySelector(".service-item.selected");
        const phone = (body.querySelector("#nf-ref")?.value || "").slice(0, 10);
        if (!selected || !isValid10(phone)) { Swal.fire("Error", "Selecciona un servicio y número válido", "error"); return; }
        const name = selected.querySelector(".service-name")?.textContent?.trim() || "Servicio Netflix";
        const priceTxt = selected.querySelector(".service-price")?.textContent || "$0";
        const sku = selected.dataset.sku;
        const price = parseFloat(priceTxt.replace(/[^0-9.]/g, "")) || 0;
        const commission = 2;
        
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
            NetflixServices.closeOverlay();
          });
        } else {
          // Fallback legacy
          const total = price + commission;
          Swal.fire({
            title: "¿Confirmar pago?",
            html: `<div style="text-align:left; padding:10px;"><p><strong>Servicio:</strong> ${name}</p><p><strong>Número:</strong> ${phone}</p><p><strong>Monto:</strong> $${price.toFixed(2)}</p><p><strong>Comisión:</strong> $${commission.toFixed(2)}</p><p><strong>Total:</strong> $${total.toFixed(2)}</p></div>`,
            icon: "question",
            showCancelButton: true,
            confirmButtonText: "Pagar",
            cancelButtonText: "Cancelar"
          }).then((res) => {
            if (res.isConfirmed) {
              Swal.fire({ title: "Procesando...", allowOutsideClick: false, showConfirmButton: false, didOpen: () => Swal.showLoading() });
              const payParams = new URLSearchParams({ comprar: "1", svc: sku, ref: phone, amount: price });
              fetch(`prontipagos_proxy.php?${payParams}`, { headers: { "Accept": "application/json" }, cache: "no-store" })
                .then(r => r.json())
                .then(d => { if (d?.codeTransaction === "00") { Swal.fire("¡Pago exitoso!", "", "success").then(() => NetflixServices.closeOverlay()); } else { Swal.fire("Error", d?.codeDescription || "Error", "error"); } });
            }
          });
        }
      }
    });

    footer.addEventListener("click", (e) => {
      if (e.target.closest("#nf-cancel")) { NetflixServices.closeOverlay(); return; }
    });
    validate();
  }
}

NetflixServices.openOverlay = ({ title, bodyHTML }) => {
  if (window.ServiceModal) {
    window.ServiceModal.open({ title, bodyHTML, footerHTML: '' });
  } else {
    let root = document.getElementById("netflix-kiosk-root");
    if (!root) {
      root = document.createElement("div");
      root.id = "netflix-kiosk-root";
      document.body.appendChild(root);
      root.innerHTML = `<div id="netflix-kiosk-wrapper">${bodyHTML}</div>`;
      try { document.documentElement.style.overflow = 'hidden'; document.body.style.overflow = 'hidden'; } catch {}
    }
  }
};

NetflixServices.closeOverlay = () => {
  if (window.ServiceModal) {
    window.ServiceModal.close();
  } else {
    const root = document.getElementById("netflix-kiosk-root");
    if (root) root.remove();
    try { document.documentElement.style.overflow = ''; document.body.style.overflow = ''; } catch {}
  }
};


