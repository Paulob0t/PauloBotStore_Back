USE colegos_vending;

-- Insertar registro de prueba con UUID
INSERT INTO sincronizacion_log (uuid, tabla, accion, id_registro, datos, origen, sincronizado) 
VALUES (UUID(), 'productos_test', 'INSERT', 1, '{"test": true}', 'VALIDACION', 0);

-- Ver el registro insertado
SELECT id_sync, uuid, tabla, accion, origen, fecha_sync 
FROM sincronizacion_log 
WHERE tabla = 'productos_test' 
ORDER BY id_sync DESC 
LIMIT 1;
