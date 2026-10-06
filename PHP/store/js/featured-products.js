/**
 * Módulo para gestionar la sección de Productos Destacados
 * Autor: VendingBox Team
 * Fecha: Octubre 2025
 */

const FeaturedProducts = {
    container: null,
    products: [],
    currentSlide: 0,
    autoSlideInterval: null,
    itemsPerSlide: 4,

    /**
     * Inicializa el módulo
     */
    init() {
        this.container = document.getElementById('featured-products-container');
        if (!this.container) {
            console.warn('Contenedor de productos destacados no encontrado');
            return;
        }
        this.updateItemsPerSlide();
        this.loadProducts();
        window.addEventListener('resize', () => this.updateItemsPerSlide());
    },

    updateItemsPerSlide() {
        const width = window.innerWidth;
        if (width <= 480) {
            this.itemsPerSlide = 1;
        } else if (width <= 768) {
            this.itemsPerSlide = 2;
        } else if (width <= 992) {
            this.itemsPerSlide = 3;
        } else {
            this.itemsPerSlide = 4;
        }
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
                this.render();
            }

            // Cargar desde servidor
            const response = await fetch('get_featured_products.php');
            if (!response.ok) throw new Error('Error al cargar productos destacados');
            
            const data = await response.json();
            this.products = data;
            
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
        if (!this.container || !this.products || this.products.length === 0) {
            const section = document.querySelector('.featured-products-section');
            if (section) section.style.display = 'none';
            return;
        }

        // Duplicar productos para loop infinito
        let productsToRender = [...this.products];
        
        // Si hay pocos productos, duplicarlos para llenar el carrusel
        while (productsToRender.length < this.itemsPerSlide * 3) {
            productsToRender = [...productsToRender, ...this.products];
        }
        
        const productsHTML = productsToRender.map(product => this.createProductCard(product)).join('');
        
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

        // Usar solo los productos originales para los dots
        const totalSlides = Math.ceil(this.products.length / this.itemsPerSlide);
        
        // Crear dots basados en productos originales
        dotsContainer.innerHTML = '';
        for (let i = 0; i < totalSlides; i++) {
            const dot = document.createElement('button');
            dot.className = `featured-carousel-dot ${i === 0 ? 'active' : ''}`;
            dot.addEventListener('click', () => this.goToSlide(i));
            dotsContainer.appendChild(dot);
        }

        prevBtn.addEventListener('click', () => this.prevSlide());
        nextBtn.addEventListener('click', () => this.nextSlide());

        // Auto-slide cada 4 segundos
        this.startAutoSlide();

        // Pausar auto-slide al hover
        carousel.addEventListener('mouseenter', () => this.stopAutoSlide());
        carousel.addEventListener('mouseleave', () => this.startAutoSlide());
        
        // Configurar transiciones suaves
        carousel.style.transition = 'transform 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55)';
    },

    goToSlide(index, instant = false) {
        const carousel = document.getElementById('featured-carousel');
        const dots = document.querySelectorAll('.featured-carousel-dot');
        const totalSlides = Math.ceil(this.products.length / this.itemsPerSlide);
        
        if (!carousel) return;
        
        // Normalizar índice para loop infinito
        this.currentSlide = ((index % totalSlides) + totalSlides) % totalSlides;
        
        // Obtener el ancho real de las cards
        const cards = carousel.querySelectorAll('.featured-product-card');
        if (cards.length === 0) return;
        
        const cardStyle = window.getComputedStyle(cards[0]);
        const cardWidth = cards[0].offsetWidth;
        const marginRight = parseInt(cardStyle.marginRight) || 0;
        const marginLeft = parseInt(cardStyle.marginLeft) || 0;
        const totalCardWidth = cardWidth + marginRight + marginLeft;
        
        const offset = -this.currentSlide * totalCardWidth * this.itemsPerSlide;
        
        // Transición instantánea si se requiere
        if (instant) {
            carousel.style.transition = 'none';
            carousel.style.transform = `translateX(${offset}px)`;
            // Forzar reflow
            carousel.offsetHeight;
            carousel.style.transition = 'transform 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55)';
        } else {
            carousel.style.transform = `translateX(${offset}px)`;
        }
        
        // Actualizar dots
        dots.forEach((dot, i) => {
            dot.classList.toggle('active', i === this.currentSlide);
        });
    },

    nextSlide() {
        const totalSlides = Math.ceil(this.products.length / this.itemsPerSlide);
        if (totalSlides <= 1) return;
        // Loop infinito: cuando llegamos al final, volvemos al inicio
        this.currentSlide = (this.currentSlide + 1) % totalSlides;
        this.goToSlide(this.currentSlide);
    },

    prevSlide() {
        const totalSlides = Math.ceil(this.products.length / this.itemsPerSlide);
        if (totalSlides <= 1) return;
        // Loop infinito: cuando llegamos al inicio, volvemos al final
        this.currentSlide = (this.currentSlide - 1 + totalSlides) % totalSlides;
        this.goToSlide(this.currentSlide);
    },

    startAutoSlide() {
        this.stopAutoSlide();
        this.autoSlideInterval = setInterval(() => this.nextSlide(), 4000);
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
    createProductCard(product) {
        const precio = parseFloat(product.precio);
        const descuento = parseFloat(product.descuento) || 0;
        const precioFinal = descuento > 0 ? precio * (1 - descuento / 100) : precio;
        const imagenUrl = product.imagen_principal || './images/placeholder-product.png';
        const ordenDestacado = product.orden_destacado || '';

        return `
            <div class="featured-product-card" data-product-id="${product.id_producto}" data-orden="${ordenDestacado}">
                <div class="featured-product-image">
                    <img src="${imagenUrl}" alt="${this.escapeHtml(product.nombre_producto)}" loading="lazy" decoding="async">
                    ${ordenDestacado ? `<span class="featured-order-badge" title="Posición destacada">#${ordenDestacado}</span>` : ''}
                </div>
                <div class="featured-product-content">
                    <h3 class="featured-product-title">${this.escapeHtml(product.nombre_producto)}</h3>
                    <div class="featured-price-container">
                        <span class="featured-product-price">$${precioFinal.toFixed(2)}</span>
                        ${descuento > 0 ? `
                            <span class="featured-original-price">$${precio.toFixed(2)}</span>
                            <span class="featured-discount-badge">-${descuento}%</span>
                        ` : ''}
                    </div>
                    <button class="featured-add-to-cart" data-product-id="${product.id_producto}">
                        <i class="bi bi-cart-plus"></i>
                        Agregar al Carrito
                    </button>
                </div>
            </div>
        `;
    },

    /**
     * Adjunta event listeners a los botones
     */
    attachEventListeners() {
        const buttons = this.container.querySelectorAll('.featured-add-to-cart');
        buttons.forEach(button => {
            button.addEventListener('click', (e) => {
                e.stopPropagation();
                const productId = button.dataset.productId;
                this.addToCart(productId);
            });
        });

        // Click en la tarjeta para ver detalles (opcional)
        const cards = this.container.querySelectorAll('.featured-product-card');
        cards.forEach(card => {
            card.addEventListener('click', (e) => {
                if (!e.target.closest('.featured-add-to-cart')) {
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

        // Usar la función global del carrito si está disponible
        if (typeof window.Carrito !== 'undefined' && window.Carrito.agregarProducto) {
            window.Carrito.agregarProducto(product.id_producto, product.nombre_producto, product.precio, product.imagen_principal);
        } else {
            console.warn('Sistema de carrito no disponible');
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
            let cart = JSON.parse(localStorage.getItem('vb_cart') || '[]');
            const existingItem = cart.find(item => item.id === product.id_producto);
            
            if (existingItem) {
                existingItem.quantity += 1;
            } else {
                cart.push({
                    id: product.id_producto,
                    nombre: product.nombre_producto,
                    precio: product.precio,
                    imagen: product.imagen_principal,
                    quantity: 1
                });
            }
            
            localStorage.setItem('vb_cart', JSON.stringify(cart));
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
