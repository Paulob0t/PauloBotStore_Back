// 🚀 Caché de 15 minutos (más tiempo = menos peticiones)
const CACHE_TTL_MS = 15 * 60 * 1000; 
const ServiceCache = (() => {
  const mem = new Map();
  const lsKey = (key) => `svc-cache:${key}`;
  function now() { return Date.now(); }

  function get(key) {
    const inMem = mem.get(key);
    if (inMem && (now() - inMem.ts) < CACHE_TTL_MS) return inMem.data;

    try {
      const raw = localStorage.getItem(lsKey(key));
      if (!raw) return null;
      const parsed = JSON.parse(raw);
      if (parsed && parsed.ts && (now() - parsed.ts) < CACHE_TTL_MS) {
        mem.set(key, { ts: parsed.ts, data: parsed.data });
        return parsed.data;
      }
    } catch { 
    }
    return null;
  }

  function set(key, data) {
    const rec = { ts: now(), data };
    mem.set(key, rec);
    try { localStorage.setItem(lsKey(key), JSON.stringify(rec)); } catch {  }
  }

  async function fetchJson(url) {
    const r = await fetch(url, { headers: { 'Accept': 'application/json' }, cache: 'no-store' });
    return r.ok ? r.json() : null;
  }

  const endpoints = {
    telcel_tae: 'prontipagos_proxy.php?servicio=telcel&tae=1',
    telcel_paquetes: 'prontipagos_proxy.php?servicio=telcel',
    telcel_internet: 'prontipagos_proxy.php?servicio=telcel',
    movistar: 'prontipagos_proxy.php?servicio=movistar',
    netflix: 'prontipagos_proxy.php?servicio=netflix',
    spotify: 'prontipagos_proxy.php?servicio=spotify',
    cfe: 'prontipagos_proxy.php?servicio=cfe',
    megacable: 'prontipagos_proxy.php?servicio=megacable',
    sky: 'prontipagos_proxy.php?servicio=sky',
    telmex: 'prontipagos_proxy.php?servicio=telmex'
  };

  async function prime() {
    const run = async () => {
      // 1) Intentar carga masiva via ProntiServices (guarda en localStorage['servicios'])
      if (window.ProntiServices) {
        try {
          const data = await window.ProntiServices.load();
          if (Array.isArray(data) && data.length) return; // éxito, no hacer más
        } catch {}
      }
      // 2) Fallback: carga individual por servicio
      const tasks = Object.entries(endpoints).map(async ([key, url]) => {
        if (get(key)) return; 
        try {
          const data = await fetchJson(url);
          const list = data?.services || (Array.isArray(data) ? data : null);
          if (list) set(key, list);
        } catch { 
         }
      });
      try { await Promise.allSettled(tasks); } catch {  }
    };

    if ('requestIdleCallback' in window) {
      requestIdleCallback(() => run());
    } else {
      setTimeout(run, 500);
    }
  }
  return { get, set, prime, endpoints };
})();

window.ServiceCache = ServiceCache;
export { ServiceCache };

if (document.readyState === 'complete' || document.readyState === 'interactive') {
  setTimeout(() => ServiceCache.prime(), 0);
} else {
  document.addEventListener('DOMContentLoaded', () => ServiceCache.prime());
}
