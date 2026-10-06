// ⚡ OPTIMIZADOR DE SERVICIOS - Reduce lag al cambiar entre servicios
// Versión SIMPLE sin lazy loader

const providers = [
  {
    id: 'telcel',
    label: 'Telcel',
    color: '#1E88E5',
    icon: 'images/providers/telcel.jpg',
    init: async () => (await import('./telcel-services.js')).TelcelServices.init(),
    ensureRendered: () => {}
  },
  {
    id: 'movistar',
    label: 'Movistar',
    color: '#019DF4',
    icon: 'images/providers/Movistar-Logo-2004.jpg',
    init: async () => (await import('./movistar-services.js')).MovistarServices.init(),
    ensureRendered: () => {}
  },
  {
    id: 'spotify',
    label: 'Spotify',
    color: '#1DB954',
    icon: 'images/providers/51rttY7a+9L.png',
    init: async () => (await import('./spotify-services.js')).SpotifyServices.init(),
    ensureRendered: () => {}
  },
  {
    id: 'netflix',
    label: 'Netflix',
    color: '#E50914',
    icon: 'images/providers/Netflix-Symbol.png',
    init: async () => (await import('./netflix-services.js')).NetflixServices.init(),
    ensureRendered: () => {}
  },
  {
    id: 'cfe',
    label: 'CFE',
    color: '#7EBC12',
    icon: 'images/providers/16806759686086.jpg',
    init: async () => (await import('./cfe-services.js')).CFEServices.init(),
    ensureRendered: () => {}
  },
  {
    id: 'megacable',
    label: 'Megacable',
    color: '#0B5ED7',
    icon: 'images/providers/Megacable.jpg',
    init: async () => (await import('./megacable-services.js')).MegacableServices.init(),
    ensureRendered: () => {}
  }
];

// ⚡ Variables de optimización
let renderCache = null;
let currentProvider = null;
let isLoading = false;

// 🚀 Debounce helper
function debounce(fn, ms = 150) {
  let timer;
  return function(...args) {
    clearTimeout(timer);
    timer = setTimeout(() => fn.apply(this, args), ms);
  };
}

// 🎯 Optimizar manipulación DOM
function clearProducts() {
  const products = document.getElementById('products-container');
  if (products) {
    // Más rápido que innerHTML = ''
    while (products.firstChild) {
      products.removeChild(products.firstChild);
    }
  }
}

function hideCatalogGrids() {
  const cats = document.getElementById('categories-container');
  const subs = document.getElementById('subcategories-container');
  
  // Usar visibility en vez de display para evitar reflow
  if (cats) {
    cats.style.display = 'none';
    cats.style.visibility = 'hidden';
  }
  if (subs) {
    subs.style.display = 'none';
    subs.style.visibility = 'hidden';
  }
}

function showServicesMenu() {
  const services = document.getElementById('services-container');
  const products = document.getElementById('products-container');
  
  if (!services) return;
  
  // Batch DOM updates con requestAnimationFrame
  requestAnimationFrame(() => {
    services.style.display = 'grid';
    if (products) products.style.display = 'none';
    hideCatalogGrids();
  });
}

// ⚡ Renderizar botones (con caché)
function ensureButtons() {
  const container = document.getElementById('services-container');
  if (!container) return;
  
  // Ya renderizado - reusar
  if (container.dataset.ready === '1') {
    container.style.display = 'grid';
    return;
  }

  // Usar caché de HTML si existe
  if (renderCache) {
    container.innerHTML = renderCache;
  } else {
    container.style.display = 'grid';
    container.style.gridTemplateColumns = 'repeat(3, 110px)';
    container.style.justifyContent = 'center';
    container.style.justifyItems = 'center';
    container.style.gap = '22px 130px';
    container.style.padding = '8px 200px 8px 200px';

    // Crear HTML una sola vez
    const html = providers.map(p => `
      <button class="service-menu-btn" data-provider="${p.id}" style="
          display: flex; flex-direction: column; align-items: center; justify-content: center;
          width: 200px; height: 200px; border-radius: 999px; border: 3px solid ${p.color};
          background: #fff; color: #222; font-weight: 700; cursor: pointer;
          transition: transform .15s ease, box-shadow .15s ease; box-shadow: 0 4px 14px rgba(0,0,0,.08);
          will-change: transform, box-shadow;
      ">
        <img alt="${p.label}" src="${p.icon}" 
             style="width: 170px; height: 100px; object-fit: contain;" 
             loading="lazy" decoding="async" />
      </button>
    `).join('');
    
    renderCache = html;
    container.innerHTML = html;
  }
  
  container.dataset.ready = '1';
  attachEventListeners(container);
}

