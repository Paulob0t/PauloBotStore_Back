<?php

$dbhost = 'localhost';
$dbuser = 'colegos_admin';
$dbpass = 'l7+]GYHEjx;q}xlC';
$dbname = 'colegos_Ecosystem';

$conn = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname);

if (!$conn) {
    die('no hay conexion: ' . mysqli_connect_error());
}

if (!mysqli_set_charset($conn, 'utf8mb4')) {
    error_log('debug_bd/conn.php: No se pudo definir utf8mb4 - ' . mysqli_error($conn));
}