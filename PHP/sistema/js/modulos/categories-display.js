if (typeof window !== 'undefined' && window.currentView === undefined) {
    window.currentView = 'categories'; 
}
// In-memory caches to make header clicks instant
let cachedCategories = Array.isArray(window?.categories) && window.categories.length ? window.categories : null;
let cachedProducts = {};
// Seed cache with preloaded products if available
if (Array.isArray(window?.products) && window.products.length) {
    cachedProducts.all = window.products;
}
// Small helper cache using sessionStorage to persist across navigations
function ssGet(key) {
    try { return JSON.parse(sessionStorage.getItem(key)); } catch { return null; }
}
function ssSet(key, val) {
    try { sessionStorage.setItem(key, JSON.stringify(val)); } catch {}
}
function getNoSubCache(categoryId) {
    const mem = cachedProducts[`cat_${categoryId}_nosub`];
    if (Array.isArray(mem)) return mem;
    const ss = ssGet(`cat_${categoryId}_nosub`);
    return Array.isArray(ss) ? ss : null;
}
function setNoSubCache(categoryId, data) {
    if (Array.isArray(data)) {
        cachedProducts[`cat_${categoryId}_nosub`] = data;
        ssSet(`cat_${categoryId}_nosub`, data);
    }
}
async function prefetchNoSub(categoryId, signal) {
    const existing = getNoSubCache(categoryId);
    if (existing) return existing;
    const url = `get_products.php?categoria=${encodeURIComponent(categoryId)}&sin_subcategoria=1`;
    const res = await fetch(url, { signal }).catch(() => null);
    if (!res) return null;
    const data = await res.json().catch(() => null);
    if (Array.isArray(data)) setNoSubCache(categoryId, data);
    return data;
}
// Helper to refresh categories in background and update cache/UI if needed
async function refreshCategoriesCache(updateUI = false) {
    try {
        const res = await fetch('get_categories.php');
        const fresh = await res.json();
        if (Array.isArray(fresh) && fresh.length) {
            cachedCategories = fresh;
            // If we should re-render (e.g., after an instant render), do so subtly
            if (updateUI && window.currentView === 'categories') {
                window.categories = fresh;
                if (typeof window.initCarousels === 'function') {
                    window.initCarousels();
                } else {
                    renderCategoriesGrid(fresh, { skipTransition: true });
                }
            }
        }
    } catch (e) {}
}
// Simple navigation state to support contextual back and indicator updates
window.navState = {
    isDrilled: false,
    categoryId: null,
    categoryName: null,
    subcategoryId: null,
    subcategoryName: null,
    lastSubcategories: null,
    fromView: null // Para recordar de dónde venimos (cats_and_subs, subcategories, categories)
};
// Helpers: fetch page title and render section titles above grids
async function getPageTitle(page, fallback) {
    try {
        const r = await fetch(`get_page_title.php?page=${encodeURIComponent(page)}`);
        const d = await r.json();
        if (d?.success && d.title) return d.title;
    } catch {}
    return fallback;
}
function ensureTitleElement(id, text) {
    let el = document.getElementById(id);
    if (!el) {
        el = document.createElement('h2');
        el.id = id;
        el.className = 'section-title';
        // Minimal style to make it look nice without touching global CSS
        el.style.cssText = 'margin: 10px 0 12px; font-size: 1.2rem; font-weight: 700; color: #222;';
    }
    // Always ensure the title sits right above the intended container
    const mainArea = document.querySelector('.main-categories-area');
    const anchor = (id === 'categories-section-title')
        ? document.getElementById('categories-container')
        : document.getElementById('subcategories-container');
    if (mainArea && anchor && anchor.parentNode && el) {
        anchor.parentNode.insertBefore(el, anchor);
    }
    el.textContent = text || '';
    el.style.display = text ? 'block' : 'none';
    return el;
}
function setTitleVisibility(id, visible) {
    const el = document.getElementById(id);
    if (el) el.style.display = visible ? 'block' : 'none';
}
let productosBtn, serviciosBtn, categoriesContainer, subcategoriesContainer, productsContainer, backButton;
function initializeDOMReferences() {
    productosBtn = document.getElementById('productos-btn');
    serviciosBtn = document.getElementById('servicios-btn');
    categoriesContainer = document.getElementById('categories-container');
    subcategoriesContainer = document.getElementById('subcategories-container');
    productsContainer = document.getElementById('products-container');
    backButton = document.getElementById('back-button');
    // Configurar evento click del botón de regresar
    if (backButton) {
        backButton.addEventListener('click', () => {
            navigateBack();
        });
    }
}
// Funciones para controlar el botón de regresar
function showBackButton() {
    if (backButton) {
        backButton.classList.remove('hidden');
    }
}
function hideBackButton() {
    if (backButton) {
        backButton.classList.add('hidden');
    }
}
function updateContainersVisibility(view) {
    if (categoriesContainer && subcategoriesContainer && productsContainer) {
    // Use CSS grid for consistent 3-per-row layout
    categoriesContainer.style.display = view === 'categories' ? 'block' : 'none';
    subcategoriesContainer.style.display = view === 'subcategories' ? 'grid' : 'none';
    productsContainer.style.display = view === 'products' ? 'grid' : 'none';
    }
}
async function loadCategories() {
    try {
    // NO ocultar el título de categorías en la vista de categorías principales
    // setTitleVisibility('categories-section-title', false); // COMENTADO - queremos que SE VEA
    setTitleVisibility('subcategories-section-title', false);
    // MOSTRAR el header de categorías en vista de categorías
    const categoriesHeader = document.getElementById('categories-header');
    const categoriesSectionTitle = document.getElementById('categories-section-title');
    if (categoriesHeader) {
        categoriesHeader.style.display = 'block'; // MOSTRAR en categorías principales
        categoriesHeader.classList.remove('hidden');
    }
    if (categoriesSectionTitle) {
        categoriesSectionTitle.style.display = 'block';
        // RESTAURAR el título original con el ícono
        categoriesSectionTitle.innerHTML = '<i class="bi bi-grid-3x3-gap"></i>\n                    Categorías Principales';
    }
    // OCULTAR el título de productos en vista de categorías
    const productsHeader = document.getElementById('products-header');
    if (productsHeader) {
        productsHeader.style.display = 'none'; // OCULTAR en categorías principales
    }
    // Ocultar subcategorías
    const subcategoriesHeader = document.getElementById('subcategories-header');
    if (subcategoriesHeader) {
        subcategoriesHeader.style.display = 'none';
    }
    // Ocultar cats+subs
    const catsSubsHeader = document.getElementById('cats-subs-header');
    if (catsSubsHeader) {
        catsSubsHeader.style.display = 'none';
    }
        // Even if already in categories, ensure indicator/back button and containers sync
        if (window.categoryIndicator) {
            window.categoryIndicator.setType('productos');
            window.categoryIndicator.setCategory('Categorías');
        }
        window.currentView = 'categories';
        // Reset drill state when returning to root categories (pero NO borrar fromView)
        window.navState.isDrilled = false;
        window.navState.categoryId = null;
        window.navState.categoryName = null;
        window.navState.subcategoryId = null;
        window.navState.subcategoryName = null;
        window.navState.lastSubcategories = null;
        // fromView se mantiene para saber de dónde venimos
        
        // Ocultar botón de regresar en vista principal
        hideBackButton();
        // Keep indicator in sync immediately for Categories root
        if (window.categoryIndicator) {
            window.categoryIndicator.setType('productos');
            window.categoryIndicator.setCategory('Categorías');
        }
        // Always reset containers so products don't linger in categories view
        try {
            const catsC = document.getElementById('categories-container');
            const subsC = document.getElementById('subcategories-container');
            const prodC = document.getElementById('products-container');
            if (catsC) {
                catsC.style.display = 'block'; // Carousel usa block, no grid
                catsC.classList.remove('hidden'); // Remover clase hidden
            }
            if (subsC) { 
                subsC.style.display = 'none'; 
                subsC.innerHTML = '';
                subsC.classList.add('hidden'); // Agregar clase hidden
            }
            if (prodC) { 
                prodC.style.display = 'none'; 
                prodC.innerHTML = '';
                prodC.classList.add('hidden'); // Agregar clase hidden
            }
        } catch {}
        // Use cached categories if present for instant paint
        const categories = Array.isArray(cachedCategories) && cachedCategories.length
            ? cachedCategories
            : (Array.isArray(window?.categories) && window.categories.length ? window.categories : null);
        if (Array.isArray(categories) && categories.length) {
            renderCategoriesGrid(categories);
            // Refresh quietly in background to keep cache fresh
            refreshCategoriesCache(false);
        } else {
            // Fallback to fetch when nothing cached
            await refreshCategoriesCache(false);
            renderCategoriesGrid(Array.isArray(cachedCategories) ? cachedCategories : []);
        }
        // NO ocultar el título de categorías cuando estamos en la vista de categorías
        // setTitleVisibility('categories-section-title', false); // COMENTADO
        setTitleVisibility('subcategories-section-title', false);
    } catch (error) {
    }
};
// ===================================================
// 🎠 CATEGORÍAS EN CARRUSEL (igual que featured-products)
// ===================================================
const CategoriesCarousel = {
    currentSlide: 0,
    autoSlideInterval: null,
    itemsPerSlide: 4,
    totalSlides: 0,
    categories: [],

    updateItemsPerSlide() {
        const w = window.innerWidth;
        if (w <= 480)       this.itemsPerSlide = 1;
        else if (w <= 768)  this.itemsPerSlide = 2;
        else if (w <= 1200) this.itemsPerSlide = 3;
        else                this.itemsPerSlide = Math.min(4, this.categories.length);
    },

    createCard(category) {
        const imgSrc = category.imagen_categoria
            ? (category.imagen_categoria.startsWith('data:') ? category.imagen_categoria : category.imagen_categoria)
            : null;
        const imgHTML = imgSrc
            ? `<img src="${imgSrc}" alt="${category.nombre_categoria}" decoding="async"
                    style="max-width:80%; max-height:80%; object-fit:contain;">`
            : `<div style="font-size:3rem; color:#ccc;"><i class="bi bi-grid"></i></div>`;

        // Escapar el ID correctamente para onclick (agregar comillas si es string)
        const categoryIdForOnclick = typeof category.id_categoria === 'string' 
            ? `'${category.id_categoria}'` 
            : category.id_categoria;

        return `
            <div class="featured-product-card category-carousel-card"
                 data-category-id="${category.id_categoria}"
                 onclick="openCategory(${categoryIdForOnclick})"
                 style="cursor:pointer; border-top:4px solid #F6DA01;">
                <div class="featured-product-image" style="background:#f8f8f8;">
                    ${imgHTML}
                </div>
                <div class="product-content">
                    <h3 class="featured-product-title">${category.nombre_categoria}</h3>
                    <button class="featured-add-to-cart category-enter-btn"
                            data-category-id="${category.id_categoria}"
                            onclick="event.stopPropagation(); openCategory(${categoryIdForOnclick})"
                            style="background:#222; color:#F6DA01;">
                        <i class="fa fa-arrow-right"></i>
                        Ver Productos
                    </button>
                </div>
            </div>
        `;
    },

    render(categories) {
        const container = document.getElementById('categories-container');
        if (!container) return;
        this.categories = categories || [];
        if (this.categories.length === 0) return;

        this.updateItemsPerSlide();

        const cardsHTML = this.categories.map(c => this.createCard(c)).join('');

        container.style.display = 'block';
        container.innerHTML = `
            <div class="featured-products-carousel-wrapper">
                <button class="featured-carousel-btn prev" id="cats-carousel-prev">
                    <i class="fa fa-chevron-left"></i>
                </button>
                <div class="featured-products-grid">
                    <div class="featured-products-carousel" id="cats-carousel">
                        ${cardsHTML}
                    </div>
                </div>
                <button class="featured-carousel-btn next" id="cats-carousel-next">
                    <i class="fa fa-chevron-right"></i>
                </button>
            </div>
            <div class="featured-carousel-dots" id="cats-carousel-dots"></div>
        `;

        this.initCarousel();
        this.attachEvents(container, categories);
    },

    initCarousel() {
        const carousel = document.getElementById('cats-carousel');
        const prevBtn  = document.getElementById('cats-carousel-prev');
        const nextBtn  = document.getElementById('cats-carousel-next');
        const dotsWrap = document.getElementById('cats-carousel-dots');
        if (!carousel) return;

        this.currentSlide = 0;
        this.totalSlides = Math.ceil(this.categories.length / this.itemsPerSlide);

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
        document.getElementById('cats-carousel-prev').addEventListener('click', () => this.prevSlide());
        document.getElementById('cats-carousel-next').addEventListener('click', () => this.nextSlide());

        carousel.addEventListener('mouseenter', () => this.stopAutoSlide());
        carousel.addEventListener('mouseleave', () => this.startAutoSlide());

        this.startAutoSlide();
        requestAnimationFrame(() => this.goToSlide(0));
    },

    goToSlide(index) {
        const carousel = document.getElementById('cats-carousel');
        if (!carousel || this.totalSlides === 0) return;

        if (index >= this.totalSlides)  this.currentSlide = 0;
        else if (index < 0)             this.currentSlide = this.totalSlides - 1;
        else                            this.currentSlide = index;

        const cards = carousel.querySelectorAll('.featured-product-card');
        if (!cards.length) return;
        const cardWidth = cards[0].offsetWidth;
        const offset = -(this.currentSlide * this.itemsPerSlide * (cardWidth + 20));
        carousel.style.transition = 'transform 0.6s ease-in-out';
        carousel.style.transform = `translateX(${offset}px)`;

        document.querySelectorAll('#cats-carousel-dots .featured-carousel-dot').forEach((d, i) => {
            d.classList.toggle('active', i === this.currentSlide);
        });
    },

    nextSlide() { if (this.totalSlides > 1) this.goToSlide((this.currentSlide + 1) % this.totalSlides); },
    prevSlide() { if (this.totalSlides > 1) this.goToSlide((this.currentSlide - 1 + this.totalSlides) % this.totalSlides); },

    startAutoSlide() {
        this.stopAutoSlide();
        if (this.totalSlides <= 1) return;
        this.autoSlideInterval = setInterval(() => this.nextSlide(), 5000);
    },

    stopAutoSlide() {
        if (this.autoSlideInterval) { clearInterval(this.autoSlideInterval); this.autoSlideInterval = null; }
    },

    attachEvents(container, categories) {
        // 🚀 DESACTIVADO - Ahora redireccionamos a categoria.php en vez de navegación inline
        // El onclick="openCategory()" en index.php maneja la redirección
        return;
        
        /* CÓDIGO VIEJO COMENTADO - navegación inline
        container.addEventListener('click', (ev) => {
            const btn = ev.target.closest('.category-enter-btn, .category-carousel-card');
            if (!btn) return;
            const id = parseInt(btn.dataset.categoryId, 10);
            const category = categories.find(c => c.id_categoria === id || c.id_categoria === String(id));
            if (!category) return;

            // Replicate existing click logic from renderCategoriesGrid
            window.navState.isDrilled = true;
            window.navState.categoryId = category.id_categoria;
            window.navState.categoryName = category.nombre_categoria;
            window.navState.subcategoryId = null;
            window.navState.subcategoryName = null;
            window.navState.lastSubcategories = category.subcategorias || [];
            window.navState.fromView = 'categories';
            if (window.categoryIndicator) {
                window.categoryIndicator.setType('productos');
                window.categoryIndicator.setCategory(category.nombre_categoria);
            }
            // Scroll to main area
            const mainArea = document.querySelector('.main-categories-area');
            if (mainArea) mainArea.scrollIntoView({ behavior: 'smooth', block: 'start' });

            if (category.subcategorias && category.subcategorias.length > 0) {
                loadSubcategories(category.id_categoria, category.subcategorias, category.nombre_categoria);
            } else {
                loadProducts(null, category.id_categoria, { categoryName: category.nombre_categoria, allFromCategory: true });
            }
        });
        */
    }
};
// ===================================================

