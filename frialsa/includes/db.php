<?php
// includes/db.php
$host = 'localhost';
$user = 'root';      // Usuario por defecto de XAMPP
$password = '';      // Contraseña por defecto (vacía)
$database = 'almacen'; // Asegúrate que este sea el nombre exacto en phpMyAdmin

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Error de conexión fatal: " . $conn->connect_error);
}

// Esto permite que las ñ y tildes se vean bien
$conn->set_charset("utf8");
?>