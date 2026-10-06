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

    return qz.websocket.connect().then(() => {
        console.log("Conectado a QZ Tray");
    });
}

// =====================================================
// 4) IMPRIMIR TICKET ESC/POS
// =====================================================
function imprimirTicket() {
    conectarQZ()
        .then(() => qz.printers.find("EPSON TM-T88V"))
        .then(printer => {

            console.log("Impresora encontrada:", printer);

            var config = qz.configs.create(printer);

            var CUT = "\x1D\x56\x00"; // Corte total

            var ticket =
                  "   VENDING BOX TEST   \n"
                + "------------------------\n"
                + "Fecha: " + new Date().toLocaleString() + "\n"
                + "Cajero: PRUEBA\n"
                + "------------------------\n"
                + "Producto A        $10.00\n"
                + "Producto B        $15.00\n"
                + "Producto C         $8.00\n"
                + "------------------------\n"
                + "TOTAL             $33.00\n"
                + "------------------------\n"
                + "   GRACIAS POR SU COMPRA\n"
                + "        *** OK ***      \n\n\n\n"
                + CUT;

            var data = [{
                type: 'raw',
                format: 'plain',
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