// Extracted renderer for categories grid to reuse on refresh
function renderCategoriesGrid(categories, options = {}) {
    const { skipTransition = false } = options;
    const container = document.getElementById('categories-container');
    const subs = document.getElementById('subcategories-container');
    const prods = document.getElementById('products-container');

    if (Array.isArray(categories) && categories.length) {
        window.categories = categories;
        cachedCategories = categories;
    }

    if (subs) subs.style.display = 'none';
    if (prods) prods.style.display = 'none';

    if (container) {
        container.style.display = 'block';
        container.classList.remove('hidden');
    }

    // El carrusel infinito lo renderiza CarouselManager en index.php (no CategoriesCarousel legacy)
    if (typeof window.initCarousels === 'function') {
        window.initCarousels();
    } else if (container && !container.querySelector('.infinite-carousel-mode')) {
        CategoriesCarousel.render(categories);
    }

    const categoriesHeader = document.getElementById('categories-header');
    const categoriesSectionTitle = document.getElementById('categories-section-title');
    if (categoriesHeader) {
        categoriesHeader.style.display = 'block';
        categoriesHeader.classList.remove('hidden');
    }
    if (categoriesSectionTitle) {
        categoriesSectionTitle.style.display = 'block';
        categoriesSectionTitle.innerHTML = '<i class="bi bi-grid-3x3-gap"></i>\n                    Categorías Principales';
    }
    const productsHeader = document.getElementById('products-header');
    if (productsHeader) productsHeader.style.display = 'none';
    if (!skipTransition && container) {
        requestAnimationFrame(() => container.classList.add('fade-grid', 'show'));
    }
}