// ⚡ Event listeners optimizados
function attachEventListeners(container) {
  // Event delegation - Más eficiente
  container.addEventListener('click', handleServiceClick);
  
  // Hover effects con RAF
  container.addEventListener('mouseenter', handleMouseEnter, true);
  container.addEventListener('mouseleave', handleMouseLeave, true);
}

// 🖱️ Handler de clicks (con debounce)
const handleServiceClick = debounce(async (ev) => {
  const btn = ev.target.closest('.service-menu-btn');
  if (!btn || isLoading) return;
  
  const id = btn.getAttribute('data-provider');
  const prov = providers.find(pp => pp.id === id);
  if (!prov || currentProvider === id) return;
  
  isLoading = true;
  currentProvider = id;
  
  try {
    // Mostrar loading sin bloquear
    showLoadingState(btn);
    
    // Preparar UI
    clearProducts();
    showServicesMenu();
    
    const products = document.getElementById('products-container');
    if (products) {
      requestAnimationFrame(() => {
        products.style.display = 'grid';
        products.style.gridTemplateColumns = 'repeat(auto-fill, minmax(360px, 1fr))';
        products.style.gap = '16px';
        products.dataset.layout = 'services';
      });
    }
    
    if (window.categoryIndicator) {
      window.categoryIndicator.setType('servicios');
      window.categoryIndicator.setCategory(prov.label);
    }
    
    // ⚡ Cargar e inicializar el servicio
    await prov.init();
    
  } catch (err) {
    console.error('Error loading service:', err);
  } finally {
    hideLoadingState(btn);
    isLoading = false;
  }
}, 200);

// 🎨 Loading state visual
function showLoadingState(btn) {
  if (!btn) return;
  btn.style.opacity = '0.6';
  btn.style.pointerEvents = 'none';
}

function hideLoadingState(btn) {
  if (!btn) return;
  requestAnimationFrame(() => {
    btn.style.opacity = '1';
    btn.style.pointerEvents = 'auto';
  });
}

// 🖱️ Hover effects optimizados con RAF
function handleMouseEnter(ev) {
  const btn = ev.target.closest('.service-menu-btn');
  if (!btn) return;
  
  requestAnimationFrame(() => {
    btn.style.transform = 'translateY(-2px)';
    btn.style.boxShadow = '0 6px 16px rgba(0,0,0,.12)';
  });
}

function handleMouseLeave(ev) {
  const btn = ev.target.closest('.service-menu-btn');
  if (!btn) return;
  
  requestAnimationFrame(() => {
    btn.style.transform = 'translateY(0)';
    btn.style.boxShadow = '0 4px 14px rgba(0,0,0,.08)';
  });
}

// 🔄 Fix para switch entre vistas
function fixSwitchBug() {
  document.addEventListener('viewChange', (e) => {
    const view = e?.detail?.view;
    const services = document.getElementById('services-container');
    const products = document.getElementById('products-container');
    const cats = document.getElementById('categories-container');
    const subs = document.getElementById('subcategories-container');
    
    if (view === 'servicios') {
      requestAnimationFrame(() => {
        if (services) services.style.display = 'grid';
        if (products) {
          products.style.display = 'grid';
          products.innerHTML = '';
        }
        if (cats) cats.style.display = 'none';
        if (subs) subs.style.display = 'none';
      });
    }
  });
}

