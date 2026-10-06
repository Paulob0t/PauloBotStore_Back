<?php
// login.php
header('Content-Type: application/json');
include 'conn.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user = $_POST['usuario'];
    $pass = $_POST['password'];

    // Buscamos al usuario por nombre de usuario y contraseña
    $sql = "SELECT * FROM usuarios WHERE username = '$user' AND password = '$pass' LIMIT 1";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        echo json_encode([
            "res" => "success",
            "msg" => "Bienvenido " . $row['nombre'],
            "usuario" => $row['username']
        ]);
    } else {
        echo json_encode(["res" => "error", "msg" => "Usuario o contraseña incorrectos"]);
    }
} else {
    echo json_encode(["res" => "error", "msg" => "Metodo no permitido"]);
}
?>