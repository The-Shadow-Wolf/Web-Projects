<?php
session_start();
session_destroy(); // Destruye todos los datos de sesión (borra al usuario)
header("Location: login.php"); // Te manda de vuelta a la portada de Login
exit();
?>