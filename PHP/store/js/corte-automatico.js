/**
 * Sistema de Corte Automático con AJAX
 * Se ejecuta automáticamente en el frontend cada minuto
 * Compatible con cualquier página del sistema
 */

(function() {
    'use strict';
    
    // Configuración
    const CONFIG = {
        checkInterval: 60000, // Verificar cada 60 segundos
        endpoint: 'verificar_cierre_programado.php', // Cambiado a verificar por tiempo
        debug: false // Cambiar a true para ver mensajes en consola
    };
    
    /**
     * Función para verificar si hay que ejecutar un corte
     */
    function verificarCorteAutomatico() {
        fetch(CONFIG.endpoint, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
            },
            cache: 'no-cache'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                
                // Opcional: Mostrar notificación al usuario
                if ('Notification' in window && Notification.permission === 'granted') {
                    new Notification('Corte Automático', {
                        body: data.mensaje,
                        icon: '/images/icon.png'
                    });
                }
                
                // Si estás en la página de cortes, recargar datos
                if (typeof actualizarCortes === 'function') {
                    actualizarCortes();
                }
                
                // Mostrar alerta visual al usuario
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'info',
                        title: 'Caja Cerrada Automáticamente',
                        text: 'La caja se cerró porque llegó la hora programada',
                        confirmButtonText: 'Entendido'
                    });
                } else {
                    alert('⏰ La caja se cerró automáticamente porque llegó la hora programada');
                }
            }
        })
        .catch(error => {
            if (CONFIG.debug) {
                console.error('[Corte Auto] Error:', error);
            }
        });
    }
    
    /**
     * Pedir permisos para notificaciones (opcional)
     */
    function solicitarPermisosNotificacion() {
        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission();
        }
    }
    
    /**
     * Iniciar el sistema de verificación automática
     */
    function iniciar() {
        // console.log('🤖 Sistema de Corte Automático iniciado');
        
        // Verificar inmediatamente al cargar
        setTimeout(verificarCorteAutomatico, 2000);
        
        // Verificar cada minuto
        setInterval(verificarCorteAutomatico, CONFIG.checkInterval);
        
        // Solicitar permisos de notificación
        solicitarPermisosNotificacion();
    }
    
    // Iniciar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', iniciar);
    } else {
        iniciar();
    }
    
})();
