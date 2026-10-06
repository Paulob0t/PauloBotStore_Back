const providers = [
  {
    id: 'telcel',
    nombre: 'Telcel',
    color: '#1E88E5',
  imagen: 'images/services/telcel.png',
  tipo: 'recargas',
  init: async () => (await import('./telcel-services.js')).TelcelServices.init(),
    ensureRendered: () => {
    }
  },
  {
    id: 'movistar',
    nombre: 'Movistar',
    color: '#019DF4',
  imagen: 'images/services/movistar.png',
  tipo: 'recargas',
  init: async () => (await import('./movistar-services.js')).MovistarServices.init(),
    ensureRendered: () => {}
  },
  {
    id: 'bait',
    nombre: 'Bait',
    color: '#ED1C24',
  imagen: 'images/services/bait.png',
  tipo: 'recargas',
  init: async () => (await import('./bait-services.js')).BaitServices.init(),
    ensureRendered: () => {}
  },
  {
    id: 'att',
    nombre: 'AT&T',
    color: '#009FDB',
  imagen: 'images/services/att.png',
  tipo: 'recargas',
  init: async () => (await import('./att-services.js')).AttServices.init(),
    ensureRendered: () => {}
  },
  {
    id: 'iusacell',
    nombre: 'Iusacell',
    color: '#8E2D89',
  imagen: 'images/services/iusacell.png',
  tipo: 'recargas',
  init: async () => (await import('./iusacell-services.js')).IusacellServices.init(),
    ensureRendered: () => {}
  },
  {
    id: 'unefon',
    nombre: 'Unefon',
    color: '#F68B1F',
  imagen: 'images/services/unefon.png',
  tipo: 'recargas',
  init: async () => (await import('./unefon-services.js')).UnefonServices.init(),
    ensureRendered: () => {}
  },
  {
    id: 'spotify',
    nombre: 'Spotify',
    color: '#1DB954',
  imagen: 'images/services/spotify.png',
  tipo: 'streaming',
  init: async () => (await import('./spotify-services.js')).SpotifyServices.init(),
    ensureRendered: () => {}
  },
  {
    id: 'netflix',
    nombre: 'Netflix',
    color: '#E50914',
  imagen: 'images/services/netflix.png',
  tipo: 'streaming',
  init: async () => (await import('./netflix-services.js')).NetflixServices.init(),
    ensureRendered: () => {}
  },
  {
    id: 'shein',
    nombre: 'Shein',
    color: '#E6007E',
  imagen: 'images/services/shein.png',
  tipo: 'streaming',
  init: async () => (await import('./shein-services.js')).SheinServices.init(),
    ensureRendered: () => {}
  },
  {
    id: 'cfe',
    nombre: 'CFE',
    color: '#7EBC12',
  imagen: 'images/services/cfe.png',
  tipo: 'servicios',
  init: async () => (await import('./cfe-services.js')).CFEServices.init(),
    ensureRendered: () => {}
  },
  {
    id: 'megacable',
    nombre: 'Megacable',
    color: '#0B5ED7',
  imagen: 'images/services/megacable.png',
  tipo: 'servicios',
  init: async () => (await import('./megacable-services.js')).MegacableServices.init(),
    ensureRendered: () => {}
  },
  {
    id: 'total play',
    nombre: 'Totalplay',
    color: '#003D6B',
  imagen: 'images/services/totalplay.png',
  tipo: 'servicios',
  init: async () => (await import('./totalplay-services.js')).TotalplayServices.init(),
    ensureRendered: () => {}
  },
  {
    id: 'izzi',
    nombre: 'Izzi',
    color: '#00A650',
  imagen: 'images/services/izzi.png',
  tipo: 'servicios',
  init: async () => (await import('./izzi-services.js')).IzziServices.init(),
    ensureRendered: () => {}
  },
  {
    id: 'telmex',
    nombre: 'Telmex',
    color: '#0099CC',
    imagen: 'images/services/telmex.png',
    tipo: 'servicios',
    init: async () => (await import('./telmex.js')).TelmexService.init(),
    ensureRendered: () => {}
  }
];

function clearProducts() {
  const products = document.getElementById('products-container');
  if (products) products.innerHTML = '';
}

function hideSubcategoriesOnly() {
  const subs = document.getElementById('subcategories-container');
  const subsSection = document.getElementById('subcategories-section');
  if (subs) subs.style.display = 'none';
  if (subsSection) subsSection.style.display = 'none';
}

