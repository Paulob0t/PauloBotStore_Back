document.addEventListener('DOMContentLoaded', function() {
    const productosBtn = document.getElementById('productos-btn');
    const serviciosBtn = document.getElementById('servicios-btn');
    const productsContainer = document.getElementById('products-container');
    const categoriesContainer = document.getElementById('categories-container');
    const subcategoriesContainer = document.getElementById('subcategories-container');
    const servicesContainer = document.getElementById('services-container');
    const switchSlider = document.querySelector('.switch-slider');
    const categoryIndicator = window.categoryIndicator;
    const featuredSection = document.querySelector('.featured-products-section');

    if (!productosBtn || !serviciosBtn || !switchSlider) {
        return;
    }
    async function switchToProducts() {
        
        // Ocultar botón de regresar cuando volvemos a productos
        const backButton = document.getElementById('back-button');
        if (backButton) {
            backButton.classList.add('hidden');
        }
        
        // Mostrar productos destacados
        if (featuredSection) {
            featuredSection.style.display = 'block';
        }
        
        // OCULTAR header de servicios
        const servicesHeader = document.getElementById('services-header');
        if (servicesHeader) {
            servicesHeader.style.display = 'none';
            servicesHeader.classList.add('hidden');
        }
        
        if (servicesContainer) {
            servicesContainer.style.display = 'none';
            servicesContainer.classList.add('hidden');
        }
        if (productsContainer) {
            productsContainer.innerHTML = '';
            productsContainer.style.display = 'none';
            if (productsContainer.dataset && productsContainer.dataset.layout === 'services') {
                productsContainer.style.removeProperty('grid-template-columns');
                productsContainer.style.removeProperty('gap');
                delete productsContainer.dataset.layout;
            }
        }
        if (subcategoriesContainer) {
            subcategoriesContainer.style.display = 'none';
            subcategoriesContainer.classList.add('hidden');
        }
        if (categoriesContainer) {
            categoriesContainer.style.display = 'block';
            categoriesContainer.classList.remove('hidden');
        }
        const categoriesSection = document.querySelector('.categories-visual-section');
        if (categoriesSection) categoriesSection.style.display = 'block';

        switchUI('productos');

        if (window.currentView !== undefined) {
            window.currentView = '';
        }
        if (typeof window.loadCategories === 'function') {
            await window.loadCategories();
        }

        const headerAllBtn = document.querySelector('.header-category-btn[data-category-id="0"]');
        if (headerAllBtn) {
            const headerBtns = document.querySelectorAll('.header-category-btn');
            headerBtns.forEach(btn => btn.classList.remove('active'));
            headerAllBtn.classList.add('active');
        }
    }

    function switchUI(view) {
        const headerCategories = document.querySelector('.header-categories-center');
        if (view === 'productos') {
            productosBtn.classList.add('active');
            serviciosBtn.classList.remove('active');
            switchSlider.style.left = '8px';
            productosBtn.style.color = '#222';
            serviciosBtn.style.color = '#666';
            if (headerCategories) headerCategories.style.display = 'flex';
            if (categoryIndicator) {
                categoryIndicator.setType('productos');
                categoryIndicator.setCategory('Categorías');
            }
            const headerBar = document.getElementById('header-categories-bar');
            if (headerBar) {
                const anyActive = headerBar.querySelector('.header-category-btn.active');
                if (!anyActive) {
                    const allBtn = document.getElementById('btn-all-categories');
                    if (allBtn) allBtn.classList.add('active');
                }
            }
        } else {
            productosBtn.classList.remove('active');
            serviciosBtn.classList.add('active');
            switchSlider.style.left = '50%';
            productosBtn.style.color = '#666';
            serviciosBtn.style.color = '#222';
            if (headerCategories) headerCategories.style.display = 'none';
            if (categoryIndicator) {
                categoryIndicator.setType('servicios');
                categoryIndicator.setCategory('Servicios');
            }
        }
        document.dispatchEvent(new CustomEvent('viewChange', { detail: { view } }));
    }

    async function switchToServices() {
        // OCULTAR productos destacados cuando estamos en servicios
        if (featuredSection) {
            featuredSection.style.display = 'none';
        }
        
        // Ocultar productos y categorías
        if (productsContainer) {
            productsContainer.innerHTML = '';
            productsContainer.style.display = 'none';
        }
        if (subcategoriesContainer) {
            subcategoriesContainer.style.display = 'none';
            subcategoriesContainer.classList.add('hidden');
        }
        if (categoriesContainer) {
            categoriesContainer.style.display = 'block';
            categoriesContainer.classList.remove('hidden');
        }
        const categoriesSection = document.querySelector('.categories-visual-section');
        if (categoriesSection) categoriesSection.style.display = 'block';
        if (typeof window.ensureCategoriesVisible === 'function') {
            window.ensureCategoriesVisible();
        }
        
        // Ocultar headers de productos y MOSTRAR header de servicios (categorías siguen visibles)
        const categoriesHeader = document.getElementById('categories-header');
        const productsHeader = document.getElementById('products-header');
        const subcategoriesHeader = document.getElementById('subcategories-header');
        const catsSubsHeader = document.getElementById('cats-subs-header');
        const servicesHeader = document.getElementById('services-header');
        
        if (categoriesHeader) {
            categoriesHeader.style.display = 'block';
            categoriesHeader.classList.remove('hidden');
        }
        if (productsHeader) productsHeader.style.display = 'none';
        if (subcategoriesHeader) subcategoriesHeader.style.display = 'none';
        if (catsSubsHeader) catsSubsHeader.style.display = 'none';
        
        // MOSTRAR header de servicios
        if (servicesHeader) {
            servicesHeader.style.display = 'block';
            servicesHeader.classList.remove('hidden');
        }
        
        // Mostrar servicios
        if (servicesContainer) {
            servicesContainer.style.display = 'block';
            servicesContainer.classList.remove('hidden');
        }
        
        switchUI('servicios');
        
        // Disparar evento para que services-menu.js renderice los botones
        document.dispatchEvent(new CustomEvent('servicesRequested'));
    }

    productosBtn.addEventListener('click', switchToProducts);
    serviciosBtn.addEventListener('click', switchToServices);
});