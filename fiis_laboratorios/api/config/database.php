<?php
// /api/config/database.php

// 1. FORZAR ZONA HORARIA (Perú)
date_default_timezone_set('America/Lima');

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'db_fiis_laboratorios'); // Asegúrate que este nombre sea correcto

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Error de conexión: ' . $conn->connect_error]));
}
$conn->set_charset('utf8');