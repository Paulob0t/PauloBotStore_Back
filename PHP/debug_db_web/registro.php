<?php
// registro.php
header('Content-Type: application/json');
include 'conn.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Recibimos los datos del Android (POST)
    $nombre = $_POST['nombre'];
    $email  = $_POST['email'];
    $user   = $_POST['usuario'];
    $pass   = $_POST['password'];

    // Primero revisamos si el usuario ya existe
    $check = "SELECT id FROM usuarios WHERE username = '$user' OR email = '$email'";
    $resCheck = mysqli_query($conn, $check);

    if (mysqli_num_rows($resCheck) > 0) {
        echo json_encode(["res" => "error", "msg" => "El usuario o email ya existe vlo"]);
    } else {
        // Insertamos el nuevo usuario
        $sql = "INSERT INTO usuarios (nombre, email, username, password) VALUES ('$nombre', '$email', '$user', '$pass')";
        
        if (mysqli_query($conn, $sql)) {
            echo json_encode(["res" => "success", "msg" => "Usuario creado con exito"]);
        } else {
            echo json_encode(["res" => "error", "msg" => "Error al registrar: " . mysqli_error($conn)]);
        }
    }
} else {
    echo json_encode(["res" => "error", "msg" => "Metodo no permitido"]);
}
?>