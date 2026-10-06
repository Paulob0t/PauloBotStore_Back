export async function loadProducts() {
    try {
        const response = await fetch('get_products.php');
        const productos = await response.json();
        displayProducts(productos);
    } catch (error) {
        console.error('Error al cargar productos:', error);
    }
}

export function displayProducts(productos) {
    const container = document.getElementById('products-container');
    if (!container) return;

    container.innerHTML = '';
    productos.forEach((producto) => {
        const col = document.createElement('div');
        col.className = 'col-lg-4 col-md-6 col-12';
        col.style.padding = '15px';

        const card = document.createElement('div');
        card.className = 'single-product d-flex flex-column h-100';

        const price = producto.descuento
            ? (producto.precio - (producto.precio * producto.descuento / 100)).toFixed(2)
            : producto.precio;

        card.innerHTML = `
            <div class="product-img">
                <img src="${producto.imagen_principal}" alt="${producto.nombre_producto}">
            </div>
            <div class="product-content-row d-flex flex-column flex-grow-1 justify-content-between">
                <div class="product-info">
                    <h3>${producto.nombre_producto}</h3>
                    <div class="product-price">
                        ${producto.descuento ? 
                            `<span class="old-price" style="text-decoration: line-through; color: #999; margin-right: 10px;">
                                $${producto.precio}
                            </span>` 
                            : ''
                        }
                        $${price}
                    </div>
                </div>
                <div class="text-center pt-3">
                    <button class="btn add-to-cart-btn" 
                        data-product-id="${producto.id_producto}"
                        style="background: #F7941D; color: white; border: none; padding: 10px 20px; border-radius: 5px; transition: all 0.3s ease;"
                        onmouseover="this.style.background='#e58100'" 
                        onmouseout="this.style.background='#F7941D'">
                        <i class="fa fa-shopping-cart"></i> Agregar al carrito
                    </button>
                </div>
            </div>
        `;

        col.appendChild(card);
        container.appendChild(col);
    });
}

export function addToCart(productId) {
    try {
        const product = products.find(p => p.id_producto === productId);
        if (!product) {
            throw new Error('Producto no encontrado');
        }

        let cart = JSON.parse(localStorage.getItem('cart')) || [];
        const existingItem = cart.find(item => item.id_producto === productId);
        if (existingItem) {
            existingItem.quantity += 1;
        } else {
            cart.push({
                ...product,
                quantity: 1
            });
        }

        localStorage.setItem('cart', JSON.stringify(cart));

        if (typeof Cart !== 'undefined' && typeof Cart.updateCartUI === 'function') {
            Cart.updateCartUI();
        }

        Swal.fire({
            title: '¡Agregado!',
            text: 'Producto agregado al carrito',
            icon: 'success',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000
        });

    } catch (error) {
        console.error('Error al agregar al carrito:', error);
        Swal.fire({
            title: 'Error',
            text: 'No se pudo agregar el producto al carrito',
            icon: 'error',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000
        });
    }
}
