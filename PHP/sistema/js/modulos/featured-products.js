/**
 * Módulo para gestionar la sección de Productos Destacados
 * Autor: VendingBox Team
 * Fecha: Octubre 2025
 */

const FeaturedProducts = {
    container: null,
    products: [],
    displayProducts: [],
    currentSlide: 0,
    autoSlideInterval: null,
    itemsPerSlide: 4,
    totalSlides: 0,
    boundHandleResize: null,

    /**
     * Inicializa el módulo
     */
    init() {
        this.container = document.getElementById('featured-products-container');
        this.updateItemsPerSlide();
        this.loadProducts();

        this.boundHandleResize = this.onResize.bind(this);
        window.addEventListener('resize', this.boundHandleResize);
    },

    updateItemsPerSlide(totalProducts = null) {
        const width = window.innerWidth;
        let baseItems = 3; // Cambiado de 4 a 3 para pantallas grandes

        if (width <= 480) {
            baseItems = 1;
        } else if (width <= 768) {
            baseItems = 2;
        } else if (width <= 1200) {
            baseItems = 3;
        }

        if (typeof totalProducts === 'number' && totalProducts > 0) {
            baseItems = Math.min(baseItems, totalProducts);
        }

        this.itemsPerSlide = Math.max(1, baseItems);
    },

    /**
     * Carga los productos destacados desde el servidor
     */
    async loadProducts() {
        try {
            this.showLoading();

            // Intentar cargar desde caché primero
            const cached = this.getFromCache();
            if (cached && cached.length > 0) {
                this.products = cached;
                window.featuredProducts = cached;
                this.render();
            }

            // Cargar desde servidor
            const response = await fetch('get_featured_products.php');
            if (!response.ok) throw new Error('Error al cargar productos destacados');

            const data = await response.json();
            this.products = data;
            window.featuredProducts = data;

            // Guardar en caché
            this.saveToCache(data);

            this.render();
        } catch (error) {
            console.error('Error al cargar productos destacados:', error);
            this.showError();
        }
    },

    /**
     * Muestra el estado de carga
     */
    showLoading() {
        if (!this.container) return;
        this.container.innerHTML = `
            <div class="featured-loading">
                <i class="fa fa-spinner fa-spin"></i>
                <p>Cargando productos destacados...</p>
            </div>
        `;
    },

    /**
     * Muestra mensaje de error
     */
    showError() {
        if (!this.container) return;
        this.container.innerHTML = `
            <div class="featured-loading">
                <i class="fa fa-exclamation-circle"></i>
                <p>No se pudieron cargar los productos destacados</p>
            </div>
        `;
    },

    /**
     * Renderiza los productos destacados
     */
    render() {
        this.stopAutoSlide();

        if (!this.container || !this.products || this.products.length === 0) {
            const section = document.querySelector('.featured-products-section');
            if (section) section.style.display = 'none';
            return;
        }

        this.updateItemsPerSlide(this.products.length);
        this.displayProducts = this.prepareDisplayProducts();

        if (!this.displayProducts || this.displayProducts.length === 0) {
            const section = document.querySelector('.featured-products-section');
            if (section) section.style.display = 'none';
            return;
        }

        const section = document.querySelector('.featured-products-section');
        if (section) section.style.display = 'block';

        this.currentSlide = 0;

        const productsHTML = this.displayProducts.map((product, index) => this.createProductCard(product, index)).join('');
        
        this.container.innerHTML = `
            <div class="featured-products-carousel-wrapper">
                <button class="featured-carousel-btn prev" id="featured-prev">
                    <i class="fa fa-chevron-left"></i>
                </button>
                <div class="featured-products-grid">
                    <div class="featured-products-carousel" id="featured-carousel">
                        ${productsHTML}
                    </div>
                </div>
                <button class="featured-carousel-btn next" id="featured-next">
                    <i class="fa fa-chevron-right"></i>
                </button>
            </div>
            <div class="featured-carousel-dots" id="featured-dots"></div>
        `;

        this.attachEventListeners();
        this.initCarousel();
    },

    initCarousel() {
        const carousel = document.getElementById('featured-carousel');
        const prevBtn = document.getElementById('featured-prev');
        const nextBtn = document.getElementById('featured-next');
        const dotsContainer = document.getElementById('featured-dots');
        
        if (!carousel) return;

        // Calcular el total de slides basado en todos los productos disponibles
        const totalProducts = this.displayProducts.length;
        const totalSlides = Math.ceil(totalProducts / this.itemsPerSlide);
        this.totalSlides = totalSlides;
                
        // Crear dots
        dotsContainer.innerHTML = '';
        for (let i = 0; i < totalSlides; i++) {
            const dot = document.createElement('button');
            dot.className = `featured-carousel-dot ${i === 0 ? 'active' : ''}`;
            dot.addEventListener('click', () => this.goToSlide(i));
            dotsContainer.appendChild(dot);
        }

        dotsContainer.style.display = totalSlides > 1 ? 'flex' : 'none';

        // Remover listeners anteriores si existen
        prevBtn.replaceWith(prevBtn.cloneNode(true));
        nextBtn.replaceWith(nextBtn.cloneNode(true));
        
        // Obtener referencias nuevas después de clonar
        const newPrevBtn = document.getElementById('featured-prev');
        const newNextBtn = document.getElementById('featured-next');
        
        newPrevBtn.addEventListener('click', () => this.prevSlide());
        newNextBtn.addEventListener('click', () => this.nextSlide());

        newPrevBtn.style.display = totalSlides > 1 ? 'flex' : 'none';
        newNextBtn.style.display = totalSlides > 1 ? 'flex' : 'none';

        // Auto-slide cada 5 segundos
        this.startAutoSlide();

        // Pausar auto-slide al hover
        carousel.addEventListener('mouseenter', () => this.stopAutoSlide());
        carousel.addEventListener('mouseleave', () => this.startAutoSlide());

        requestAnimationFrame(() => this.goToSlide(0));
    },

    goToSlide(index) {
        const carousel = document.getElementById('featured-carousel');
        const dots = document.querySelectorAll('.featured-carousel-dot');
        const totalSlides = this.totalSlides;
        
        if (!carousel || totalSlides === 0) return;
        
        // Hacer el carrusel infinito
        if (index >= totalSlides) {
            this.currentSlide = 0;
        } else if (index < 0) {
            this.currentSlide = totalSlides - 1;
        } else {
            this.currentSlide = index;
        }

        const cards = carousel.querySelectorAll('.featured-product-card');
        if (cards.length === 0) return;
                
        // Usar el ancho REAL de la primera tarjeta
        const firstCard = cards[0];
        const cardWidth = firstCard.offsetWidth;
        const gap = 20;
        
        // Calcular el desplazamiento: simplemente mover por tarjetas completas
        const offset = -(this.currentSlide * this.itemsPerSlide * (cardWidth + gap));
        
        carousel.style.transition = 'transform 0.6s ease-in-out';
        carousel.style.transform = `translateX(${offset}px)`;

        dots.forEach((dot, i) => {
            dot.classList.toggle('active', i === this.currentSlide);
        });
    },

    nextSlide() {
        if (this.totalSlides <= 1) return;
        // Loop infinito: cuando llegamos al final, volvemos al inicio automáticamente
        const nextIndex = (this.currentSlide + 1) % this.totalSlides;
        this.goToSlide(nextIndex);
    },

    prevSlide() {
        if (this.totalSlides <= 1) return;
        // Loop infinito: cuando llegamos al inicio, volvemos al final automáticamente
        const prevIndex = (this.currentSlide - 1 + this.totalSlides) % this.totalSlides;
        this.goToSlide(prevIndex);
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

    /**
     * Crea la tarjeta HTML de un producto
     */
    createProductCard(product, index) {
        const precio = parseFloat(product.precio) || 0;
        const descuento = parseFloat(product.descuento) || 0;
        const info = (window.VBPrecio && window.VBPrecio.calcular)
            ? window.VBPrecio.calcular(precio, descuento)
            : { original: precio, descuento, final: Math.max(0, precio - descuento), tieneDescuento: descuento > 0 };
        const imagenUrl = product.imagen_principal || './images/placeholder-product.png';
        const ordenDestacado = product.orden_destacado || '';
        const cloneAttributes = product.__duplicate ? ` data-clone="1" data-clone-seq="${product.__cloneSeq || index}"` : '';

        return `
            <div class="featured-product-card" data-product-id="${product.id_producto}" data-display-index="${index}" data-orden="${ordenDestacado}"${cloneAttributes}>
                <div class="featured-product-image">
                    <img src="${imagenUrl}" alt="${this.escapeHtml(product.nombre_producto)}" loading="lazy" decoding="async">
                    ${ordenDestacado ? `<span class="featured-order-badge" title="Posición destacada">#${ordenDestacado}</span>` : ''}
                </div>
                <div class="featured-product-content">
                    <h3 class="featured-product-title">${this.escapeHtml(product.nombre_producto)}</h3>
                    <div class="featured-price-container">
                        <span class="featured-product-price">$${info.final.toFixed(2)}</span>
                        ${info.tieneDescuento ? `
                            <span class="featured-original-price">$${info.original.toFixed(2)}</span>
                            <span class="featured-discount-badge">-$${info.descuento.toFixed(2)}</span>
                        ` : ''}
                    </div>
                    <button class="btn add-to-cart-btn"
                        data-product-id="${product.id_producto}"
                        style="background: #F7941D; color: white; border: none; padding: 10px 20px; border-radius: 5px; transition: all 0.3s ease;"
                        onmouseover="this.style.background='#e58100'" 
                        onmouseout="this.style.background='#F7941D'">
                        <i class="fa fa-shopping-cart"></i> Agregar al carrito
                    </button>
                </div>
            </div>
        `;
    },

    /**
     * Adjunta event listeners a los botones
     */
    attachEventListeners() {
        // Los botones .add-to-cart-btn son manejados por carrito.js vía delegación global

        // Click en la tarjeta para ver detalles (excluyendo el botón)
        const cards = this.container.querySelectorAll('.featured-product-card');
        cards.forEach(card => {
            card.addEventListener('click', (e) => {
                if (!e.target.closest('.add-to-cart-btn')) {
                    const productId = card.dataset.productId;
                    // Aquí puedes agregar lógica para mostrar detalles del producto
                }
            });
        });
    },

    /**
     * Agrega un producto al carrito
     */
    addToCart(productId) {
        const product = this.products.find(p => p.id_producto == productId);
        if (!product) {
            console.error('Producto no encontrado');
            return;
        }

        // Usar window.Cart que es el sistema correcto
        if (typeof window.Cart !== 'undefined' && window.Cart.addItem) {
            window.Cart.addItem(product);
        } else {
            // Fallback: guardar en localStorage directamente
            this.addToCartFallback(product);
        }

        // Feedback visual con SweetAlert
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: '¡Agregado!',
                text: `${product.nombre_producto} se agregó al carrito`,
                icon: 'success',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 2000,
                timerProgressBar: true
            });
        }
    },

    /**
     * Fallback para agregar al carrito si el módulo principal no está disponible
     */
    addToCartFallback(product) {
        try {
            let cart = JSON.parse(localStorage.getItem('cart') || '[]');
            const existingItem = cart.find(item =>
                String(item.id_producto) === String(product.id_producto)
            );

            if (existingItem) {
                existingItem.quantity += 1;
            } else {
                cart.push({
                    id_producto: product.id_producto,
                    nombre_producto: product.nombre_producto,
                    precio: product.precio,
                    descuento: product.descuento || 0,
                    imagen_principal: product.imagen_principal || './images/placeholder-product.png',
                    quantity: 1
                });
            }

            localStorage.setItem('cart', JSON.stringify(cart));
        } catch (error) {
            console.error('Error al guardar en carrito:', error);
        }
    },

    /**
     * Obtiene productos desde caché
     */
    getFromCache() {
        try {
            const cached = localStorage.getItem('vb_featured_products');
            if (!cached) return null;
            
            const data = JSON.parse(cached);
            const TTL = 10 * 60 * 1000; // 10 minutos
            
            if (Date.now() - data.timestamp < TTL) {
                return data.products;
            }
            
            return null;
        } catch (error) {
            console.error('Error al leer caché:', error);
            return null;
        }
    },

    /**
     * Guarda productos en caché
     */
    saveToCache(products) {
        try {
            const data = {
                products: products,
                timestamp: Date.now()
            };
            localStorage.setItem('vb_featured_products', JSON.stringify(data));
        } catch (error) {
            console.error('Error al guardar en caché:', error);
        }
    },

    /**
     * Escapa HTML para prevenir XSS
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    },

    prepareDisplayProducts() {
        if (!Array.isArray(this.products) || this.products.length === 0) {
            return [];
        }

        // Simplemente devolver todos los productos sin duplicar ni limitar
        // El carrusel infinito lo manejamos con el loop en nextSlide/prevSlide
        return [...this.products];
    },

    onResize() {
        if (!this.container || !this.products || this.products.length === 0) {
            return;
        }

        const previousItemsPerSlide = this.itemsPerSlide;
        this.updateItemsPerSlide(this.products.length);

        if (previousItemsPerSlide !== this.itemsPerSlide) {
            this.render();
        } else {
            // Asegurar que el offset se recalibre si el ancho cambia pero el número de ítems por slide no
            this.goToSlide(this.currentSlide);
        }
    }
};

// Inicializar cuando el DOM esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => FeaturedProducts.init());
} else {
    FeaturedProducts.init();
}

// Exportar para uso global
window.FeaturedProducts = FeaturedProducts;
