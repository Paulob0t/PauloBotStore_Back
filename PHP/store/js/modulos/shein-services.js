let sheinData = { servicios: null };
let _loadingShein = null;

export class SheinServices {
  static async init() {
    try { this.render(); this.wire(); this.loadServices(); } catch { Swal.fire("Error","No se pudieron cargar los servicios de Shein","error"); }
  }

  static async loadServices() {
    if (_loadingShein) return _loadingShein;
    sheinData.servicios = [];
    _loadingShein = (async () => {
      try {
        if (window.ProntiServices) {
          await window.ProntiServices.load();
          const raw = window.ProntiServices.getProducts('shein');
          if (raw && raw.length) {
            sheinData.servicios = [raw[0]];
            return;
          }
        }
        const r = await fetch('prontipagos_proxy.php?servicio=shein');
        const d = await r.json();
        const list = Array.isArray(d) ? d : [];
        sheinData.servicios = list.length ? [list[0]] : [];
      } catch { sheinData.servicios = []; }
      finally { _loadingShein = null; }
    })();
    return _loadingShein;
  }

  static async load() {
    if (sheinData.servicios === null || sheinData.servicios.length === 0) await this.loadServices();
  }

  static render() {
    if (!document.getElementById("shein-styles")) {
        const style = document.createElement("style");
        style.id = "shein-styles";
        style.textContent = `
        #shein-kiosk-root {
          position: fixed; inset: 0; z-index: 99999;
          background: linear-gradient(135deg, rgba(30, 5, 20, 0.95) 0%, rgba(45, 8, 25, 0.95) 100%);
          display: flex; align-items: center; justify-content: center; padding: 1rem;
          animation: fadeIn 0.4s ease-out; overflow: auto; backdrop-filter: blur(12px);
        }
        #shein-kiosk-wrapper { width: 98%; max-width: none; max-height: 96vh; display: flex; align-items: center; justify-content: center; }
        .shein-main-card {
          width: 100%; max-height: 96vh; background: white; border-radius: 24px;
          box-shadow: 0 25px 80px rgba(230, 0, 126, 0.4), 0 0 1px rgba(0, 0, 0, 0.2);
          overflow: hidden; animation: slideUp 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
          border: 1px solid rgba(230, 0, 126, 0.1); display: flex; flex-direction: column; transform-origin: center;
        }
        .shein-header {
          background: linear-gradient(135deg, #E6007E 0%, #C0006B 100%); padding: 3rem 3rem;
          text-align: center; position: relative; overflow: hidden; flex-shrink: 0;
        }
        .shein-header::before {
          content: ""; position: absolute; top: 0; left: 0; right: 0; bottom: 0;
          background: radial-gradient(circle at 20% 30%, rgba(255,255,255,0.15) 0%, transparent 50%),
                      radial-gradient(circle at 80% 70%, rgba(255,255,255,0.1) 0%, transparent 50%);
          animation: headerPulse 4s ease-in-out infinite;
        }
        .shein-header::after {
          content: ""; position: absolute; top: 0; left: 0; right: 0; height: 5px;
          background: linear-gradient(90deg, #E6007E 0%, #C0006B 50%, #E6007E 100%);
          box-shadow: 0 0 20px rgba(230, 0, 126, 0.5);
        }
        .shein-header img {
          max-width: 200px; height: auto; filter: brightness(1.1) drop-shadow(0 4px 20px rgba(0,0,0,0.2));
          position: relative; z-index: 1; animation: logoFloat 3s ease-in-out infinite;
        }
        .shein-header h2 {
          color: white; margin: 1.5rem 0 0 0; font-weight: 700; font-size: 2.5rem;
          text-shadow: 0 3px 12px rgba(0,0,0,0.2); position: relative; z-index: 1; letter-spacing: -0.5px;
        }
        @keyframes logoFloat { 0%, 100% { transform: translateY(0px); } 50% { transform: translateY(-8px); } }
        .shein-content { padding: 3.5rem; background: #fafbfc; overflow-y: auto; flex: 1; }
        .step-chip {
          display: inline-flex; align-items: center; gap: 0.5rem; background: white; color: #E6007E;
          font-weight: 600; font-size: 1.05rem; padding: 0.85rem 1.75rem; border-radius: 14px;
          border: 2px solid #fce4ec; margin-bottom: 2.5rem; box-shadow: 0 3px 10px rgba(230, 0, 126, 0.1);
          transition: all 0.3s ease; animation: chipBounce 0.6s ease-out;
        }
        .step-chip:hover { border-color: #E6007E; box-shadow: 0 5px 15px rgba(230, 0, 126, 0.2); transform: translateY(-2px); }
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
          outline: none; border-color: #E6007E; background: white;
          box-shadow: 0 0 0 5px rgba(230, 0, 126, 0.1), 0 8px 16px rgba(230, 0, 126, 0.15); transform: translateY(-2px);
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
          background: white; border: 2px solid #fce4ec; border-radius: 16px; padding: 1.75rem;
          margin-bottom: 2rem; box-shadow: 0 2px 8px rgba(230, 0, 126, 0.06);
        }
        .hint-list { list-style: none; padding: 0; margin: 0; }
        .hint-item {
          display: flex; align-items: flex-start; gap: 1rem; margin-bottom: 1rem;
          font-size: 0.95rem; color: #475569; line-height: 1.6;
        }
        .hint-item:last-child { margin-bottom: 0; }
        .hint-icon {
          display: inline-flex; align-items: center; justify-content: center; min-width: 28px; height: 28px;
          background: linear-gradient(135deg, #E6007E 0%, #C0006B 100%); color: white; border-radius: 50%;
          font-size: 0.8rem; font-weight: 700; box-shadow: 0 2px 8px rgba(230, 0, 126, 0.25);
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
        .btn-primary-shein {
          background: linear-gradient(135deg, #E6007E 0%, #C0006B 100%); color: white;
          box-shadow: 0 4px 14px rgba(230, 0, 126, 0.35);
        }
        .btn-primary-shein:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(230, 0, 126, 0.45); }
        .btn-primary-shein:disabled {
          opacity: 0.5; cursor: not-allowed; transform: none; box-shadow: 0 2px 8px rgba(230, 0, 126, 0.2);
        }
        .btn-secondary-shein {
          background: white; color: #475569; border: 2px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
        .btn-secondary-shein:hover {
          background: #f8fafc; border-color: #cbd5e1; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
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
        #shein-kiosk-footer {
          padding: 1.5rem 2rem; background: white; border-top: 2px solid #e2e8f0; display: flex;
          justify-content: center; align-items: center; gap: 1rem; box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.05);
          flex-shrink: 0; border-radius: 0 0 24px 24px;
        }
        #shein-kiosk-footer .modern-btn { min-width: 200px; }
        @media (max-width: 768px) {
          #shein-kiosk-root { padding: 0; }
          .shein-main-card { width: 100%; border-radius: 0; min-height: 100vh; max-width: 100%; max-height: 100vh; }
          .shein-header { padding: 2.5rem 2rem; }
          .shein-header img { max-width: 150px; }
          .shein-header h2 { font-size: 2rem; }
          .shein-content { padding: 2.5rem 2rem; }
          .button-group { flex-direction: column; }
          .modern-btn { width: 100%; min-width: unset; padding: 1.25rem 2rem; font-size: 1.1rem; }
          #shein-kiosk-footer { padding: 1.25rem 1.5rem; }
          #shein-kiosk-footer .modern-btn { width: 100%; padding: 1.25rem 2rem; }
        }
        `;
        document.head.appendChild(style);
    }

    const bodyHTML = `
      <div class="shein-main-card">
        <div class="shein-header">
          <img src="images/services/shein.png" alt="Shein" />
          <h2>Servicios Shein</h2>
        </div>
        <div class="shein-content">
          <div id="sh-step-1" class="step-screen">
            <div class="step-chip">\uD83D\uDCCB Paso 1: Ingresa tu n\u00famero de referencia</div>
            <div class="section-title">N\u00famero de referencia</div>
            <div class="hint-card">
              <ul class="hint-list">
                <li class="hint-item"><span class="hint-icon">\u2460</span><span>Escribe tu n\u00famero de referencia de 18 d\u00edgitos</span></li>
                <li class="hint-item"><span class="hint-icon">\u2461</span><span>Conf\u00edrmalo en el segundo campo</span></li>
                <li class="hint-item"><span class="hint-icon">\u2462</span><span>Haz clic en "Continuar"</span></li>
              </ul>
            </div>
            <div class="input-wrap">
              <input type="tel" id="sh-ref" placeholder="Referencia (18 d\u00edgitos)" inputmode="numeric" pattern="[0-9]{18}" maxlength="18" autofocus />
              <span class="state-icon" id="sh-s1"></span>
            </div>
            <div class="input-wrap">
              <input type="tel" id="sh-ref2" placeholder="Confirma la referencia" inputmode="numeric" pattern="[0-9]{18}" maxlength="18" />
              <span class="state-icon" id="sh-s2"></span>
            </div>
            <div id="sh-err" class="error-message">Las referencias no coinciden</div>
            <div style="font-size:0.85rem; color:#dc2626; margin-top:1rem; padding:1rem; background:#fff3f3; border-radius:12px; border:1px solid #fecaca; line-height:1.7;">
              <strong>\u26A0\uFE0F Nota importante:</strong><br>
              Monto m\u00ednimo $100 pesos, m\u00e1ximo $1,000 pesos.<br>
              Cuenta CLABE para el dep\u00f3sito.<br>
              Horario para dep\u00f3sito de 6:00 a 23:00.<br>
              La referencia generada para el pago tiene una duraci\u00f3n de 4 horas, el cliente debe pagar en este periodo para evitar rechazos.
            </div>
            <div class="button-group">
              <button id="sh-next" class="modern-btn btn-primary-shein" disabled>Continuar \u2192</button>
            </div>
          </div>
          <div id="sh-step-2" class="step-screen hidden-step">
            <div class="step-header">
              <div class="step-chip">\uD83D\uDCB0 Paso 2: Ingresa el monto</div>
              <button id="sh-back" class="modern-btn btn-secondary-shein">\u2190 Regresar</button>
            </div>
            <div class="hint-card">
              <div class="hint-item">
                <span class="hint-icon">\u2139</span>
                <span>Ingresa el monto a depositar (m\u00ednimo $100, m\u00e1ximo $1,000)</span>
              </div>
            </div>
            <div class="input-wrap" style="margin-top: 1.5rem;">
              <input type="number" id="sh-amount" placeholder="Monto ($100 - $1,000)" min="100" max="1000" step="1" />
            </div>
            <div id="sh-amount-error" class="error-message">Monto inv\u00e1lido (debe ser entre $100 y $1,000)</div>
            <div style="font-size:0.95rem; color:#64748b; margin-top:1rem; padding:0.75rem 1rem; background:#f8fafc; border-radius:10px; font-weight:600;">
              Comisi\u00f3n: $2.00
            </div>
            <div class="button-group" style="margin-top: 3rem;">
              <button class="modern-btn btn-primary-shein" id="sh-pay" disabled>\uD83D\uDCB3 Pagar</button>
            </div>
          </div>
        </div>
        <div id="shein-kiosk-footer">
          <button class="modern-btn btn-secondary-shein" id="sh-cancel">Cerrar</button>
        </div>
      </div>
    `;

    SheinServices.openOverlay({ title: "Servicios Shein", bodyHTML });
  }

