/**
 * Precios VendingBox — descuento en PESOS (igual que carrito y procesar_venta.php)
 */
(function (global) {
    function calcular(precio, descuento) {
        const original = parseFloat(precio) || 0;
        const desc = Math.max(0, Math.min(original, parseFloat(descuento) || 0));
        return {
            original,
            descuento: desc,
            final: Math.max(0, original - desc),
            tieneDescuento: desc > 0
        };
    }

    function aplicarEnContenedor(root, precio, descuento) {
        if (!root) return calcular(precio, descuento);
        const info = calcular(precio, descuento);
        const priceEl = root.querySelector('.product-price, .featured-product-price');
        const origEl = root.querySelector('.product-original-price, .featured-original-price');
        const badgeEl = root.querySelector('.product-discount, .featured-discount-badge');

        if (priceEl) {
            priceEl.textContent = `$${info.final.toFixed(2)}`;
        }
        if (origEl) {
            if (info.tieneDescuento) {
                origEl.textContent = `$${info.original.toFixed(2)}`;
                origEl.style.display = '';
            } else {
                origEl.textContent = '';
                origEl.style.display = 'none';
            }
        }
        if (badgeEl) {
            if (info.tieneDescuento) {
                badgeEl.textContent = `-$${info.descuento.toFixed(2)}`;
                badgeEl.style.display = '';
            } else {
                badgeEl.textContent = '';
                badgeEl.style.display = 'none';
            }
        }
        return info;
    }

    global.VBPrecio = { calcular, aplicarEnContenedor };
})(typeof window !== 'undefined' ? window : this);