function ensureCategoriesVisible() {
  const cats = document.getElementById('categories-container');
  const section = document.querySelector('.categories-visual-section');
  if (section) section.style.display = 'block';
  if (cats) {
    cats.style.display = 'block';
    cats.classList.remove('hidden');
  }
  if (typeof window.initCarousels === 'function' && !cats?.querySelector('.infinite-carousel-mode')) {
    window.initCarousels();
  }
}

function showServicesMenu() {
  const services = document.getElementById('services-container');
  const products = document.getElementById('products-container');
  const productsSection = document.getElementById('products-display-section');
  if (!services) return;
  services.style.display = 'block';
  if (products) products.style.display = 'none';
  if (productsSection) productsSection.style.display = 'none';
  hideSubcategoriesOnly();
  ensureCategoriesVisible();
}

function ensureButtons() {
  const container = document.getElementById('services-container');
  if (!container) return;
  if (container.dataset.ready === '1') return;

  CarouselManager.init({
    containerId: 'services-container',
    infinite: true,
    infiniteSpeed: 0.55,
    dataSource: () => providers,
    renderCard: (service) => {
      const hasImage = service.imagen && service.imagen !== '';
      return `
        <div class="service-card service-menu-btn" data-provider="${service.id}">
          ${hasImage ? `
            <img class="service-img"
                 src="${service.imagen}"
                 alt="${service.nombre || 'Servicio'}"
                 onerror="this.style.display='none';this.nextElementSibling&&(this.nextElementSibling.style.display='flex')">
            <div class="no-image-placeholder" style="display:none">
              <i class="bi bi-gear-fill"></i>
            </div>
          ` : `
            <div class="no-image-placeholder">
              <i class="bi bi-gear-fill"></i>
            </div>
          `}
          <div class="service-overlay">
            <h3 class="service-title">${service.nombre || 'Servicio'}</h3>
            <button type="button" class="service-btn">
              <i class="bi bi-arrow-right"></i>
              Ver más
            </button>
          </div>
        </div>
      `;
    },
    autoSlide: true,
    slideInterval: 6000
  });


  container.dataset.ready = '1';

  container.addEventListener('click', async (ev) => {
    const btn = ev.target.closest('.service-menu-btn');
    if (!btn) return;
    const id = btn.getAttribute('data-provider');
    const prov = providers.find(pp => pp.id === id);
    if (!prov) return;
    clearProducts();
    showServicesMenu();
    ensureCategoriesVisible();
    if (window.categoryIndicator) {
      window.categoryIndicator.setType('servicios');
      window.categoryIndicator.setCategory(prov.nombre);
    }
    await prov.init();
  });

}

function fixSwitchBug() {
  document.addEventListener('viewChange', (e) => {
    const view = e?.detail?.view;
    const services = document.getElementById('services-container');
    const products = document.getElementById('products-container');
    const cats = document.getElementById('categories-container');
    const subs = document.getElementById('subcategories-container');
    if (view === 'servicios') {
      if (services) services.style.display = 'block';
      if (products) {
        products.style.display = 'none';
        products.innerHTML = '';
      }
      if (subs) subs.style.display = 'none';
      ensureCategoriesVisible();
    }
  });
}

function wireSwitchButtons() {
  const productosBtn = document.getElementById('productos-btn');
  const serviciosBtn = document.getElementById('servicios-btn');
  if (!productosBtn || !serviciosBtn) return;

  serviciosBtn.addEventListener('click', () => {
    showServicesMenu();
    ensureButtons();
    if (window.categoryIndicator) {
      window.categoryIndicator.setType('servicios');
      window.categoryIndicator.setCategory('Servicios');
    }
  });

  productosBtn.addEventListener('click', () => {
    const services = document.getElementById('services-container');
    if (services) services.style.display = 'none';
  });
}

document.addEventListener('DOMContentLoaded', () => {
  //console.log('🎬 services-menu.js inicializado');
  wireSwitchButtons();
  fixSwitchBug();
  
  // Escuchar el evento de servicios solicitados
  document.addEventListener('servicesRequested', () => {
    // console.log('🎯 Evento servicesRequested recibido');
    showServicesMenu();
    ensureButtons();
  });
  
  const servicesActive = document.querySelector('#servicios-btn.active');
  if (servicesActive) {
    //console.log('✅ Botón servicios activo al cargar');
    showServicesMenu();
    ensureButtons();
  }
});

// Exponer funciones globalmente
window.ensureServiceButtons = ensureButtons;
window.ensureCategoriesVisible = ensureCategoriesVisible;
