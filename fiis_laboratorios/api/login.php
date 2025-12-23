<?php
// /api/login.php
// VERSIÓN CORREGIDA PARA TU ESTRUCTURA

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config/database.php';

$response = ['success' => false, 'message' => 'Credenciales incorrectas'];

// Manejo de preflight request (CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Leer JSON de entrada
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validar que lleguen los datos
    if (!isset($input['codigo']) || !isset($input['password'])) {
        echo json_encode(['success' => false, 'message' => 'Faltan datos']);
        exit;
    }

    $codigo = $input['codigo'];
    $password = $input['password'];

    // 1. BUSCAR USUARIO POR CÓDIGO
    // Nota: Seleccionamos explícitamente 'password_hash' que es como se llama tu columna
    $stmt = $conn->prepare("SELECT usuario_id, codigo_unac, nombres, apellidos, password_hash, rol_id FROM usuarios WHERE codigo_unac = ? AND estado = 'activo'");
    $stmt->bind_param("s", $codigo);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $usuario = $result->fetch_assoc();
        
        // 2. VERIFICAR CONTRASEÑA (Usando password_verify con el hash de la BD)
        if (password_verify($password, $usuario['password_hash'])) {
            
            // ¡ÉXITO!
            $response['success'] = true;
            $response['message'] = 'Login exitoso';
            
// --- AQUÍ ESTÁ LA CLAVE ---
    $response['data'] = [
        'usuario_id' => $usuario['usuario_id'],
        'codigo' => $usuario['codigo_unac'],
        // Concatenamos nombres y apellidos para enviarlo limpio al frontend
        'nombre' => $usuario['nombres'] . ' ' . $usuario['apellidos'], 
        'rol_id' => $usuario['rol_id']
    ];


        } else {
            $response['message'] = 'Contraseña incorrecta.';
        }
    } else {
        $response['message'] = 'Usuario no encontrado o inactivo.';
    }
    $stmt->close();
}

$conn->close();
echo json_encode($response);
?>