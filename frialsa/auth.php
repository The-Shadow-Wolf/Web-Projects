<?php
session_start(); // Iniciar el manejo de sesiones
include 'includes/db.php';

$correo = $_POST['correo'];
$password = $_POST['password'];

// Consultar usuario por correo
// NOTA: En producción real, usa password_hash() y password_verify(). 
// Aquí usaremos comparación directa porque en la BD pusimos 'hash123' como texto plano para la demo.

$sql = "SELECT * FROM usuarios WHERE correo = '$correo' AND password_hash = '$password' AND activo = 1";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    
    // ¡CREDENCIALES CORRECTAS!
    // Guardamos datos clave en la sesión del navegador
    $_SESSION['id_usuario'] = $row['id_usuario'];
    $_SESSION['nombre'] = $row['nombre'];
    $_SESSION['rol'] = $row['rol'];
    
    // Redirigir al Dashboard
    header("Location: index.php");
} else {
    // ERROR: Devolver al login con alerta
    header("Location: login.php?error=1");
}
?>