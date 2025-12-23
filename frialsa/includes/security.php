<?php
session_start();

// Si no existe la variable de sesión 'id_usuario', es que no se ha logueado
if (!isset($_SESSION['id_usuario'])) {
    header("Location: login.php");
    exit();
}
?>