// 🎛️ Wire switch buttons
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
    if (services) {
      requestAnimationFrame(() => {
        services.style.display = 'none';
      });
    }
    currentProvider = null; // Reset
  });
}

// =====================================================
// 🎠 CARRUSEL DE SERVICIOS (mismo formato que featured-products)
// =====================================================
const ServicesCarousel = {
  currentSlide: 0,
  autoSlideInterval: null,
  itemsPerSlide: 4,
  totalSlides: 0,

  updateItemsPerSlide() {
    const w = window.innerWidth;
    if (w <= 480)       this.itemsPerSlide = 1;
    else if (w <= 768)  this.itemsPerSlide = 2;
    else if (w <= 1200) this.itemsPerSlide = 3;
    else                this.itemsPerSlide = Math.min(4, providers.length);
  },

  createCard(p) {
    return `
      <div class="featured-product-card service-carousel-card" data-provider="${p.id}"
           style="cursor:pointer; border-top: 4px solid ${p.color};">
        <div class="featured-product-image" style="background:#fff;">
          <img src="${p.icon}" alt="${p.label}" loading="lazy" decoding="async"
               style="max-width:80%; max-height:80%; object-fit:contain;">
        </div>
        <div class="featured-product-content">
          <h3 class="featured-product-title">${p.label}</h3>
          <button class="featured-add-to-cart service-select-btn" data-provider="${p.id}"
                  style="background:${p.color}; color:#fff;">
            <i class="fa fa-arrow-right"></i>
            Ver Servicios
          </button>
        </div>
      </div>
    `;
  },

  render() {
    const container = document.getElementById('services-container');
    if (!container) return;

    this.updateItemsPerSlide();

    const cardsHTML = providers.map(p => this.createCard(p)).join('');

    container.innerHTML = `
      <div class="featured-products-carousel-wrapper">
        <button class="featured-carousel-btn prev" id="services-prev">
          <i class="fa fa-chevron-left"></i>
        </button>
        <div class="featured-products-grid">
          <div class="featured-products-carousel" id="services-carousel">
            ${cardsHTML}
          </div>
        </div>
        <button class="featured-carousel-btn next" id="services-next">
          <i class="fa fa-chevron-right"></i>
        </button>
      </div>
      <div class="featured-carousel-dots" id="services-dots"></div>
    `;

    this.initCarousel();
    this.attachEvents(container);
  },

  initCarousel() {
    const carousel  = document.getElementById('services-carousel');
    const prevBtn   = document.getElementById('services-prev');
    const nextBtn   = document.getElementById('services-next');
    const dotsWrap  = document.getElementById('services-dots');
    if (!carousel) return;

    this.totalSlides = Math.ceil(providers.length / this.itemsPerSlide);

    // dots
    dotsWrap.innerHTML = '';
    for (let i = 0; i < this.totalSlides; i++) {
      const dot = document.createElement('button');
      dot.className = `featured-carousel-dot ${i === 0 ? 'active' : ''}`;
      dot.addEventListener('click', () => this.goToSlide(i));
      dotsWrap.appendChild(dot);
    }
    dotsWrap.style.display = this.totalSlides > 1 ? 'flex' : 'none';

    prevBtn.style.display = this.totalSlides > 1 ? 'flex' : 'none';
    nextBtn.style.display = this.totalSlides > 1 ? 'flex' : 'none';

    prevBtn.replaceWith(prevBtn.cloneNode(true));
    nextBtn.replaceWith(nextBtn.cloneNode(true));
    document.getElementById('services-prev').addEventListener('click', () => this.prevSlide());
    document.getElementById('services-next').addEventListener('click', () => this.nextSlide());

    carousel.addEventListener('mouseenter', () => this.stopAutoSlide());
    carousel.addEventListener('mouseleave', () => this.startAutoSlide());

    this.startAutoSlide();
    requestAnimationFrame(() => this.goToSlide(0));
  },

  goToSlide(index) {
    const carousel = document.getElementById('services-carousel');
    if (!carousel || this.totalSlides === 0) return;

    if (index >= this.totalSlides)   this.currentSlide = 0;
    else if (index < 0)              this.currentSlide = this.totalSlides - 1;
    else                             this.currentSlide = index;

    const cards = carousel.querySelectorAll('.featured-product-card');
    if (!cards.length) return;

    const cardWidth = cards[0].offsetWidth;
    const offset = -(this.currentSlide * this.itemsPerSlide * (cardWidth + 20));
    carousel.style.transition = 'transform 0.6s ease-in-out';
    carousel.style.transform = `translateX(${offset}px)`;

    document.querySelectorAll('#services-dots .featured-carousel-dot').forEach((d, i) => {
      d.classList.toggle('active', i === this.currentSlide);
    });
  },

  nextSlide() {
    if (this.totalSlides <= 1) return;
    this.goToSlide((this.currentSlide + 1) % this.totalSlides);
  },

  prevSlide() {
    if (this.totalSlides <= 1) return;
    this.goToSlide((this.currentSlide - 1 + this.totalSlides) % this.totalSlides);
  },

  startAutoSlide() {
    this.stopAutoSlide();
    if (this.totalSlides <= 1) return;
    this.autoSlideInterval = setInterval(() => this.nextSlide(), 5000);
  },

  stopAutoSlide() {
    if (this.autoSlideInterval) {
      clearInterval(this.autoSlideInterval);
      this.autoSlideInterval = null;
    }
  },

  attachEvents(container) {
    container.addEventListener('click', async (ev) => {
      const btn = ev.target.closest('.service-select-btn, .service-carousel-card');
      if (!btn || isLoading) return;

      const id = btn.dataset.provider;
      const prov = providers.find(pp => pp.id === id);
      if (!prov) return;

      // Activar el botón de servicios y desplazarse a la sección
      const serviciosBtn = document.getElementById('servicios-btn');
      const productosBtn = document.getElementById('productos-btn');
      if (serviciosBtn && !serviciosBtn.classList.contains('active')) {
        serviciosBtn.click();
      }

      // Scroll suave a la sección de servicios
      const mainArea = document.querySelector('.main-categories-area');
      if (mainArea) mainArea.scrollIntoView({ behavior: 'smooth', block: 'start' });

      // Simular click en el botón del proveedor dentro del menú interno
      // (ensureButtons renderiza los botones si no están listos)
      ensureButtons();
      await new Promise(r => setTimeout(r, 80));

      const provBtn = document.querySelector(`.service-menu-btn[data-provider="${id}"]`);
      if (provBtn) {
        provBtn.dispatchEvent(new MouseEvent('click', { bubbles: true }));
      } else {
        // Fallback: cargar directamente
        isLoading = true;
        currentProvider = id;
        try {
          clearProducts();
          showServicesMenu();
          const products = document.getElementById('products-container');
          if (products) {
            products.style.display = 'grid';
            products.style.gridTemplateColumns = 'repeat(auto-fill, minmax(360px, 1fr))';
            products.style.gap = '16px';
            products.dataset.layout = 'services';
          }
          await prov.init();
        } catch (err) {
          console.error('Error loading service:', err);
        } finally {
          isLoading = false;
        }
      }
    });
  }
};

// =====================================================

// 🚀 Inicialización
document.addEventListener('DOMContentLoaded', () => {
  wireSwitchButtons();
  fixSwitchBug();

  // Renderizar carrusel de servicios siempre visible
  ServicesCarousel.render();
  
  // Precargar imágenes de providers en idle time
  if ('requestIdleCallback' in window) {
    requestIdleCallback(() => {
      providers.forEach(p => {
        const img = new Image();
        img.src = p.icon;
      });
    }, { timeout: 2000 });
  }
  
  // Evento de servicios solicitados
  document.addEventListener('servicesRequested', () => {
    showServicesMenu();
    ensureButtons();
  });
  
  // Auto-show si botón activo
  const servicesActive = document.querySelector('#servicios-btn.active');
  if (servicesActive) {
    showServicesMenu();
    ensureButtons();
  }
});

// Exponer función globalmente
window.ensureServiceButtons = ensureButtons;
