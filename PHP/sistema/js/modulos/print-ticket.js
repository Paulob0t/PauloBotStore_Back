/**
 * 🖨️ Módulo de Impresión de Tickets con QZ Tray
 * Genera tickets ESC/POS para impresoras térmicas
 */

class PrintTicket {
    static printerName = "EPSON TM-T88V"; // Nombre exacto en Windows
    static isConnected = false;

    static getAppBase() {
        if (this._appBase !== undefined) return this._appBase;
        const path = window.location.pathname || '';
        this._appBase = path.replace(/\/[^/]*\.php.*$/i, '').replace(/\/$/, '');
        return this._appBase;
    }

    static certUrl(file) {
        return `${this.getAppBase()}/admin/dist/cert/${file}`;
    }

    /**
     * Inicializar QZ Tray con certificados
     */
    static async init() {
        try {
            const certUrl = this.certUrl('digital-certificate.txt');
            const signUrl = this.certUrl('sign-message.php');

            qz.security.setCertificatePromise(function(resolve, reject) {
                fetch(certUrl, { cache: 'no-store' })
                    .then(res => res.text())
                    .then(resolve)
                    .catch(reject);
            });

            qz.security.setSignaturePromise(function(toSign) {
                return function(resolve, reject) {
                    fetch(signUrl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ request: toSign })
                    })
                    .then(res => res.text())
                    .then(resolve)
                    .catch(reject);
                };
            });

            return true;
        } catch (error) {
            console.error('❌ Error al inicializar QZ Tray:', error);
            return false;
        }
    }

    /**
     * Conectar a QZ Tray
     */
    static async connect() {
        if (qz.websocket.isActive()) {
            this.isConnected = true;
            return true;
        }

        try {
            await qz.websocket.connect();
            this.isConnected = true;
            return true;
        } catch (error) {
            console.error("❌ Error al conectar QZ Tray:", error);
            this.isConnected = false;
            return false;
        }
    }

    /**
     * Imprimir ticket de venta
     * @param {Object} ventaData - Datos de la venta
     */
    static async imprimirTicketVenta(ventaData) {
        try {
            if (typeof qz === 'undefined') {
                throw new Error('QZ Tray no está disponible');
            }

            if (!this.isConnected) {
                await this.init();
                await this.connect();
            }

            let printer;
            try {
                printer = await qz.printers.find(this.printerName);
            } catch (e) {
                console.warn(`Impresora "${this.printerName}" no encontrada, usando predeterminada`);
                printer = await qz.printers.getDefault();
            }

            const config = qz.configs.create(printer);

            // Generar contenido
            const ticketContent = this.generarTicketVenta(ventaData);

            const data = [{
                type: 'raw',
                format: 'plain',
                data: ticketContent
            }];

            await qz.print(config, data);
            return true;

        } catch (error) {
            console.error("❌ Error al imprimir ticket:", error);
            console.warn("⚠️ Impresión fallida, continuando flujo normal");
            return false;
        }
    }

    /**
     * Generar contenido ESC/POS del ticket
     */
    static generarTicketVenta(ventaData) {
        const {
            folio,
            fecha,
            cajero,
            productos = [],
            subtotal = 0,
            descuento = 0,
            iva = 0,
            total = 0,
            metodo_pago = '',
            cambio = null,
            empresa = null
        } = ventaData;

        // Datos de la empresa
        const empresaInfo = empresa || {};
        const nombreEmpresa = empresaInfo.nombre || 'VENDING BOX';
        const direccion = empresaInfo.direccion || '';
        const ciudad = empresaInfo.ciudad || '';
        const estado = empresaInfo.estado || '';
        const telefono = empresaInfo.telefono || '';
        const rfc = empresaInfo.rfc || '';
        const website = empresaInfo.website || 'www.vendigbox.com';

        // ===== ESC/POS =====
        const ESC = "\x1B";
        const GS  = "\x1D";

        const RESET       = ESC + "@";
        const CENTER      = ESC + "a" + "\x01";
        const LEFT        = ESC + "a" + "\x00";
        const BOLD_ON     = ESC + "E" + "\x01";
        const BOLD_OFF    = ESC + "E" + "\x00";
        const DOUBLE_SIZE = GS  + "!" + "\x11";
        const NORMAL_SIZE = GS  + "!" + "\x00";

        // FEED + CUT (lo que evita que se "coma" las últimas líneas)
        const FEED = (n) => ESC + "d" + String.fromCharCode(n); // imprime y avanza n líneas
        const CUT_FULL = GS + "V" + "\x41" + "\x03"; // Full cut con feed previo (3)

        const MARGIN = "  ";

        let ticket = RESET;

        // (Opcional) menos espacio arriba: NO metas \n al inicio
        // ticket += "\n";  // <-- QUITADO para no dejar blanco grande

        // ===== ENCABEZADO =====
        ticket += CENTER;
        ticket += BOLD_ON + DOUBLE_SIZE + nombreEmpresa + "\n" + NORMAL_SIZE + BOLD_OFF;
        ticket += "\n";

        ticket += LEFT;

        if (direccion) ticket += MARGIN + this.truncarTexto(direccion, 28) + "\n";

        if (ciudad && estado) ticket += MARGIN + `${ciudad}, ${estado}\n`;
        else if (ciudad) ticket += MARGIN + `${ciudad}\n`;
        else if (estado) ticket += MARGIN + `${estado}\n`;

        if (telefono) ticket += MARGIN + `Tel: ${telefono}\n`;
        if (rfc) ticket += MARGIN + `RFC: ${rfc}\n`;

        ticket += MARGIN + "Venta de Productos\n";
        ticket += MARGIN + "================================\n";

        // ===== DATOS VENTA =====
        ticket += MARGIN + "Folio:\n";
        ticket += MARGIN + (folio || '') + "\n";
        ticket += MARGIN + "Fecha:\n";
        ticket += MARGIN + (fecha || '') + "\n";
        ticket += MARGIN + "Atendio:\n";
        ticket += MARGIN + (cajero || 'Sistema') + "\n";
        ticket += MARGIN + "================================\n";

        // ===== PRODUCTOS =====
        ticket += MARGIN + BOLD_ON + "PRODUCTOS\n" + BOLD_OFF;

        productos.forEach(prod => {
            const nombre = this.truncarTexto(String(prod.nombre || ''), 28);
            const cantidad = Number(prod.cantidad || 0);
            const precio = Number(prod.precio || 0);
            const desc = Number(prod.descuento || 0);
            const precioFinal = Math.max(0, precio - desc);
            const subtotalProd = (precioFinal * cantidad).toFixed(2);

            ticket += MARGIN + nombre + "\n";
            ticket += MARGIN + `${cantidad} x $${precioFinal.toFixed(2)}\n`;
            ticket += MARGIN + `$${subtotalProd}\n`;

            if (desc > 0) {
                ticket += MARGIN + `(Descuento: -$${desc.toFixed(2)})\n`;
            }
        });

        ticket += MARGIN + "--------------------------------\n";

        // ===== TOTALES =====
        ticket += this.lineaTotalConMargen(MARGIN, "Subtotal:", subtotal);
        if (Number(descuento) > 0) ticket += this.lineaTotalConMargen(MARGIN, "Descuento:", descuento, true);
        ticket += this.lineaTotalConMargen(MARGIN, "IVA (16%):", iva);
        ticket += MARGIN + "================================\n";

        // TOTAL centrado
        ticket += CENTER;
        ticket += BOLD_ON + DOUBLE_SIZE + "TOTAL\n" + `$${Number(total).toFixed(2)}\n` + NORMAL_SIZE + BOLD_OFF;

        ticket += LEFT;
        ticket += MARGIN + "================================\n";

        // ===== PAGO =====
        ticket += MARGIN + "Metodo de Pago:\n";
        ticket += MARGIN + (metodo_pago || '') + "\n";

        if ((metodo_pago || '').toLowerCase() === 'efectivo' && cambio !== null && cambio !== undefined) {
            ticket += this.lineaTotalConMargen(MARGIN, "Cambio:", cambio);
        }

        // poquito espacio (no mucho)
        ticket += "\n";

        // ===== PIE =====
        ticket += CENTER;
        ticket += "================================\n";
        ticket += BOLD_ON + "GRACIAS POR SU COMPRA\n" + BOLD_OFF;
        ticket += "Conserve este ticket\n";
        ticket += "================================\n";

        // ESTO ya no se lo come porque metemos feed antes del corte
        ticket += website + "\n";
        ticket += "Vuelva Pronto!\n";

        // --- AQUÍ CONTROLAS EL ESPACIO FINAL ---
        // Querías “uno o dos” -> dejamos 2 líneas
        ticket += FEED(2);

        // Corte seguro con feed previo
        ticket += CUT_FULL;

        return ticket;
    }

    /**
     * Línea de total alineada + margen
     */
    static lineaTotalConMargen(margin, label, valor, esNegativo = false) {
        const val = Number(valor || 0);
        const valorStr = (esNegativo ? '-' : '') + '$' + val.toFixed(2);

        // 32 caracteres aprox. en 80mm con fuente normal; con margen reduce un poco
        const ancho = 32 - margin.length;
        const textoLabel = label;
        const espacios = Math.max(0, ancho - textoLabel.length - valorStr.length);

        return margin + textoLabel + ' '.repeat(espacios) + valorStr + "\n";
    }

    static truncarTexto(texto, maxLen) {
        texto = String(texto ?? '');
        if (texto.length <= maxLen) return texto;
        return texto.substring(0, maxLen - 3) + '...';
    }

    static async disconnect() {
        if (qz.websocket.isActive()) {
            await qz.websocket.disconnect();
            this.isConnected = false;
            console.log("🔌 Desconectado de QZ Tray");
        }
    }
}

// Exportar para uso global
window.PrintTicket = PrintTicket;