// ---- OLD renderCategoriesGrid kept as renderCategoriesGridLegacy for internal use ----
function renderCategoriesGridLegacy(categories, options = {}) {
    const { skipTransition = false } = options;
    const container = document.getElementById('categories-container');
    const template = document.getElementById('category-template');
    const subs = document.getElementById('subcategories-container');
    const prods = document.getElementById('products-container');
    if (!container || !template) return;
    container.innerHTML = '';
    categories.forEach(category => {
        const clone = template.content.cloneNode(true);
    const card = clone.querySelector('.category-card');
        const img = clone.querySelector('.category-image');
        const title = clone.querySelector('.category-title');
        const placeholder = clone.querySelector('.no-image-placeholder');
        if (category.imagen_categoria) {
            if (category.imagen_categoria.startsWith('data:')) {
                img.src = category.imagen_categoria;
            } else {
                img.src = `${category.imagen_categoria}`;
            }
            img.style.display = 'block';
            img.style.opacity = '1';
            img.style.visibility = 'visible';
            placeholder.style.display = 'none';
            img.onerror = () => {
                img.style.display = 'none';
                placeholder.style.display = 'flex';
            };
        } else {
            img.style.display = 'none';
            placeholder.style.display = 'flex';
        }
        if (title) title.textContent = category.nombre_categoria;
        // Prefetch no-sub products on hover to make first click instant-ish
        let hoverCtrl;
        card.addEventListener('mouseenter', () => {
            try {
                if (hoverCtrl) hoverCtrl.abort();
                hoverCtrl = new AbortController();
                prefetchNoSub(category.id_categoria, hoverCtrl.signal);
            } catch {}
        });
        
        // 🚀 CLICK DESACTIVADO - Ahora redireccionamos a categoria.php
        // card.addEventListener('click', () => { ... }); COMENTADO
        /* CÓDIGO VIEJO COMENTADO
        card.addEventListener('click', () => {
            // Set drill state
            window.navState.isDrilled = true;
            window.navState.categoryId = category.id_categoria;
            window.navState.categoryName = category.nombre_categoria;
            window.navState.subcategoryId = null;
            window.navState.subcategoryName = null;
            window.navState.lastSubcategories = category.subcategorias || [];
            window.navState.fromView = 'categories'; // Venimos de la vista normal de categorías
            if (window.categoryIndicator) {
                window.categoryIndicator.setType('productos');
                window.categoryIndicator.setCategory(category.nombre_categoria);
            }
            if (category.subcategorias && category.subcategorias.length > 0) {
                loadSubcategories(category.id_categoria, category.subcategorias, category.nombre_categoria);
            } else {
                // Cargar TODOS los productos de esta categoría (sin filtro de subcategoría)
                loadProducts(null, category.id_categoria, { categoryName: category.nombre_categoria, allFromCategory: true });
            }
        });
        */
        
        container.appendChild(clone);
    });
    // Show categories grid, hide others
    container.classList.remove('show');
    subs.classList.remove('show');
    prods.classList.remove('show');
    container.style.display = 'grid';
    subs.style.display = 'none';
    prods.style.display = 'none';
    // IMPORTANTE: Asegurar que el header de categorías esté visible después de renderizar
    const categoriesHeader = document.getElementById('categories-header');
    const categoriesSectionTitle = document.getElementById('categories-section-title');
    if (categoriesHeader) {
        categoriesHeader.style.display = 'block';
        categoriesHeader.classList.remove('hidden');
    }
    if (categoriesSectionTitle) {
        categoriesSectionTitle.style.display = 'block';
        // RESTAURAR el título original siempre que renderizamos categorías
        categoriesSectionTitle.innerHTML = '<i class="bi bi-grid-3x3-gap"></i>\n                    Categorías Principales';
    }
    // Ocultar otros headers
    const productsHeader = document.getElementById('products-header');
    if (productsHeader) productsHeader.style.display = 'none';
    if (!skipTransition) {
        requestAnimationFrame(() => {
            container.classList.add('fade-grid','show');
        });
    }
}
function navigateBack() {
    // Si venimos del carousel o all-products, volver a mostrar los carruseles
    if (window.navState?.fromView === 'carousel' || window.navState?.fromView === 'all-products') {
        // Mostrar carruseles
        const featuredSection = document.querySelector('.featured-products-section');
        const servicesSection = document.querySelector('.services-section');
        const categoriesSection = document.querySelector('.categories-visual-section');
        
        if (featuredSection) {
            featuredSection.style.display = 'block';
            featuredSection.style.setProperty('display', 'block', 'important');
        }
        if (servicesSection) {
            servicesSection.style.display = 'block';
            servicesSection.style.setProperty('display', 'block', 'important');
        }
        if (categoriesSection) {
            categoriesSection.style.display = 'block';
            categoriesSection.style.setProperty('display', 'block', 'important');
        }
        
        // Ocultar contenedores de navegación
        const subsSection = document.getElementById('subcategories-section');
        const prodsSection = document.getElementById('products-display-section');
        const subsContainer = document.getElementById('subcategories-container');
        const prodsContainer = document.getElementById('products-container');
        
        if (subsSection) {
            subsSection.style.display = 'none';
            subsSection.style.setProperty('display', 'none', 'important');
        }
        if (prodsSection) {
            prodsSection.style.display = 'none';
            prodsSection.style.setProperty('display', 'none', 'important');
        }
        if (subsContainer) {
            subsContainer.style.display = 'none';
            subsContainer.innerHTML = '';
        }
        if (prodsContainer) {
            prodsContainer.style.display = 'none';
            prodsContainer.innerHTML = '';
        }
        
        // Resetear estado
        window.navState.isDrilled = false;
        window.navState.fromView = null;
        window.navState.categoryId = null;
        window.navState.categoryName = null;
        window.navState.subcategoryId = null;
        window.navState.subcategoryName = null;
        window.currentView = '';
        
        hideBackButton();
        
        // Scroll suave al inicio
        window.scrollTo({ top: 0, behavior: 'smooth' });
        return;
    }
    
    // Si venimos de productos y estábamos en cat+subcat, volver a cat+subcat
    if (window.currentView === 'products' && window.navState.fromView === 'cats_and_subs') {
        document.dispatchEvent(new CustomEvent('headerFilter', { detail: { mode: 'cats_and_subs' } }));
        return;
    }
    
    // Si estamos en subcategorías y venimos de cat+subcat, volver a cat+subcat
    if (window.currentView === 'subcategories' && window.navState.fromView === 'cats_and_subs') {
        document.dispatchEvent(new CustomEvent('headerFilter', { detail: { mode: 'cats_and_subs' } }));
        return;
    }
    
    // If we are in products and came from a subcategory, go back to subcategories
    if (window.currentView === 'products' && window.navState && window.navState.subcategoryId) {
        if (window.navState.categoryId && window.navState.lastSubcategories) {
            loadSubcategories(window.navState.categoryId, window.navState.lastSubcategories || [], window.navState.categoryName || '');
            if (window.categoryIndicator) window.categoryIndicator.setCategory(window.navState.categoryName || 'Categorías');
        } else {
            // Fallback to flat subcategories view if we don't know the parent category
            document.dispatchEvent(new CustomEvent('headerFilter', { detail: { mode: 'subcategories' } }));
        }
        return;
    }
    
    // If we are in products from a category with no subcategories, go back to categories
    if (window.currentView === 'products') {
        loadCategories();
        return;
    }
    
    // If we are in subcategories, go back to categories  
    if (window.currentView === 'subcategories') {
        loadCategories();
        return;
    }
}
async function loadSubcategories(categoryId, subcategories, categoryName = '') {
    window.currentView = 'subcategories';
    window.navState.categoryId = categoryId;
    window.navState.categoryName = categoryName || window.navState.categoryName;
    window.navState.subcategoryId = null;
    window.navState.subcategoryName = null;
    window.navState.lastSubcategories = subcategories;
    // Mostrar botón de regresar
    showBackButton();
    if (window.categoryIndicator) {
        window.categoryIndicator.setType('productos');
        window.categoryIndicator.setCategory(window.navState.categoryName || 'Categorías');
    }
    
    // Ocultar secciones de carruseles
    const featuredSection = document.querySelector('.featured-products-section');
    const servicesSection = document.querySelector('.services-section');
    const categoriesSection = document.querySelector('.categories-visual-section');
    
    if (featuredSection) featuredSection.style.setProperty('display', 'none', 'important');
    if (servicesSection) servicesSection.style.setProperty('display', 'none', 'important');
    if (categoriesSection) categoriesSection.style.setProperty('display', 'none', 'important');
    
    // Mostrar sección de subcategorías
    const subcategoriesSection = document.getElementById('subcategories-section');
    if (subcategoriesSection) {
        subcategoriesSection.style.display = 'block';
    }
    
    // Actualizar título de subcategorías
    const subcategoriesHeader = document.getElementById('subcategories-header');
    if (subcategoriesHeader) {
        subcategoriesHeader.textContent = categoryName || 'Subcategorías';
        subcategoriesHeader.style.display = 'block';
        subcategoriesHeader.style.color = '#F6DA01';
        subcategoriesHeader.style.fontSize = '3.5rem';
        subcategoriesHeader.style.fontWeight = '900';
        subcategoriesHeader.style.textTransform = 'uppercase';
        subcategoriesHeader.style.letterSpacing = '2px';
    }
    
    // Ocultar otros headers
    const categoriesHeader = document.getElementById('categories-header');
    if (categoriesHeader) {
        categoriesHeader.style.display = 'none';
    }
    const productsHeader = document.getElementById('products-header');
    if (productsHeader) {
        productsHeader.style.display = 'none';
    }
    const catsSubsHeader = document.getElementById('cats-subs-header');
    if (catsSubsHeader) {
        catsSubsHeader.style.display = 'none';
    }
    const container = document.getElementById('subcategories-container');
    const template = document.getElementById('subcategory-template');
    // Ensure products are hidden in subcategories view
    const productsContainer = document.getElementById('products-container');
    if (productsContainer) {
        productsContainer.style.display = 'none';
        productsContainer.innerHTML = '';
        productsContainer.classList.add('hidden');
    }
    // Ocultar categorías también
    const categoriesContainer = document.getElementById('categories-container');
    if (categoriesContainer) {
        categoriesContainer.style.display = 'none';
        categoriesContainer.classList.add('hidden');
    }
    container.innerHTML = '';
    container.classList.remove('hidden'); // IMPORTANTE: Remover clase hidden
    // Renderizar subcategorías PRIMERO para respuesta instantánea
    const subFrag = document.createDocumentFragment();
    if ((subcategories && subcategories.length > 0)) {
        subcategories.forEach(subcategory => {
            const clone = template.content.cloneNode(true);
            const card = clone.querySelector('.subcategory-card');
            const img = clone.querySelector('.subcategory-image');
            const title = clone.querySelector('.subcategory-title');
            const placeholder = clone.querySelector('.no-image-placeholder');
            if (subcategory.imagen_subcategoria) {
                const src = subcategory.imagen_subcategoria.startsWith('data:') ? subcategory.imagen_subcategoria : `${subcategory.imagen_subcategoria}`;
                if (img) { img.src = src; img.style.display = 'block'; }
                if (placeholder) placeholder.style.display = 'none';
            } else {
                if (img) img.style.display = 'none';
                if (placeholder) placeholder.style.display = 'flex';
            }
            if (title) title.textContent = subcategory.nombre_subcategoria;
            if (card) {
                // 🚀 CLICK DESACTIVADO - En index.php no mostramos subcategorías, redireccionamos a categoria.php
                /* CÓDIGO VIEJO COMENTADO
                card.addEventListener('click', () => {
                    window.navState.subcategoryId = subcategory.id_subcategoria;
                    window.navState.subcategoryName = subcategory.nombre_subcategoria;
                    if (window.categoryIndicator) {
                        window.categoryIndicator.setCategory(subcategory.nombre_subcategoria);
                    }
                    loadProducts(subcategory.id_subcategoria, null, { subcategoryName: subcategory.nombre_subcategoria });
                });
                */
            }
            subFrag.appendChild(clone);
        });
        container.appendChild(subFrag);
    }
    // Mostrar inmediatamente las subcategorías
    const cats = document.getElementById('categories-container');
    cats.classList.remove('show');
    cats.classList.add('hidden');
    container.classList.remove('show');
    cats.style.display = 'none';
    container.style.display = 'grid';
    
    // Mostrar sección de subcategorías si existe (ya declarada arriba)
    if (subcategoriesSection) {
        subcategoriesSection.style.display = 'block';
    }
    
    requestAnimationFrame(() => {
        container.classList.add('fade-grid','show');
    });
    // Luego cargar productos sin subcategoría EN BACKGROUND (sin bloquear UI)
    if (categoryId) {
        const noSubKey = `cat_${categoryId}_nosub`;
        let plainProducts = getNoSubCache(categoryId);
        // Si hay cache, mostrar inmediatamente
        if (plainProducts && Array.isArray(plainProducts) && plainProducts.length) {
            prependNoSubProducts(container, plainProducts);
        }
        // Fetch en background para actualizar cache (no await para no bloquear)
        fetch(`get_products.php?categoria=${encodeURIComponent(categoryId)}&sin_subcategoria=1`)
            .then(resp => resp.json())
            .then(freshProducts => {
                if (Array.isArray(freshProducts)) {
                    setNoSubCache(categoryId, freshProducts);
                    // Solo actualizar UI si cambió el contenido y seguimos en la misma vista
                    if (window.currentView === 'subcategories' && window.navState.categoryId === categoryId) {
                        if (!plainProducts || JSON.stringify(plainProducts) !== JSON.stringify(freshProducts)) {
                            prependNoSubProducts(container, freshProducts);
                        }
                    }
                }
            })
            .catch(() => {});
    }
    // Si no hay subcategorías, mostrar mensaje
    if ((!subcategories || subcategories.length === 0)) {
        const hasChildren = container.querySelector('.product-card');
        if (!hasChildren) {
            container.innerHTML = '<div class="no-products">No hay productos en esta categoría</div>';
            return;
        }
    }
}
// Helper para agregar productos sin subcategoría al principio del contenedor
function prependNoSubProducts(container, plainProducts) {
    if (!Array.isArray(plainProducts) || plainProducts.length === 0) return;
    // Remover productos sin sub anteriores si existen
    const oldLabel = container.querySelector('[data-nosub-label]');
    if (oldLabel) {
        let next = oldLabel.nextElementSibling;
        while (next && next.classList.contains('product-card')) {
            const toRemove = next;
            next = next.nextElementSibling;
            toRemove.remove();
        }
        oldLabel.remove();
    }
    const frag = document.createDocumentFragment();
    const label = document.createElement('div');
    label.textContent = 'Productos sin subcategoría';
    label.setAttribute('data-nosub-label', 'true');
    label.style.cssText = 'grid-column: 1 / -1; font-weight:700; margin: 20px 0 15px; color:#F6DA01; font-size: 24px; text-transform: uppercase; letter-spacing: 1px;';
    frag.appendChild(label);
    const productTpl = document.getElementById('product-overlay-template');
    if (productTpl) {
        plainProducts.forEach(product => {
            const pclone = productTpl.content.cloneNode(true);
            const img = pclone.querySelector('.product-img');
            const title = pclone.querySelector('.product-title');
            const price = pclone.querySelector('.product-price');
            const addToCartBtn = pclone.querySelector('.add-to-cart');
            if (img) {
                img.src = product.imagen_principal ? `${product.imagen_principal}` : 'images/no-image.png';
                img.alt = product.nombre_producto || 'Producto';
            }
            if (title) title.textContent = product.nombre_producto || '';
            const productPrice = parseFloat(product.precio) || 0;
            const productDiscount = parseFloat(product.descuento) || 0;
            const priceContainer = pclone.querySelector('.price-container') || pclone;
            if (window.VBPrecio) {
                window.VBPrecio.aplicarEnContenedor(priceContainer, productPrice, productDiscount);
            } else if (price) {
                price.textContent = `$${Math.max(0, productPrice - productDiscount).toFixed(2)}`;
            }
            if (addToCartBtn) {
                addToCartBtn.classList.add('add-to-cart-btn');
                addToCartBtn.type = 'button';
                addToCartBtn.dataset.productId = product.id_producto;
                addToCartBtn.dataset.nombre = product.nombre_producto || '';
                addToCartBtn.dataset.precio = productPrice;
                addToCartBtn.dataset.descuento = productDiscount;
                addToCartBtn.dataset.imagen = product.imagen_principal || 'images/no-image.png';
                if (window.Cart?.registerProduct) {
                    window.Cart.registerProduct(product);
                }
            }
            frag.appendChild(pclone);
        });
    }
    // Agregar separador
    const sep = document.createElement('div');
    sep.style.cssText = 'grid-column: 1 / -1; height: 1px; background: #eee; margin: 12px 0 8px;';
    frag.appendChild(sep);
    const subLabel = document.createElement('div');
    subLabel.textContent = 'Subcategorías';
    subLabel.style.cssText = 'grid-column: 1 / -1; font-weight:700; margin: 20px 0 15px; color:#F6DA01; font-size: 24px; text-transform: uppercase; letter-spacing: 1px;';
    frag.appendChild(subLabel);
    container.prepend(frag);
}
async function loadProducts(subcategoryId, categoryId, meta = {}) {
    try {
        window.currentView = 'products';
        // Mostrar botón de regresar
        showBackButton();
    // Hide section titles once we drill into any products view,
    // even if we came from Cat + Subcat
    setTitleVisibility('categories-section-title', false);
    setTitleVisibility('subcategories-section-title', false);
    // Ocultar el título "NUESTROS PRODUCTOS" cuando es un producto específico
    const categoriesHeader = document.getElementById('categories-header');
    if (categoriesHeader) {
        categoriesHeader.style.display = 'none';
    }
    
    // Actualizar el título de productos con el nombre correcto
    const productsHeaderTitle = document.getElementById('products-header');
    if (productsHeaderTitle) {
        const displayName = meta.subcategoryName || meta.categoryName || window.navState.subcategoryName || window.navState.categoryName || 'Productos';
        productsHeaderTitle.textContent = displayName;
        productsHeaderTitle.style.display = 'block';
        productsHeaderTitle.style.color = '#ffffff';
    }
        // Update nav state and indicator per context
        if (subcategoryId) {
            window.navState.subcategoryId = subcategoryId;
            if (meta.subcategoryName) window.navState.subcategoryName = meta.subcategoryName;
        } else if (categoryId) {
            window.navState.categoryId = categoryId;
            if (meta.categoryName) window.navState.categoryName = meta.categoryName;
        }
        if (window.categoryIndicator) {
            const label = meta.subcategoryName || meta.categoryName || window.navState.subcategoryName || window.navState.categoryName || 'Productos';
            window.categoryIndicator.setType('productos');
            window.categoryIndicator.setCategory(label);
        }
        // Immediately hide other grids to avoid overlap while data loads
        try {
            const cats = document.getElementById('categories-container');
            const subs = document.getElementById('subcategories-container');
            const prods = document.getElementById('products-container');
            if (cats) {
                cats.style.display = 'none';
                cats.classList.add('hidden');
            }
            if (subs) {
                subs.style.display = 'none';
                subs.classList.add('hidden');
            }
            if (prods) {
                prods.classList.remove('hidden'); // IMPORTANTE: remover hidden
                prods.style.display = 'grid';
            }
            
            // Mostrar sección de productos si existe
            const productsSection = document.getElementById('products-display-section');
            if (productsSection) {
                productsSection.style.display = 'block';
            }
            
            // Ocultar sección de subcategorías
            const subcategoriesSection = document.getElementById('subcategories-section');
            if (subcategoriesSection) {
                subcategoriesSection.style.display = 'none';
            }
        } catch {}
    const cacheKey = subcategoryId ? `sub_${subcategoryId}` : (meta.sinSubOnly ? `cat_${categoryId}_nosub` : (meta.allFromCategory ? `cat_${categoryId}_all` : `cat_${categoryId}`));
    if (cachedProducts[cacheKey]) {
            displayProducts(cachedProducts[cacheKey]);
            return;
        }
        let url = 'get_products.php';
        const params = new URLSearchParams();
        if (subcategoryId) {
            params.append('subcategoria', subcategoryId);
        } else if (categoryId) {
            params.append('categoria', categoryId);
            // Solo agregar sin_subcategoria si explícitamente se pide
            if (meta.sinSubOnly) {
                params.append('sin_subcategoria', '1');
            }
            // Si es allFromCategory, NO agregamos sin_subcategoria para traer TODOS
        }
        if (params.toString()) {
            url += '?' + params.toString();
        }
        const response = await fetch(url);
        const products = await response.json();
        cachedProducts[cacheKey] = products;
        displayProducts(products);
    } catch (error) {
    }
}
function displayProducts(products, keepSubcategoriesVisible = false) {
    const container = document.getElementById('products-container');
    const template = document.getElementById('product-template'); // Usar template correcto
    container.innerHTML = '';
    // Siempre usar grid para productos con overlay
    container.style.display = 'grid';
    container.style.justifyContent = 'center';
    container.style.alignItems = 'stretch';
    products.forEach(product => {
        const clone = template.content.cloneNode(true);
        const img = clone.querySelector('.product-img');
        const title = clone.querySelector('.product-title');
        const price = clone.querySelector('.product-price');
        const addToCartBtn = clone.querySelector('.add-to-cart');
        // Configurar imagen
        if (img) {
            img.src = product.imagen_principal ? `${product.imagen_principal}` : 'images/no-image.png';
            img.alt = product.nombre_producto || 'Producto';
        }
        // Configurar título
        if (title) {
            title.textContent = product.nombre_producto || 'Sin nombre';
        }
        // Calcular y configurar precio (descuento en pesos, igual que el carrito)
        const productPrice = parseFloat(product.precio) || 0;
        const productDiscount = parseFloat(product.descuento) || 0;
        const priceContainer = clone.querySelector('.price-container');
        if (window.VBPrecio && priceContainer) {
            window.VBPrecio.aplicarEnContenedor(priceContainer, productPrice, productDiscount);
        } else if (price) {
            price.textContent = `$${Math.max(0, productPrice - productDiscount).toFixed(2)}`;
        }
        // Configurar botón agregar al carrito
        if (addToCartBtn) {
            addToCartBtn.classList.add('add-to-cart-btn');
            addToCartBtn.type = 'button';
            addToCartBtn.dataset.productId = product.id_producto;
            addToCartBtn.dataset.nombre = product.nombre_producto || '';
            addToCartBtn.dataset.precio = productPrice;
            addToCartBtn.dataset.descuento = productDiscount;
            addToCartBtn.dataset.imagen = product.imagen_principal || 'images/no-image.png';
            if (window.Cart?.registerProduct) {
                window.Cart.registerProduct(product);
            }
        }
        container.appendChild(clone);
    });
    const cats = document.getElementById('categories-container');
    const subs = document.getElementById('subcategories-container');
    // Remover clases y ocultar otros contenedores
    cats.classList.remove('show');
    cats.classList.add('hidden');
    subs.classList.remove('show');
    subs.classList.add('hidden');
    container.classList.remove('show', 'hidden'); // IMPORTANTE: remover hidden también
    cats.style.display = 'none';
    subs.style.display = keepSubcategoriesVisible ? 'grid' : 'none';
    container.style.display = 'grid';
    
    requestAnimationFrame(() => {
        if (keepSubcategoriesVisible) {
            subs.classList.remove('hidden');
            subs.classList.add('fade-grid','show');
        }
        container.classList.add('fade-grid','show');
    });
    if (products.length === 0) {
        container.innerHTML = '<div class="no-products">No hay productos disponibles</div>';
    }
}
function addToCart(product) {
}
document.addEventListener('DOMContentLoaded', () => {
    initializeDOMReferences();
    updateContainersVisibility('categories');
    window.currentView = '';
    // Ocultar botón de regresar al inicio
    hideBackButton();
    if (typeof window !== 'undefined') {
        window.loadCategories = loadCategories;
        window.loadSubcategories = loadSubcategories;
        window.loadProducts = loadProducts;
    }
    loadCategories();
    // Header filters: categories | subcategories | cats_and_subs | products
    document.addEventListener('headerFilter', async (ev) => {
        const mode = ev?.detail?.mode;
        if (!mode) return;
        const catC = document.getElementById('categories-container');
        const subC = document.getElementById('subcategories-container');
        const prodC = document.getElementById('products-container');
        switch (mode) {
            case 'categories':
                // Mostrar todas las categorías
                window.currentView = '';
                window.navState.isDrilled = false;
                window.navState.fromView = null; // Resetear fromView
                
                // Ocultar botón de regresar cuando volvemos a categorías principales
                hideBackButton();
                
                await loadCategories();
                // Hide section titles (only Cat + Subcat shows titles)
                setTitleVisibility('categories-section-title', false);
                setTitleVisibility('subcategories-section-title', false);
                // Mostrar el header de categorías
                const categoriesHeaderCat = document.getElementById('categories-header');
                const categoriesSectionTitleCat = document.getElementById('categories-section-title');
                if (categoriesHeaderCat) {
                    categoriesHeaderCat.style.display = 'block';
                    categoriesHeaderCat.classList.remove('hidden');
                }
                if (categoriesSectionTitleCat) {
                    categoriesSectionTitleCat.style.display = 'block';
                    // RESTAURAR el título original con el ícono
                    categoriesSectionTitleCat.innerHTML = '<i class="bi bi-grid-3x3-gap"></i>\n                    Categorías Principales';
                }
                // Ocultar el título de productos
                const productsHeaderCat = document.getElementById('products-header');
                if (productsHeaderCat) productsHeaderCat.style.display = 'none';
                // Ocultar subcategorías
                const subcategoriesHeaderCat = document.getElementById('subcategories-header');
                if (subcategoriesHeaderCat) subcategoriesHeaderCat.style.display = 'none';
                // Ocultar cats+subs
                const catsSubsHeaderCat = document.getElementById('cats-subs-header');
                if (catsSubsHeaderCat) catsSubsHeaderCat.style.display = 'none';
                // Clear any lingering products
                if (prodC) {
                    prodC.innerHTML = '';
                    prodC.classList.add('hidden');
                }
                if (subC) {
                    subC.classList.add('hidden');
                }
                break;
            case 'subcategories':
                // Mostrar solo subcategorías de todas las categorías (flat)
                window.currentView = 'subcategories';
                window.navState.isDrilled = false;
                window.navState.fromView = null; // Resetear fromView
                
                // Ocultar botón de regresar en vista principal de subcategorías
                hideBackButton();
                
                // Ocultar categorías y productos
                catC.style.display = 'none';
                catC.classList.add('hidden');
                prodC.style.display = 'none';
                prodC.classList.add('hidden');
                if (prodC) prodC.innerHTML = '';
                subC.innerHTML = '';
                // Remover clase hidden del contenedor de subcategorías
                subC.classList.remove('hidden');
                // Ocultar el header de categorías
                const categoriesHeaderSub = document.getElementById('categories-header');
                if (categoriesHeaderSub) categoriesHeaderSub.style.display = 'none';
                // Ocultar el título de productos
                const productsHeaderSub = document.getElementById('products-header');
                if (productsHeaderSub) productsHeaderSub.style.display = 'none';
                // Ocultar cat+sub header
                const catsSubsHeaderSub = document.getElementById('cats-subs-header');
                if (catsSubsHeaderSub) catsSubsHeaderSub.style.display = 'none';
                // MOSTRAR el header de subcategorías
                const subcategoriesHeaderSub = document.getElementById('subcategories-header');
                if (subcategoriesHeaderSub) {
                    subcategoriesHeaderSub.classList.remove('hidden');
                    subcategoriesHeaderSub.style.display = 'block';
                    subcategoriesHeaderSub.style.visibility = 'visible';
                    subcategoriesHeaderSub.style.opacity = '1';
                }
                
                // RESTAURAR el título de subcategorías con su HTML original (no de BD)
                const subTitleElement = document.getElementById('subcategories-section-title');
                if (subTitleElement) {
                    // Restaurar el HTML completo con ícono y texto
                    subTitleElement.innerHTML = '<i class="bi bi-collection"></i>\n                    Subcategorías';
                    subTitleElement.style.display = 'block';
                    subTitleElement.style.visibility = 'visible';
                    subTitleElement.style.opacity = '1';
                }
                
                // Show subcategories as a grid for proper sizing
                subC.style.display = 'grid';
                try {
                    // Usar cache si existe para respuesta inmediata
                    const categories = Array.isArray(cachedCategories) && cachedCategories.length
                        ? cachedCategories
                        : (Array.isArray(window?.categories) && window.categories.length ? window.categories : null);
                    if (!categories) {
                        const res = await fetch('get_categories.php');
                        cachedCategories = await res.json();
                    }
                    const source = categories || cachedCategories || [];
                    const template = document.getElementById('subcategory-template');
                    source.forEach(cat => {
                        (cat.subcategorias || []).forEach(sub => {
                            const clone = template.content.cloneNode(true);
                            const img = clone.querySelector('.subcategory-image');
                            const title = clone.querySelector('.subcategory-title');
                            const placeholder = clone.querySelector('.no-image-placeholder');
                            if (sub.imagen_subcategoria) {
                                img.src = sub.imagen_subcategoria.startsWith('data:') ? sub.imagen_subcategoria : `${sub.imagen_subcategoria}`;
                                img.style.display = 'block';
                                placeholder.style.display = 'none';
                            } else {
                                img.style.display = 'none';
                                placeholder.style.display = 'flex';
                            }
                            title.textContent = sub.nombre_subcategoria;
                            // 🚀 CLICK DESACTIVADO - Redireccionamos a categoria.php
                            /* COMENTADO
                            clone.querySelector('.subcategory-card').addEventListener('click', () => {
                                window.navState.isDrilled = true;
                                window.navState.subcategoryId = sub.id_subcategoria;
                                window.navState.subcategoryName = sub.nombre_subcategoria;
                                window.navState.fromView = 'subcategories'; // Venimos de vista de subcategorías
                                if (window.categoryIndicator) window.categoryIndicator.setCategory(sub.nombre_subcategoria);
                                loadProducts(sub.id_subcategoria, null, { subcategoryName: sub.nombre_subcategoria });
                            });
                            */
                            subC.appendChild(clone);
                        });
                    });
                    // Refresh categories cache in background
                    refreshCategoriesCache(false);
                } catch (e) {}
                // Agregar animación de fade-in
                subC.classList.remove('show');
                requestAnimationFrame(() => {
                    subC.classList.add('fade-grid', 'show');
                });
                // NO ocultar los títulos en la vista de subcategorías individuales
                // Se deben mostrar con el header completo
                break;
            case 'cats_and_subs':
                // Mostrar categorías y subcategorías juntas; ocultar productos
                window.currentView = 'subcategories';
                window.navState.isDrilled = false;
                window.navState.fromView = 'cats_and_subs'; // Marcar que estamos en cats_and_subs
                
                // Ocultar botón de regresar en vista principal de cats+subs
                hideBackButton();
                
                prodC.style.display = 'none';
                prodC.innerHTML = '';
                prodC.classList.add('hidden');
                
                // MOSTRAR los headers individuales de categorías Y subcategorías
                const categoriesHeaderCatsSubs = document.getElementById('categories-header');
                if (categoriesHeaderCatsSubs) {
                    categoriesHeaderCatsSubs.style.display = 'block';
                    categoriesHeaderCatsSubs.classList.remove('hidden');
                }
                
                const subcategoriesHeaderCatsSubs = document.getElementById('subcategories-header');
                if (subcategoriesHeaderCatsSubs) {
                    subcategoriesHeaderCatsSubs.style.display = 'block';
                    subcategoriesHeaderCatsSubs.classList.remove('hidden');
                    // Agregar margen superior para separar de las categorías
                    subcategoriesHeaderCatsSubs.style.marginTop = '40px';
                }
                
                // OCULTAR el header de productos
                const productsHeaderCatsSubs = document.getElementById('products-header');
                if (productsHeaderCatsSubs) productsHeaderCatsSubs.style.display = 'none';
                
                // OCULTAR el header de cats+subs (no se usa en este modo)
                const catsSubsHeader = document.getElementById('cats-subs-header');
                if (catsSubsHeader) {
                    catsSubsHeader.style.display = 'none';
                    catsSubsHeader.classList.add('hidden');
                }
                
                // Remover clase hidden de subcategorías
                subC.classList.remove('hidden');
                
                // Mostrar contenedor de categorías
                catC.innerHTML = '';
                catC.style.display = 'grid';
                catC.classList.remove('hidden');
                
                // SIEMPRE cargar categorías si no hay cache
                let categories = Array.isArray(cachedCategories) && cachedCategories.length
                    ? cachedCategories
                    : (Array.isArray(window?.categories) && window.categories.length ? window.categories : null);
                
                // Si no hay categorías, hacer fetch ANTES de renderizar
                if (!categories) {
                    try {
                        const res = await fetch('get_categories.php');
                        categories = await res.json();
                        cachedCategories = categories;
                    } catch (e) {
                        categories = [];
                    }
                }
                
                const source = categories || [];
                const categoryTemplate = document.getElementById('category-template');
                // Verificar que catC esté disponible
                // Renderizar categorías con el cache disponible (INSTANTÁNEO)
                const catFrag = document.createDocumentFragment();
                source.forEach(category => {
                    const clone = categoryTemplate.content.cloneNode(true);
                    const card = clone.querySelector('.category-card');
                    const img = clone.querySelector('.category-image');
                    const title = clone.querySelector('.category-title');
                    const placeholder = clone.querySelector('.no-image-placeholder');
                    if (category.imagen_categoria) {
                        img.src = category.imagen_categoria.startsWith('data:') ? category.imagen_categoria : `${category.imagen_categoria}`;
                        img.style.display = 'block';
                        placeholder.style.display = 'none';
                    } else {
                        img.style.display = 'none';
                        placeholder.style.display = 'flex';
                    }
                    if (title) title.textContent = category.nombre_categoria;
                    // 🚀 CLICK DESACTIVADO - Redireccionamos a categoria.php
                    /* COMENTADO
                    card.addEventListener('click', () => {
                        window.navState.isDrilled = true;
                        window.navState.categoryId = category.id_categoria;
                        window.navState.categoryName = category.nombre_categoria;
                        window.navState.fromView = 'cats_and_subs'; // Recordar de dónde venimos
                        if (window.categoryIndicator) {
                            window.categoryIndicator.setType('productos');
                            window.categoryIndicator.setCategory(category.nombre_categoria);
                        }
                        if (category.subcategorias && category.subcategorias.length > 0) {
                            loadSubcategories(category.id_categoria, category.subcategorias, category.nombre_categoria);
                        } else {
                            loadProducts(null, category.id_categoria, { categoryName: category.nombre_categoria, allFromCategory: true });
                        }
                    });
                    */
                    catFrag.appendChild(clone);
                });
                catC.appendChild(catFrag);
                
                // ASEGURAR que catC esté visible
                catC.style.display = 'grid';
                catC.classList.remove('hidden', 'fade-grid');
                catC.classList.add('show');
                // Renderizar subcategorías inmediatamente con cache
                subC.innerHTML = '';
                subC.style.display = 'grid';
                const subFrag = document.createDocumentFragment();
                const template2 = document.getElementById('subcategory-template');
                source.forEach(cat => {
                    (cat.subcategorias || []).forEach(sub => {
                        const clone = template2.content.cloneNode(true);
                        const img = clone.querySelector('.subcategory-image');
                        const title = clone.querySelector('.subcategory-title');
                        const placeholder = clone.querySelector('.no-image-placeholder');
                        if (sub.imagen_subcategoria) {
                            img.src = sub.imagen_subcategoria.startsWith('data:') ? sub.imagen_subcategoria : `${sub.imagen_subcategoria}`;
                            img.style.display = 'block';
                            placeholder.style.display = 'none';
                        } else {
                            img.style.display = 'none';
                            placeholder.style.display = 'flex';
                        }
                        title.textContent = sub.nombre_subcategoria;
                        // 🚀 CLICK DESACTIVADO - Redireccionamos a categoria.php
                        /* COMENTADO
                        clone.querySelector('.subcategory-card').addEventListener('click', () => {
                            window.navState.isDrilled = true;
                            window.navState.subcategoryId = sub.id_subcategoria;
                            window.navState.subcategoryName = sub.nombre_subcategoria;
                            window.navState.fromView = 'cats_and_subs'; // Recordar de dónde venimos
                            if (window.categoryIndicator) window.categoryIndicator.setCategory(sub.nombre_subcategoria);
                            loadProducts(sub.id_subcategoria, null, { subcategoryName: sub.nombre_subcategoria });
                        });
                        */
                        subFrag.appendChild(clone);
                    });
                });
                subC.appendChild(subFrag);
                
                // ASEGURAR que subC esté visible
                subC.style.display = 'grid';
                subC.classList.remove('hidden', 'fade-grid');
                subC.classList.add('show');
                
                // Ya NO necesitamos actualizar cache en background porque ya lo hicimos arriba
                if (window.categoryIndicator) {
                    window.categoryIndicator.setType('productos');
                    window.categoryIndicator.setCategory('Categorías y Subcategorías');
                }
                // Marcar botón activo en header
                try {
                    const headerBar = document.getElementById('header-categories-bar');
                    if (headerBar) {
                        headerBar.querySelectorAll('.header-category-btn').forEach(b => b.classList.remove('active'));
                        const btn = document.getElementById('btn-cats-and-subs');
                        if (btn) btn.classList.add('active');
                    }
                } catch {}
                // Cargar títulos de la BD pero SIN destruir el HTML (mantener íconos)
                setTitleVisibility('categories-section-title', true);
                setTitleVisibility('subcategories-section-title', true);
                
                // Obtener título de categorías de la BD
                getPageTitle('categorias', 'Categorías Principales').then(title => {
                    const catTitle = document.getElementById('categories-section-title');
                    if (catTitle) {
                        const icon = catTitle.querySelector('i');
                        if (icon) {
                            // Si hay ícono, mantenerlo y solo cambiar el texto
                            catTitle.innerHTML = icon.outerHTML + '\n                    ' + title;
                        } else {
                            // Si no hay ícono, restaurar el original con el título de BD
                            catTitle.innerHTML = '<i class="bi bi-grid-3x3-gap"></i>\n                    ' + title;
                        }
                    }
                });
                
                // Obtener título de subcategorías de la BD
                getPageTitle('subcategorias', 'Subcategorías').then(title => {
                    const subTitle = document.getElementById('subcategories-section-title');
                    if (subTitle) {
                        const icon = subTitle.querySelector('i');
                        if (icon) {
                            // Si hay ícono, mantenerlo y solo cambiar el texto
                            subTitle.innerHTML = icon.outerHTML + '\n                    ' + title;
                        } else {
                            // Si no hay ícono, restaurar el original con el título de BD
                            subTitle.innerHTML = '<i class="bi bi-collection"></i>\n                    ' + title;
                        }
                    }
                });
                break;
            case 'products':
                // Mostrar todos los productos
                window.currentView = 'products';
                window.navState.isDrilled = false;
                
                // Ocultar botón de regresar en vista principal de todos los productos
                hideBackButton();
                
                catC.style.display = 'none';
                subC.style.display = 'none';
                catC.classList.add('hidden');
                subC.classList.add('hidden');
                // Ocultar el header de categorías
                const categoriesHeader = document.getElementById('categories-header');
                if (categoriesHeader) {
                    categoriesHeader.style.display = 'none';
                }
                // Ocultar subcategorías
                const subcategoriesHeaderProd = document.getElementById('subcategories-header');
                if (subcategoriesHeaderProd) {
                    subcategoriesHeaderProd.style.display = 'none';
                }
                // Ocultar cats+subs
                const catsSubsHeaderProd = document.getElementById('cats-subs-header');
                if (catsSubsHeaderProd) {
                    catsSubsHeaderProd.style.display = 'none';
                }
                // Mostrar el título "NUESTROS PRODUCTOS"
                const productsHeader = document.getElementById('products-header');
                if (productsHeader) {
                    productsHeader.style.display = 'block';
                }
                // Remover clase hidden del contenedor de productos
                prodC.classList.remove('hidden');
                try {
                    // Use cached products if available for instant render
                    const allP = Array.isArray(cachedProducts.all) && cachedProducts.all.length
                        ? cachedProducts.all
                        : (Array.isArray(window?.products) && window.products.length ? window.products : null);
                    if (allP) {
                        cachedProducts.all = allP;
                        displayProducts(allP, false);
                        // Refresh in background
                        fetch('get_products.php').then(r=>r.json()).then(data=>{
                            if (Array.isArray(data)) { cachedProducts.all = data; }
                        }).catch(()=>{});
                    } else {
                        const resP = await fetch('get_products.php');
                        const data = await resP.json();
                        cachedProducts.all = Array.isArray(data) ? data : [];
                        displayProducts(cachedProducts.all, false);
                    }
                } catch (e) {
                    displayProducts([], false);
                }
                if (window.categoryIndicator) {
                    window.categoryIndicator.setType('productos');
                    window.categoryIndicator.setCategory('Productos');
                }
                // Hide titles when no grids visible
                setTitleVisibility('categories-section-title', false);
                setTitleVisibility('subcategories-section-title', false);
                break;
        }
    });
});
