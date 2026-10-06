/**
 * 🚀 IMAGE OPTIMIZER - Carga super rápida de imágenes
 * - Lazy loading con Intersection Observer
 * - Preload de imágenes críticas
 * - Caché agresivo
 */

class ImageOptimizer {
    constructor() {
        this.observer = null;
        this.imageCache = new Set();
        this.init();
    }

    init() {
        // Crear Intersection Observer para lazy loading
        this.observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    this.loadImage(entry.target);
                    this.observer.unobserve(entry.target);
                }
            });
        }, {
            rootMargin: '50px', // Cargar 50px antes de que sea visible
            threshold: 0.01
        });

        // Preload imágenes del viewport inicial
        this.preloadCriticalImages();
    }

    /**
     * Optimizar imagen para lazy loading
     */
    optimize(img) {
        if (!img || img.dataset.optimized) return;

        const src = img.dataset.src || img.src;
        if (!src) return;

        // Marcar como optimizada
        img.dataset.optimized = 'true';

        // ✅ Si es base64 o data URI, cargar inmediatamente (ya está embebida)
        if (src.startsWith('data:image/')) {
            img.src = src;
            img.classList.add('loaded');
            return;
        }

        // Si la imagen ya está en caché del navegador, cargarla inmediatamente
        if (this.imageCache.has(src)) {
            img.src = src;
            img.classList.add('loaded');
            return;
        }

        // Si está en el viewport inicial, cargar inmediatamente
        const rect = img.getBoundingClientRect();
        const isInViewport = (
            rect.top >= 0 &&
            rect.left >= 0 &&
            rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) + 100 &&
            rect.right <= (window.innerWidth || document.documentElement.clientWidth)
        );

        if (isInViewport) {
            this.loadImage(img);
        } else {
            // Usar lazy loading
            img.dataset.src = src;
            img.src = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 300"%3E%3Crect fill="%23f0f0f0" width="400" height="300"/%3E%3C/svg%3E';
            this.observer.observe(img);
        }
    }

    /**
     * Cargar imagen de forma optimizada
     */
    loadImage(img) {
        const src = img.dataset.src || img.src;
        if (!src || src.startsWith('data:')) return;

        // Placeholder mientras carga
        img.style.backgroundColor = '#f0f0f0';

        // Crear nueva imagen para precarga
        const tempImg = new Image();
        
        tempImg.onload = () => {
            img.src = src;
            img.classList.add('loaded');
            img.style.backgroundColor = '';
            this.imageCache.add(src);
        };

        tempImg.onerror = () => {
            // Fallback a placeholder
            img.src = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 300"%3E%3Crect fill="%23ddd" width="400" height="300"/%3E%3Ctext x="50%25" y="50%25" fill="%23999" text-anchor="middle" dy=".3em" font-family="Arial" font-size="20"%3ESin imagen%3C/text%3E%3C/svg%3E';
            img.classList.add('error');
        };

        tempImg.src = src;
    }

    /**
     * Precargar imágenes críticas (primera pantalla)
     */
    preloadCriticalImages() {
        // Esperar un frame para no bloquear el render inicial
        requestAnimationFrame(() => {
            const criticalImages = document.querySelectorAll('img[data-critical="true"], .hero img, .featured-products img');
            criticalImages.forEach(img => {
                const src = img.dataset.src || img.src;
                if (src && !this.imageCache.has(src)) {
                    const link = document.createElement('link');
                    link.rel = 'preload';
                    link.as = 'image';
                    link.href = src;
                    document.head.appendChild(link);
                    this.imageCache.add(src);
                }
            });
        });
    }

    /**
     * Optimizar todas las imágenes de un contenedor
     */
    optimizeContainer(container) {
        const images = container.querySelectorAll('img:not([data-optimized])');
        images.forEach(img => {
            const src = img.dataset.src || img.src || '';
            if (!src.startsWith('data:image/')) {
                this.optimize(img);
            } else {
                // Si es base64, marcar como loaded
                img.classList.add('loaded');
                img.dataset.optimized = 'true';
            }
        });
    }

    /**
     * Precargar un array de URLs de imágenes
     */
    preloadImages(urls) {
        urls.forEach(url => {
            if (this.imageCache.has(url)) return;
            
            const img = new Image();
            img.onload = () => this.imageCache.add(url);
            img.src = url;
        });
    }

    /**
     * Limpiar observer
     */
    destroy() {
        if (this.observer) {
            this.observer.disconnect();
        }
    }
}

// Instancia global
window.ImageOptimizer = window.ImageOptimizer || new ImageOptimizer();

// Auto-optimizar imágenes cuando se agregan al DOM
if ('MutationObserver' in window) {
    const domObserver = new MutationObserver((mutations) => {
        mutations.forEach(mutation => {
            mutation.addedNodes.forEach(node => {
                if (node.nodeType === 1) { // Element node
                    if (node.tagName === 'IMG') {
                        // Solo optimizar si NO es base64
                        const src = node.dataset.src || node.src || '';
                        if (!src.startsWith('data:image/')) {
                            window.ImageOptimizer.optimize(node);
                        } else {
                            // Si es base64, marcar como loaded inmediatamente
                            node.classList.add('loaded');
                            node.dataset.optimized = 'true';
                        }
                    } else {
                        const images = node.querySelectorAll && node.querySelectorAll('img:not([data-optimized])');
                        if (images) {
                            images.forEach(img => {
                                const src = img.dataset.src || img.src || '';
                                if (!src.startsWith('data:image/')) {
                                    window.ImageOptimizer.optimize(img);
                                } else {
                                    img.classList.add('loaded');
                                    img.dataset.optimized = 'true';
                                }
                            });
                        }
                    }
                }
            });
        });
    });

    // Observar cambios en el DOM
    domObserver.observe(document.body, {
        childList: true,
        subtree: true
    });
}

// Optimizar imágenes existentes cuando el DOM esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.ImageOptimizer.optimizeContainer(document.body);
    });
} else {
    window.ImageOptimizer.optimizeContainer(document.body);
}
