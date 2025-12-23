<?php
// /api/bloqueos.php
// VERSIÓN 2.0 - Con función ELIMINAR

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config/database.php';

$response = ['success' => false];
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// --- LÓGICA GET (Listar) ---
if ($method === 'GET') {
    $sql = "
        SELECT 
            b.bloqueo_id, b.fecha_inicio, b.fecha_fin, b.motivo,
            l.nombre_lab
        FROM bloqueoshorario b
        JOIN laboratorios l ON b.laboratorio_id = l.laboratorio_id
        WHERE b.fecha_fin >= CURDATE()
        ORDER BY b.fecha_inicio
    ";
    
    $result = $conn->query($sql);
    
    if ($result) {
        $bloqueos = [];
        while ($row = $result->fetch_assoc()) {
            $bloqueos[] = $row;
        }
        $response['success'] = true;
        $response['data'] = $bloqueos;
    } else {
        $response['message'] = 'Error en la consulta GET: ' . $conn->error;
    }
}

// --- LÓGICA POST (Crear y Eliminar) ---
if ($method === 'POST') {
    
    $data = json_decode(file_get_contents('php://input'), true);

    // RAMA 1: ELIMINAR BLOQUEO (¡NUEVO!)
    if (isset($data['accion']) && $data['accion'] === 'eliminar') {
        
        if (!isset($data['bloqueo_id'])) {
            $response['message'] = 'Falta ID para eliminar.';
        } else {
            // Solo permitimos eliminar si eres admin (verificamos si quieres, por ahora confiamos en el frontend)
            $stmt = $conn->prepare("DELETE FROM bloqueoshorario WHERE bloqueo_id = ?");
            $stmt->bind_param("i", $data['bloqueo_id']);
            
            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'Bloqueo eliminado correctamente.';
            } else {
                $response['message'] = 'Error al eliminar: ' . $stmt->error;
            }
            $stmt->close();
        }

    // RAMA 2: CREAR BLOQUEO
    } else {
        // Validamos datos mínimos
        if (!isset($data['laboratorio_id']) || !isset($data['fecha_inicio']) || !isset($data['motivo'])) {
            $response['message'] = 'Error: Faltan datos para crear el bloqueo.';
        } else {
            $laboratorio_id = $data['laboratorio_id'];
            $admin_id = $data['admin_id']; 
            $fecha_inicio = $data['fecha_inicio'];
            $fecha_fin = $data['fecha_fin'];
            $motivo = $data['motivo'];

            $stmt_insert = $conn->prepare("
                INSERT INTO bloqueoshorario (laboratorio_id, admin_id, fecha_inicio, fecha_fin, motivo)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt_insert->bind_param("iisss", $laboratorio_id, $admin_id, $fecha_inicio, $fecha_fin, $motivo);
            
            if ($stmt_insert->execute()) {
                $response['success'] = true;
                $response['message'] = '¡Bloqueo de horario creado con éxito!';
            } else {
                $response['message'] = 'Error del servidor: ' . $stmt_insert->error;
            }
            $stmt_insert->close();
        }
    }
}

$conn->close();
echo json_encode($response);
?>