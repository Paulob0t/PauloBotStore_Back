<?php
/**
 * 🔍 DIAGNÓSTICO Y REPARACIÓN DE LOGIN
 * Este script verifica y repara problemas con el sistema de login
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 Diagnóstico del Sistema de Login</h1>";
echo "<hr>";

// 1️⃣ VERIFICAR CONEXIÓN A LA BASE DE DATOS
echo "<h2>1️⃣ Verificando conexión a la base de datos...</h2>";
require_once 'db_config_dual.php';

if (isset($conn) && $conn && !$conn->connect_error) {
    echo "✅ <strong>Conexión exitosa a la BD</strong><br>";
    echo "📊 Base de datos: <strong>" . (defined('USING_DB') ? USING_DB : 'Desconocida') . "</strong><br>";
    echo "<hr>";
} else {
    echo "❌ <strong>ERROR: No se pudo conectar a la base de datos</strong><br>";
    if (isset($conn)) {
        echo "Error: " . $conn->connect_error . "<br>";
    }
    echo "<hr>";
    echo "<h3>💡 Solución sugerida:</h3>";
    echo "<ol>";
    echo "<li>Verifica que XAMPP esté corriendo (Apache y MySQL)</li>";
    echo "<li>Abre phpMyAdmin: <a href='http://localhost/phpmyadmin' target='_blank'>http://localhost/phpmyadmin</a></li>";
    echo "<li>Verifica que exista la base de datos 'vending' o 'colegos_vending'</li>";
    echo "</ol>";
    exit;
}

// 2️⃣ VERIFICAR TABLA USUARIOS
echo "<h2>2️⃣ Verificando tabla 'usuarios'...</h2>";
$check_table = $conn->query("SHOW TABLES LIKE 'usuarios'");
if ($check_table && $check_table->num_rows > 0) {
    echo "✅ <strong>Tabla 'usuarios' existe</strong><br>";
    echo "<hr>";
} else {
    echo "❌ <strong>ERROR: La tabla 'usuarios' NO existe</strong><br>";
    echo "<hr>";
    echo "<h3>💡 Crear tabla usuarios:</h3>";
    echo "<pre>";
    echo "CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    correo VARCHAR(100) NOT NULL UNIQUE,
    contrasena VARCHAR(255) NOT NULL,
    tipo_usuario VARCHAR(50) DEFAULT 'empleado',
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);</pre>";
    echo "<br><button onclick=\"location.href='?crear_tabla=1'\">Crear tabla ahora</button>";
    
    if (isset($_GET['crear_tabla'])) {
        $sql = "CREATE TABLE IF NOT EXISTS usuarios (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(100) NOT NULL,
            correo VARCHAR(100) NOT NULL UNIQUE,
            contrasena VARCHAR(255) NOT NULL,
            tipo_usuario VARCHAR(50) DEFAULT 'empleado',
            fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        if ($conn->query($sql)) {
            echo "<br>✅ Tabla creada exitosamente. <a href='diagnostico_login.php'>Recargar página</a>";
        } else {
            echo "<br>❌ Error al crear tabla: " . $conn->error;
        }
    }
    exit;
}

// 3️⃣ VERIFICAR ESTRUCTURA DE LA TABLA
echo "<h2>3️⃣ Estructura de la tabla 'usuarios':</h2>";
$columns_result = $conn->query("SHOW COLUMNS FROM usuarios");
$columns = [];
if ($columns_result) {
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse; margin-bottom: 20px;'>";
    echo "<tr style='background: #e0e0e0;'><th>Columna</th><th>Tipo</th><th>Null</th><th>Default</th></tr>";
    while ($col = $columns_result->fetch_assoc()) {
        $columns[] = $col['Field'];
        echo "<tr>";
        echo "<td><strong>" . $col['Field'] . "</strong></td>";
        echo "<td>" . $col['Type'] . "</td>";
        echo "<td>" . $col['Null'] . "</td>";
        echo "<td>" . ($col['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Detectar qué columnas existen
$has_fecha = in_array('fecha_registro', $columns) || in_array('created_at', $columns);
$has_tipo = in_array('tipo_usuario', $columns);
$has_nombre = in_array('nombre', $columns);

// 4️⃣ LISTAR USUARIOS EXISTENTES
echo "<h2>4️⃣ Usuarios en la base de datos:</h2>";

// Construir query dinámicamente según columnas disponibles
$select_fields = ['id'];
if ($has_nombre) $select_fields[] = 'nombre';
$select_fields[] = 'correo';
if ($has_tipo) $select_fields[] = 'tipo_usuario';
if (in_array('fecha_registro', $columns)) $select_fields[] = 'fecha_registro';
if (in_array('created_at', $columns)) $select_fields[] = 'created_at';

$query = "SELECT " . implode(', ', $select_fields) . " FROM usuarios ORDER BY id";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr style='background: #FFD93D;'>";
    echo "<th>ID</th>";
    if ($has_nombre) echo "<th>Nombre</th>";
    echo "<th>Correo</th>";
    if ($has_tipo) echo "<th>Tipo</th>";
    if ($has_fecha) echo "<th>Fecha</th>";
    echo "</tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        if ($has_nombre) echo "<td>" . ($row['nombre'] ?? 'N/A') . "</td>";
        echo "<td><strong>" . $row['correo'] . "</strong></td>";
        if ($has_tipo) echo "<td>" . ($row['tipo_usuario'] ?? 'N/A') . "</td>";
        if ($has_fecha) {
            $fecha = $row['fecha_registro'] ?? $row['created_at'] ?? 'N/A';
            echo "<td>" . $fecha . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
    echo "<br>";
} else {
    echo "⚠️ <strong>No hay usuarios registrados en la base de datos</strong><br>";
}
echo "<hr>";

// 5️⃣ CREAR/RESETEAR USUARIO ADMIN
echo "<h2>5️⃣ Crear o resetear usuario administrador:</h2>";
echo "<form method='POST' style='border: 2px solid #FFD93D; padding: 20px; border-radius: 10px; max-width: 500px;'>";
echo "<h3>🔐 Crear nuevo usuario o resetear contraseña</h3>";
echo "<label><strong>Nombre:</strong></label><br>";
echo "<input type='text' name='nombre' value='Administrador' required style='width: 100%; padding: 8px; margin: 5px 0 15px 0;'><br>";

echo "<label><strong>Correo (para login):</strong></label><br>";
echo "<input type='email' name='correo' value='admin@vendingbox.com' required style='width: 100%; padding: 8px; margin: 5px 0 15px 0;'><br>";

echo "<label><strong>Contraseña:</strong></label><br>";
echo "<input type='text' name='nueva_contrasena' value='admin123' required style='width: 100%; padding: 8px; margin: 5px 0 15px 0;'><br>";

echo "<label><strong>Tipo de usuario:</strong></label><br>";
echo "<select name='tipo_usuario' style='width: 100%; padding: 8px; margin: 5px 0 15px 0;'>";
echo "<option value='admin'>Administrador</option>";
echo "<option value='empleado'>Empleado</option>";
echo "</select><br>";

echo "<button type='submit' name='crear_usuario' style='background: #FFD93D; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold;'>Crear/Actualizar Usuario</button>";
echo "</form>";
echo "<hr>";

// PROCESAR CREACIÓN/ACTUALIZACIÓN DE USUARIO
if (isset($_POST['crear_usuario'])) {
    $nombre = trim($_POST['nombre']);
    $correo = trim($_POST['correo']);
    $nueva_contrasena = $_POST['nueva_contrasena'];
    $tipo_usuario = $_POST['tipo_usuario'];
    
    // Hash de la contraseña
    $hash = password_hash($nueva_contrasena, PASSWORD_DEFAULT);
    
    // Verificar si el usuario ya existe
    $check = $conn->prepare("SELECT id FROM usuarios WHERE correo = ?");
    $check->bind_param('s', $correo);
    $check->execute();
    $check->store_result();
    
    if ($check->num_rows > 0) {
        // ACTUALIZAR usuario existente - adaptar según columnas disponibles
        if ($has_nombre && $has_tipo) {
            $update = $conn->prepare("UPDATE usuarios SET nombre = ?, contrasena = ?, tipo_usuario = ? WHERE correo = ?");
            $update->bind_param('ssss', $nombre, $hash, $tipo_usuario, $correo);
        } else if ($has_nombre) {
            $update = $conn->prepare("UPDATE usuarios SET nombre = ?, contrasena = ? WHERE correo = ?");
            $update->bind_param('sss', $nombre, $hash, $correo);
        } else {
            $update = $conn->prepare("UPDATE usuarios SET contrasena = ? WHERE correo = ?");
            $update->bind_param('ss', $hash, $correo);
        }
        
        if ($update->execute()) {
            echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
            echo "✅ <strong>Usuario actualizado exitosamente</strong><br>";
            echo "📧 Correo: <strong>$correo</strong><br>";
            echo "🔑 Contraseña: <strong>$nueva_contrasena</strong><br>";
            if ($has_tipo) echo "👤 Tipo: <strong>$tipo_usuario</strong><br>";
            echo "<br><a href='login.php' style='background: #FFD93D; padding: 10px 20px; text-decoration: none; color: black; border-radius: 5px; font-weight: bold;'>Ir al Login</a>";
            echo "</div>";
        } else {
            echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
            echo "❌ Error al actualizar: " . $conn->error;
            echo "</div>";
        }
        $update->close();
    } else {
        // CREAR nuevo usuario - adaptar según columnas disponibles
        if ($has_nombre && $has_tipo) {
            $insert = $conn->prepare("INSERT INTO usuarios (nombre, correo, contrasena, tipo_usuario) VALUES (?, ?, ?, ?)");
            $insert->bind_param('ssss', $nombre, $correo, $hash, $tipo_usuario);
        } else if ($has_nombre) {
            $insert = $conn->prepare("INSERT INTO usuarios (nombre, correo, contrasena) VALUES (?, ?, ?)");
            $insert->bind_param('sss', $nombre, $correo, $hash);
        } else {
            $insert = $conn->prepare("INSERT INTO usuarios (correo, contrasena) VALUES (?, ?)");
            $insert->bind_param('ss', $correo, $hash);
        }
        
        if ($insert->execute()) {
            echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
            echo "✅ <strong>Usuario creado exitosamente</strong><br>";
            echo "📧 Correo: <strong>$correo</strong><br>";
            echo "🔑 Contraseña: <strong>$nueva_contrasena</strong><br>";
            if ($has_tipo) echo "👤 Tipo: <strong>$tipo_usuario</strong><br>";
            echo "<br><a href='login.php' style='background: #FFD93D; padding: 10px 20px; text-decoration: none; color: black; border-radius: 5px; font-weight: bold;'>Ir al Login</a>";
            echo "</div>";
        } else {
            echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
            echo "❌ Error al crear: " . $conn->error;
            echo "</div>";
        }
        $insert->close();
    }
    $check->close();
}

// 6️⃣ PROBAR LOGIN
echo "<h2>6️⃣ Probar credenciales de login:</h2>";
echo "<form method='POST' style='border: 2px solid #4CAF50; padding: 20px; border-radius: 10px; max-width: 500px;'>";
echo "<h3>🧪 Probar Login</h3>";
echo "<label><strong>Correo:</strong></label><br>";
echo "<input type='email' name='test_correo' required style='width: 100%; padding: 8px; margin: 5px 0 15px 0;'><br>";

echo "<label><strong>Contraseña:</strong></label><br>";
echo "<input type='password' name='test_contrasena' required style='width: 100%; padding: 8px; margin: 5px 0 15px 0;'><br>";

echo "<button type='submit' name='probar_login' style='background: #4CAF50; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold;'>Probar Login</button>";
echo "</form>";

// PROCESAR PRUEBA DE LOGIN
if (isset($_POST['probar_login'])) {
    $test_correo = trim($_POST['test_correo']);
    $test_contrasena = $_POST['test_contrasena'];
    
    // Construir query dinámicamente
    $select_test = ['id', 'contrasena'];
    if ($has_tipo) $select_test[] = 'tipo_usuario';
    if ($has_nombre) $select_test[] = 'nombre';
    
    $query_test = "SELECT " . implode(', ', $select_test) . " FROM usuarios WHERE correo = ?";
    $stmt = $conn->prepare($query_test);
    $stmt->bind_param('s', $test_correo);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows === 1) {
        // Bind dinámico
        if ($has_nombre && $has_tipo) {
            $stmt->bind_result($id, $hash, $tipo_usuario, $nombre);
        } else if ($has_tipo) {
            $stmt->bind_result($id, $hash, $tipo_usuario);
            $nombre = 'Usuario';
        } else if ($has_nombre) {
            $stmt->bind_result($id, $hash, $nombre);
            $tipo_usuario = 'N/A';
        } else {
            $stmt->bind_result($id, $hash);
            $nombre = 'Usuario';
            $tipo_usuario = 'N/A';
        }
        $stmt->fetch();
        
        if (password_verify($test_contrasena, $hash)) {
            echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
            echo "✅ <strong>¡CREDENCIALES CORRECTAS!</strong><br>";
            echo "👤 Usuario: <strong>$nombre</strong><br>";
            echo "📧 Correo: <strong>$test_correo</strong><br>";
            if ($has_tipo) echo "🎫 Tipo: <strong>$tipo_usuario</strong><br>";
            echo "<br>Ya puedes usar estas credenciales para iniciar sesión.";
            echo "</div>";
        } else {
            echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
            echo "❌ <strong>CONTRASEÑA INCORRECTA</strong><br>";
            echo "El correo existe pero la contraseña no coincide.<br>";
            echo "Usa el formulario de arriba para resetear la contraseña.";
            echo "</div>";
        }
    } else {
        echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "⚠️ <strong>CORREO NO ENCONTRADO</strong><br>";
        echo "No existe un usuario con el correo: <strong>$test_correo</strong><br>";
        echo "Usa el formulario de arriba para crear el usuario.";
        echo "</div>";
    }
    $stmt->close();
}

$conn->close();
?>

<style>
    body {
        font-family: Arial, sans-serif;
        margin: 20px;
        background: #f5f5f5;
    }
    h1, h2, h3 {
        color: #333;
    }
    table {
        background: white;
        width: 100%;
        max-width: 800px;
    }
    th {
        color: black;
    }
    input, select {
        border: 1px solid #ddd;
        border-radius: 4px;
    }
</style>
