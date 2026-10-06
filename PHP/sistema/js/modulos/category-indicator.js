// Verificar si ya existe para evitar declaración duplicada
if (typeof window.CategoryIndicator === 'undefined') {

window.CategoryIndicator = class CategoryIndicator {
    constructor() {
        this.indicator = document.getElementById('category-indicator');
        this.indicatorText = document.getElementById('category-indicator-text');
        this.subtitle = this.indicator.querySelector('.category-subtitle');
        this.icon = this.indicator.querySelector('.category-icon');
    this.currentType = document.querySelector('#productos-btn.active') ? 'productos' : 'servicios';
    this.currentCategory = 'Categorías';
    this.currentHeaderMode = 'categories';
        this.init();
        this.setupEventListeners();
        this.setInitialState();
        this.indicator.style.transition = 'all 0.3s ease';
    }

    init() {
        this.updateIndicator();
    }

    refreshCategoryButtons() {
        const categoryBtns = document.querySelectorAll('.category-btn, .header-category-btn');
        categoryBtns.forEach(btn => {
            if (!btn.dataset.hasIndicatorListener) {
                btn.dataset.hasIndicatorListener = 'true';
                btn.addEventListener('click', () => {
                    const category = btn.dataset.category || btn.textContent.trim();
                    this.setCategory(category);
                });
            }
        });
    }

    setupEventListeners() {
        const productosBtn = document.getElementById('productos-btn');
        const serviciosBtn = document.getElementById('servicios-btn');
        if (productosBtn) {
            productosBtn.addEventListener('click', () => this.setType('productos'));
        }
        if (serviciosBtn) {
            serviciosBtn.addEventListener('click', () => this.setType('servicios'));
        }

        document.addEventListener('headerFilter', (ev) => {
            const mode = ev?.detail?.mode;
            if (!mode) return;
            this.currentHeaderMode = mode;
            const label = mode === 'categories' ? 'Categorías'
                        : mode === 'subcategories' ? 'Subcategorías'
                        : mode === 'cats_and_subs' ? 'Categorías y Subcategorías'
                        : mode === 'products' ? 'Productos'
                        : this.currentCategory;
            this.setCategory(label);
            if (this.currentType !== 'productos') this.setType('productos');
            this.syncHeaderButtons(mode);
        });

        document.addEventListener('click', (e) => {
            const categoryElement = e.target.closest('[data-category], .category-link');
            if (categoryElement) {
                const category = categoryElement.getAttribute('data-category') || categoryElement.textContent.trim();
                this.setCategory(category);
            }
        });

        const productsContainer = document.getElementById('products-container');
        if (productsContainer) {
            const observer = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    if (mutation.type === 'childList') {
                        const categoryLinks = productsContainer.querySelectorAll('[data-category], .category-link');
                        categoryLinks.forEach(link => {
                            if (!link.dataset.categoryHandlerAdded) {
                                link.dataset.categoryHandlerAdded = 'true';
                                link.addEventListener('click', (e) => {
                                    e.preventDefault();
                                    const category = link.getAttribute('data-category') || link.textContent.trim();
                                    this.setCategory(category);
                                });
                            }
                        });
                    }
                });
            });
            
            observer.observe(productsContainer, { childList: true, subtree: true });
        }

        const productCarousel = document.querySelector('.product-category-carousel');
        if (productCarousel) {
            productCarousel.addEventListener('click', (e) => {
                const categoryLink = e.target.closest('a[data-category]');
                if (categoryLink) {
                    e.preventDefault();
                    const category = categoryLink.getAttribute('data-category') || 
                                   categoryLink.textContent.trim();
                    this.setCategory(category);
                }
            });
        }

        document.body.addEventListener('click', (e) => {
            const categoryElement = e.target.closest('[data-category]');
            if (categoryElement && !categoryElement.closest('#header-categories-bar')) {
                const category = categoryElement.getAttribute('data-category') || 
                               categoryElement.textContent.trim();
                this.setCategory(category);
            }
        });

        window.addEventListener('scroll', () => {
            if (window.scrollY > 100) {
                this.indicator.classList.add('visible');
            } else {
                this.indicator.classList.remove('visible');
            }
        });
    }

    setType(type) {
        if (this.currentType === type) {
            this.syncUI();
            return;
        }
        this.currentType = type;
        if (type === 'servicios') {
            this.currentCategory = 'Servicios';
            this.indicator.style.display = 'none'; 
        } else {
            this.currentCategory = 'Categorías';
            this.indicator.style.display = 'block'; 
        }
        this.updateIndicator();
    }

    setCategory(category) {
        if (this.currentCategory === category) {
            this.syncUI();
            return;
        }
        this.currentCategory = category;
        this.updateIndicator();
    }
    updateIndicator() {
        if (!this.indicator) return; // Si no existe el indicador, salir
        
        this.indicator.classList.remove('category-indicator--swap-in');
        this.indicator.classList.add('category-indicator--swap-out');
        requestAnimationFrame(() => {
            if (this.indicatorText) {
                this.indicatorText.textContent = this.currentCategory;
            }
            if (this.subtitle) {
                this.subtitle.textContent = (this.currentType === 'productos')
                    ? 'Explora nuestra selección de productos'
                    : 'Explora nuestros servicios disponibles';
            }
            setTimeout(() => {
                this.indicator.classList.remove('category-indicator--swap-out');
                this.indicator.classList.add('category-indicator--swap-in');
            }, 10);
        });
        this.applyStyles('#fff', '#F6DA01', '#222');
        this.updateIcon();
        this.syncUI();
        this.showAnimation(true);
    }

    updateIcon() {
        if (!this.icon) return; // Si no existe el icono, salir
        
        this.icon.className = 'category-icon';
        const categoryClass = this.currentCategory.toLowerCase()
            .replace(/\s+/g, '-')
            .normalize("NFD")
            .replace(/[\u0300-\u036f]/g, "");
        this.icon.classList.add(`icon-${categoryClass}`);
    }

    syncUI() {
        const productsBtn = document.getElementById('productos-btn');
        const servicesBtn = document.getElementById('servicios-btn');
        if (productsBtn && servicesBtn) {
            productsBtn.classList.toggle('active', this.currentType === 'productos');
            servicesBtn.classList.toggle('active', this.currentType === 'servicios');
        }
        this.syncHeaderButtons(this.currentHeaderMode);
    }

    syncHeaderButtons(mode) {
        const headerBar = document.getElementById('header-categories-bar');
        if (!headerBar) return;
        const map = {
            categories: 'btn-all-categories',
            subcategories: 'btn-only-subcategories',
            cats_and_subs: 'btn-cats-and-subs',
            products: 'btn-all-products'
        };
        const targetId = map[mode];
        if (!targetId) return;
        try {
            headerBar.querySelectorAll('.header-category-btn').forEach(b => b.classList.remove('active'));
            const target = document.getElementById(targetId);
            if (target) target.classList.add('active');
        } catch {}
    }

    setInitialState() {
        const params = new URLSearchParams(window.location.search);
        const typeParam = params.get('type');
        const categoryParam = params.get('category');
    if (typeParam === 'productos' || typeParam === 'servicios') {
            this.setType(typeParam);
        }
        if (categoryParam) {
            this.setCategory(decodeURIComponent(categoryParam));
        }
        this.updateIndicator();
    }
    showAnimation(fast = false) {
        if (!this.indicator) return; // Si no existe el indicador, salir
        
        this.indicator.style.transform = 'translateY(-2px)';
        setTimeout(() => { this.indicator.style.transform = 'translateY(0)'; }, fast ? 90 : 140);
    }

    applyStyles(background, borderColor, textColor) {
        if (!this.indicator) return; // Si no existe el indicador, salir
        
        this.indicator.style.background = background;
        this.indicator.style.borderColor = borderColor;
        this.indicatorText.style.color = textColor;
    this.indicator.style.transition = 'all 0.3s ease';
        
        const cardBody = this.indicator.querySelector('.card-body');
        if (cardBody) {
            cardBody.style.background = 'transparent';
            cardBody.style.padding = '15px';
            cardBody.style.borderRadius = '8px';
            cardBody.style.display = 'flex';
            cardBody.style.alignItems = 'center';
            cardBody.style.gap = '15px';
        }

        if (this.icon) {
            this.icon.style.width = '0px';
            this.icon.style.height = '10px';
            this.icon.style.display = 'flex';
            this.icon.style.alignItems = 'center';
            this.icon.style.justifyContent = 'center';
        }

        const title = this.indicator.querySelector('.category-title');
        if (title) {
            title.style.fontSize = '14px';
            title.style.color = textColor;
            title.style.opacity = '0.8';
        }

        if (this.subtitle) {
            this.subtitle.style.fontSize = '12px';
            this.subtitle.style.color = textColor;
            this.subtitle.style.opacity = '0.7';
        }

        if (this.indicatorText) {
            this.indicatorText.style.fontSize = '18px';
            this.indicatorText.style.fontWeight = 'bold';
            this.indicatorText.style.margin = '4px 0';
        }
    }
};

} // Fin del bloque de verificación

// Inicializar solo si no existe una instancia previa
document.addEventListener('DOMContentLoaded', () => {
    if (!window.categoryIndicator && window.CategoryIndicator) {
        window.categoryIndicator = new window.CategoryIndicator();
    }
});
