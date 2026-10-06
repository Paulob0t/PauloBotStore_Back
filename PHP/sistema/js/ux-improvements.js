/**
 * Mejoras de UX JavaScript
 * Funcionalidades para mejorar elementos existentes
 */

(function() {
    'use strict';
    
    // ===================================
    // ANIMACIÓN "VOLANDO AL CARRITO"
    // ===================================
    function flyToCart(element) {
        // Obtener posición del producto
        const productRect = element.getBoundingClientRect();
        
        // Obtener posición del carrito
        const cart = document.querySelector('.shopping .single-icon') || 
                     document.querySelector('#cart-count-inner')?.parentElement?.parentElement;
        
        if (!cart) return;
        
        const cartRect = cart.getBoundingClientRect();
        
        // Crear elemento volador (clon de la imagen del producto)
        const productCard = element.closest('.single-product, .product-item');
        const productImg = productCard?.querySelector('img');
        
        if (!productImg) return;
        
        const flyingImg = productImg.cloneNode();
        flyingImg.classList.add('flying-to-cart');
        flyingImg.style.position = 'fixed';
        flyingImg.style.left = productRect.left + 'px';
        flyingImg.style.top = productRect.top + 'px';
        flyingImg.style.width = productRect.width + 'px';
        flyingImg.style.height = 'auto';
        flyingImg.style.zIndex = '99999';
        flyingImg.style.pointerEvents = 'none';
        
        // Calcular distancia al carrito
        const deltaX = cartRect.left - productRect.left;
        const deltaY = cartRect.top - productRect.top;
        
        flyingImg.style.setProperty('--x', deltaX + 'px');
        flyingImg.style.setProperty('--y', deltaY + 'px');
        
        document.body.appendChild(flyingImg);
        
        // Animar contador del carrito
        const cartBadge = document.querySelector('.total-count');
        if (cartBadge) {
            cartBadge.style.animation = 'none';
            setTimeout(() => {
                cartBadge.style.animation = 'heartbeat 0.6s ease';
            }, 10);
        }
        
        // Remover después de la animación
        setTimeout(() => {
            flyingImg.remove();
        }, 800);
    }
    
    // ===================================
    // INTERCEPTAR AGREGAR AL CARRITO
    // ===================================
    function initCartAnimations() {
        document.addEventListener('click', function(e) {
            const addButton = e.target.closest('.add-to-cart, .btn-add-cart, button[onclick*="addToCart"]');
            
            if (addButton) {
                flyToCart(addButton);
                
                // Efecto de éxito
                addButton.style.background = '#28a745';
                const originalText = addButton.innerHTML;
                addButton.innerHTML = '<i class="fa fa-check"></i> ¡Agregado!';
                
                setTimeout(() => {
                    addButton.style.background = '';
                    addButton.innerHTML = originalText;
                }, 2000);
            }
        });
    }
    
    // ===================================
    // LAZY LOADING DE IMÁGENES
    // ===================================
    function initLazyLoading() {
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        if (img.dataset.src) {
                            img.src = img.dataset.src;
                            img.removeAttribute('data-src');
                            imageObserver.unobserve(img);
                            
                            // Añadir efecto de fade in
                            img.style.opacity = '0';
                            img.onload = () => {
                                img.style.transition = 'opacity 0.3s ease';
                                img.style.opacity = '1';
                            };
                        }
                    }
                });
            }, {
                rootMargin: '50px'
            });
            
            document.querySelectorAll('img[data-src]').forEach(img => {
                imageObserver.observe(img);
            });
        }
    }
    
    // ===================================
    // SMOOTH SCROLL
    // ===================================
    function initSmoothScroll() {
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                const href = this.getAttribute('href');
                if (href === '#' || href === '#!') return;
                
                const target = document.querySelector(href);
                if (target) {
                    e.preventDefault();
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    }
    
    // ===================================
    // ANIMACIÓN DE CATEGORÍAS AL HOVER
    // ===================================
    function initCategoryHover() {
        const categories = document.querySelectorAll('.single-cate, .category-card');
        
        categories.forEach(category => {
            category.addEventListener('mouseenter', function() {
                this.style.transition = 'all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55)';
            });
        });
    }
    
    // ===================================
    // FEEDBACK VISUAL EN INPUTS
    // ===================================
    function initInputFeedback() {
        const inputs = document.querySelectorAll('input, textarea, select');
        
        inputs.forEach(input => {
            // Focus
            input.addEventListener('focus', function() {
                this.style.borderColor = '#667eea';
                this.style.boxShadow = '0 0 0 3px rgba(102, 126, 234, 0.1)';
            });
            
            // Blur
            input.addEventListener('blur', function() {
                this.style.borderColor = '';
                this.style.boxShadow = '';
            });
            
            // Validación en tiempo real
            input.addEventListener('input', function() {
                if (this.validity.valid) {
                    this.style.borderColor = '#28a745';
                } else if (this.value) {
                    this.style.borderColor = '#dc3545';
                }
            });
        });
    }
    
    // ===================================
    // PRELOADER PARA PRODUCTOS
    // ===================================
    function showProductSkeleton(container, count = 6) {
        const skeleton = `
            <div class="skeleton-product" style="height: 350px; margin: 10px; border-radius: 15px;"></div>
        `;
        container.innerHTML = skeleton.repeat(count);
    }
    
    // ===================================
    // OBSERVADOR DE PRODUCTOS VISIBLES
    // ===================================
    function initProductAnimation() {
        if (!('IntersectionObserver' in window)) return;
        
        const productObserver = new IntersectionObserver((entries) => {
            entries.forEach((entry, index) => {
                if (entry.isIntersecting) {
                    setTimeout(() => {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }, index * 50);
                    productObserver.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: '50px'
        });
        
        // Observar cuando se agreguen nuevos productos
        const observer = new MutationObserver(() => {
            const products = document.querySelectorAll('.single-product, .product-item');
            products.forEach(product => {
                if (!product.hasAttribute('data-animated')) {
                    product.setAttribute('data-animated', 'true');
                    productObserver.observe(product);
                }
            });
        });
        
        // Observar contenedores de productos
        const containers = [
            document.getElementById('products-container'),
            document.getElementById('featured-products-container'),
            document.getElementById('categories-container')
        ].filter(Boolean);
        
        containers.forEach(container => {
            observer.observe(container, { childList: true, subtree: true });
        });
    }
    
    // ===================================
    // MEJORAR DROPDOWN DEL CARRITO
    // ===================================
    function enhanceCartDropdown() {
        const cartWrapper = document.querySelector('.sinlge-bar.shopping');
        const cartDropdown = document.querySelector('.shopping-item');
        
        if (!cartWrapper || !cartDropdown) return;
        
        let hideTimer = null;
        let isHoveringCart = false;
        let isHoveringDropdown = false;
        
        // Función para mostrar el carrito
        function showCart() {
            clearTimeout(hideTimer);
            cartDropdown.style.opacity = '1';
            cartDropdown.style.visibility = 'visible';
            cartDropdown.style.transform = 'translateY(0)';
            cartDropdown.style.pointerEvents = 'auto';
            cartDropdown.style.transition = 'opacity 0.3s ease, visibility 0s ease, transform 0.3s ease';
            cartDropdown.style.transitionDelay = '0s';
        }
        
        // Función para ocultar el carrito con delay
        function hideCart() {
            hideTimer = setTimeout(() => {
                if (!isHoveringCart && !isHoveringDropdown) {
                    cartDropdown.style.opacity = '0';
                    cartDropdown.style.visibility = 'hidden';
                    cartDropdown.style.transform = 'translateY(-15px)';
                    cartDropdown.style.pointerEvents = 'none';
                    cartDropdown.style.transition = 'opacity 0.3s ease, visibility 0s ease 0.8s, transform 0.3s ease';
                }
            }, 300); // Delay adicional de 300ms antes de iniciar el cierre
        }
        
        // Eventos para el ícono del carrito
        cartWrapper.addEventListener('mouseenter', () => {
            isHoveringCart = true;
            showCart();
        });
        
        cartWrapper.addEventListener('mouseleave', () => {
            isHoveringCart = false;
            hideCart();
        });
        
        // Eventos para el dropdown
        cartDropdown.addEventListener('mouseenter', () => {
            isHoveringDropdown = true;
            showCart();
        });
        
        cartDropdown.addEventListener('mouseleave', () => {
            isHoveringDropdown = false;
            hideCart();
        });
        
        // Prevenir cierre al interactuar con elementos dentro del dropdown
        cartDropdown.addEventListener('click', (e) => {
            // Si se hace clic en un elemento del carrito (excepto eliminar), mantener abierto
            if (!e.target.closest('.remove-item')) {
                clearTimeout(hideTimer);
            }
        });
    }
    
    // ===================================
    // TOOLTIP MEJORADO
    // ===================================
    function initTooltips() {
        const elements = document.querySelectorAll('[title]');
        
        elements.forEach(el => {
            const title = el.getAttribute('title');
            if (!title) return;
            
            // Remover title para evitar tooltip nativo
            el.removeAttribute('title');
            el.setAttribute('data-tooltip', title);
            
            el.addEventListener('mouseenter', function(e) {
                const tooltip = document.createElement('div');
                tooltip.className = 'custom-tooltip';
                tooltip.textContent = this.getAttribute('data-tooltip');
                tooltip.style.cssText = `
                    position: fixed;
                    background: rgba(0, 0, 0, 0.9);
                    color: white;
                    padding: 8px 12px;
                    border-radius: 8px;
                    font-size: 12px;
                    z-index: 10000;
                    pointer-events: none;
                    white-space: nowrap;
                    animation: tooltipFade 0.2s ease;
                `;
                
                document.body.appendChild(tooltip);
                
                const rect = this.getBoundingClientRect();
                tooltip.style.left = (rect.left + rect.width / 2 - tooltip.offsetWidth / 2) + 'px';
                tooltip.style.top = (rect.top - tooltip.offsetHeight - 10) + 'px';
                
                this._tooltip = tooltip;
            });
            
            el.addEventListener('mouseleave', function() {
                if (this._tooltip) {
                    this._tooltip.remove();
                    this._tooltip = null;
                }
            });
        });
    }
    
    // ===================================
    // INICIALIZAR TODO
    // ===================================
    function init() {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
            return;
        }
        
        initCartAnimations();
        initLazyLoading();
        initSmoothScroll();
        initCategoryHover();
        initInputFeedback();
        initProductAnimation();
        enhanceCartDropdown();
        initTooltips();
        
    }
    
    init();
    
    // Exportar funciones útiles
    window.UXImprovements = {
        flyToCart,
        showProductSkeleton
    };
    
})();
