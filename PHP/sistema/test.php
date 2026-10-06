<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Prueba QZ Tray</title>
</head>
<body>

<h2>Prueba de impresión con QZ Tray</h2>
<p>Da clic en el botón de abajo para mandar un ticket de prueba.</p>

<button onclick="imprimirTicket()">Imprimir ticket de prueba</button>

<script src="qz-tray.js"></script>
<script>
// 1) Certificado
qz.security.setCertificatePromise(function(resolve, reject) {
    fetch("/vendigbox.c-onlineweb.net/admin/dist/cert/digital-certificate.txt", { cache: "no-store" })
        .then(res => res.text())
        .then(resolve)
        .catch(reject);
});

// 2) Firma (FORMA CORRECTA PARA QZ: regresa una FUNCIÓN)
qz.security.setSignaturePromise(function(toSign) {
    return function(resolve, reject) {
        fetch("/vendigbox.c-onlineweb.net/admin/dist/cert/sign-message.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ request: toSign })
        })
        .then(res => res.text())
        .then(resolve)
        .catch(reject);
    };
});

// =====================================================
// 3) CONEXIÓN A QZ TRAY
// =====================================================
function conectarQZ() {
    if (qz.websocket.isActive()) return Promise.resolve();
    return qz.websocket.connect().then(() => console.log("Conectado a QZ Tray"));
}

// =====================================================
// 4) IMPRIMIR TICKET ESC/POS
// =====================================================
function imprimirTicket() {
    conectarQZ()
        .then(() => qz.printers.find("EPSON TM-T88V"))
        .then(printer => {
            console.log("Impresora encontrada:", printer);

            // Config (puedes dejarlo así)
            var config = qz.configs.create(printer);

            // Comandos ESC/POS
            var ESC = "\x1B";
            var GS  = "\x1D";

            var INIT        = ESC + "@";                 // Inicializa
            var CENTER      = ESC + "a" + "\x01";
            var LEFT        = ESC + "a" + "\x00";
            var BOLD_ON     = ESC + "E" + "\x01";
            var BOLD_OFF    = ESC + "E" + "\x00";
            var DOUBLE_SIZE = GS  + "!" + "\x11";
            var NORMAL_SIZE = GS  + "!" + "\x00";

            // Cortes (elige 1)
            // Full cut:
            // var CUT = GS + "V" + "\x00";
            // Partial cut:
            var CUT = GS + "V" + "\x01";

            // Feed de líneas (para que no corte el texto)
            var FEED_1 = ESC + "d" + "\x01";
            var FEED_2 = ESC + "d" + "\x02";
            var FEED_3 = ESC + "d" + "\x03";

            var MARGIN = "  ";

            // -----------------------------
            // ARMA TICKET
            // -----------------------------
            // OJO: No metas \n al inicio, eso crea blanco arriba
            var ticket = INIT;

            // ENCABEZADO
            ticket += CENTER;
            ticket += BOLD_ON + DOUBLE_SIZE + "VENDING BOX" + NORMAL_SIZE + BOLD_OFF + "\n";
            // Reduce el espacio arriba: solo 1 salto
            ticket += "\n";

            // CUERPO
            ticket += LEFT;
            ticket += MARGIN + "Venta de Productos\n";
            ticket += MARGIN + "================================\n";

            // DATOS VENTA
            ticket += MARGIN + "Folio:\n";
            ticket += MARGIN + "TEST-" + Date.now() + "\n";
            ticket += MARGIN + "Fecha:\n";
            ticket += MARGIN + new Date().toLocaleString() + "\n";
            ticket += MARGIN + "Cajero:\n";
            ticket += MARGIN + "PRUEBA\n";
            ticket += MARGIN + "================================\n";

            // PRODUCTOS
            ticket += MARGIN + BOLD_ON + "PRODUCTOS" + BOLD_OFF + "\n";
            ticket += MARGIN + "Coca Cola 600ml\n";
            ticket += MARGIN + "2 x $15.00\n";
            ticket += MARGIN + "$30.00\n";

            ticket += MARGIN + "Sabritas Original\n";
            ticket += MARGIN + "1 x $18.00\n";
            ticket += MARGIN + "$18.00\n";

            ticket += MARGIN + "Gansito Marinela\n";
            ticket += MARGIN + "3 x $12.00\n";
            ticket += MARGIN + "$36.00\n";

            ticket += MARGIN + "--------------------------------\n";

            // TOTALES
            ticket += MARGIN + "Subtotal:        $84.00\n";
            ticket += MARGIN + "IVA (16%):       $13.44\n";
            ticket += MARGIN + "================================\n";

            // TOTAL CENTRADO
            ticket += CENTER;
            ticket += BOLD_ON + DOUBLE_SIZE + "TOTAL\n$97.44\n" + NORMAL_SIZE + BOLD_OFF;

            ticket += LEFT;
            ticket += MARGIN + "================================\n";

            // MÉTODO PAGO
            ticket += MARGIN + "Metodo de Pago:\n";
            ticket += MARGIN + "Efectivo\n";

            // PIE DE PÁGINA
            ticket += CENTER;
            ticket += "================================\n";
            ticket += BOLD_ON + "GRACIAS POR SU COMPRA" + BOLD_OFF + "\n";
            ticket += "Conserve este ticket\n";
            ticket += "================================\n";
            ticket += "www.vendigbox.com\n";
            ticket += "Vuelva Pronto!\n";

            // -----------------------------
            // ESPACIO FINAL (aquí lo controlas)
            // -----------------------------
            // “uno o dos”:
            // opción 1: con saltos normales (simple)
            ticket += "\n\n";

            // opción 2 (más PRO): alimentar papel antes del corte
            // si notas que aún sale mocho, cambia FEED_2 por FEED_3
            ticket += FEED_2;

            // Corte
            ticket += CUT;

            var data = [{
                type: "raw",
                format: "plain",
                data: ticket
            }];

            return qz.print(config, data);
        })
        .then(() => console.log("Ticket enviado 😎"))
        .catch(e => {
            console.error("Error al imprimir:", e);
            alert("Error al imprimir: " + e);
        });
}
</script>

</body>
</html>