  static wire() {
    const body = document.querySelector(".shein-main-card");
    const footer = document.getElementById("shein-kiosk-footer");
    if (!body || !footer) return;

    const isValidRef = v => /^[0-9]{18}$/.test(v || "");
    const validate = () => {
      const p1 = (body.querySelector("#sh-ref")?.value || "").replace(/\D/g, "").slice(0, 18);
      const p2 = (body.querySelector("#sh-ref2")?.value || "").replace(/\D/g, "").slice(0, 18);
      const ok = isValidRef(p1), cok = isValidRef(p2), match = ok && cok && p1 === p2;
      const s1 = body.querySelector("#sh-s1"), s2 = body.querySelector("#sh-s2");
      const err = body.querySelector("#sh-err");
      const next = body.querySelector("#sh-next");
      if (body.querySelector("#sh-ref").value !== p1) body.querySelector("#sh-ref").value = p1;
      if (body.querySelector("#sh-ref2").value !== p2) body.querySelector("#sh-ref2").value = p2;
      if (s1) { s1.textContent = ok ? "" : (p1 ? "!" : ""); s1.className = "state-icon " + (ok ? "ok" : (p1 ? "err" : "")); }
      if (s2) { s2.textContent = cok ? (match ? "" : "!") : (p2 ? "!" : ""); s2.className = "state-icon " + (match ? "ok" : (p2 ? "err" : "")); }
      if (err) err.style.display = (p2 && !match) ? "block" : "none";
      if (next) next.disabled = !match;

      const amountInput = body.querySelector("#sh-amount");
      const amtErr = body.querySelector("#sh-amount-error");
      const payBtn = body.querySelector("#sh-pay");
      const amt = parseFloat(amountInput?.value);
      const validAmt = !isNaN(amt) && amt >= 100 && amt <= 1000;
      if (amtErr) amtErr.style.display = (amountInput?.value && !validAmt) ? "block" : "none";
      if (payBtn) payBtn.disabled = !(validAmt && match);
    };

    const toStep2 = () => {
      const step1 = body.querySelector("#sh-step-1");
      const step2 = body.querySelector("#sh-step-2");
      step2?.classList.add("pre-enter-right");
      step1?.classList.add("slide-out-left");
      setTimeout(() => {
        step1?.classList.add("hidden-step");
        step1?.classList.remove("slide-out-left");
        step2?.classList.remove("hidden-step");
        setTimeout(() => step2?.classList.remove("pre-enter-right"), 10);
      }, 180);
      this.load().then(() => validate());
    };

    body.addEventListener("input", validate);
    body.addEventListener("keyup", (e) => { if (e.key === "Enter" && !body.querySelector("#sh-next")?.disabled) toStep2(); });
    body.querySelector("#sh-next")?.addEventListener("click", (e) => { e.preventDefault(); if (!e.currentTarget.disabled) toStep2(); });
    body.querySelector("#sh-back")?.addEventListener("click", () => {
      const step1 = body.querySelector("#sh-step-1");
      const step2 = body.querySelector("#sh-step-2");
      step1?.classList.add("pre-enter-left");
      step2?.classList.add("slide-out-right");
      setTimeout(() => {
        step2?.classList.add("hidden-step");
        step2?.classList.remove("slide-out-right");
        step1?.classList.remove("hidden-step");
        setTimeout(() => step1?.classList.remove("pre-enter-left"), 10);
      }, 180);
    });

    body.addEventListener("click", (e) => {
      if (e.target.closest("#sh-pay")) {
        const svc = Array.isArray(sheinData.servicios) && sheinData.servicios.length ? sheinData.servicios[0] : null;
        if (!svc) { Swal.fire("Error", "No se pudo cargar el servicio", "error"); return; }
        const ref = (body.querySelector("#sh-ref")?.value || "").slice(0, 18);
        const sku = svc.sku;
        const serviceName = svc.formatted_name || svc.name || "Servicio Shein";
        const amount = parseFloat(body.querySelector("#sh-amount")?.value);
        const commission = 2;
        if (!isValidRef(ref) || isNaN(amount) || amount < 100 || amount > 1000) { Swal.fire("Error", "Completa todos los campos correctamente", "error"); return; }

        if (window.ServicePaymentHandler) {
          window.ServicePaymentHandler.showPaymentOptions({
            serviceName: serviceName,
            reference: ref,
            amount: amount,
            commission: commission,
            sku: sku,
            requiresRegionalization: false
          }, () => {
            SheinServices.closeOverlay();
          });
        } else {
          const total = amount + commission;
          Swal.fire({
            title: "\u00bfConfirmar pago?",
            html: `<div style="text-align:left; padding:10px;"><p><strong>Servicio:</strong> ${serviceName}</p><p><strong>Referencia:</strong> ${ref}</p><p><strong>Monto:</strong> $${amount.toFixed(2)}</p><p><strong>Comisi\u00f3n:</strong> $${commission.toFixed(2)}</p><p><strong>Total:</strong> $${total.toFixed(2)}</p></div>`,
            icon: "question",
            showCancelButton: true,
            confirmButtonText: "Pagar",
            cancelButtonText: "Cancelar"
          }).then((res) => {
            if (res.isConfirmed) {
              Swal.fire({ title: "Procesando...", allowOutsideClick: false, showConfirmButton: false, didOpen: () => Swal.showLoading() });
              const payParams = new URLSearchParams({ comprar: "1", svc: sku, ref, amount: amount });
              fetch(`prontipagos_proxy.php?${payParams}`, { headers: { "Accept": "application/json" }, cache: "no-store" })
                .then(r => r.json())
                .then(d => { if (d?.codeTransaction === "00") { Swal.fire("\u00a1Pago exitoso!", "", "success").then(() => SheinServices.closeOverlay()); } else { Swal.fire("Error", d?.codeDescription || "Error", "error"); } });
            }
          });
        }
      }
    });

    footer.addEventListener("click", (e) => {
      if (e.target.closest("#sh-cancel")) { SheinServices.closeOverlay(); return; }
    });
    validate();
  }
}

SheinServices.openOverlay = ({ title, bodyHTML }) => {
  if (window.ServiceModal) {
    window.ServiceModal.open({ title, bodyHTML, footerHTML: '' });
  } else {
    let root = document.getElementById("shein-kiosk-root");
    if (!root) {
      root = document.createElement("div");
      root.id = "shein-kiosk-root";
      document.body.appendChild(root);
      root.innerHTML = `<div id="shein-kiosk-wrapper">${bodyHTML}</div>`;
      try { document.documentElement.style.overflow = 'hidden'; document.body.style.overflow = 'hidden'; } catch {}
    }
  }
};

SheinServices.closeOverlay = () => {
  if (window.ServiceModal) {
    window.ServiceModal.close();
  } else {
    const root = document.getElementById("shein-kiosk-root");
    if (root) root.remove();
    try { document.documentElement.style.overflow = ''; document.body.style.overflow = ''; } catch {}
  }
};
