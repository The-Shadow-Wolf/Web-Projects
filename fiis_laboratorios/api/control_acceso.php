<?php
// /api/control_acceso.php
// API para registrar el INGRESO y SALIDA de estudiantes (HU-05)

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config/database.php';

$response = ['success' => false];
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validamos que tengamos la acción y el ID
    if (!isset($data['accion']) || !isset($data['reserva_id'])) {
        $response['message'] = 'Error: Faltan datos (accion o reserva_id).';
        $conn->close();
        echo json_encode($response);
        exit;
    }

    $accion = $data['accion'];
    $reserva_id = $data['reserva_id'];
    $hora_actual = date('Y-m-d H:i:s'); // La hora actual del servidor

    $sql = "";
    
    // 1. Lógica para registrar INGRESO
    if ($accion === 'ingreso') {
        // Solo actualiza si la hora de ingreso AÚN NO ha sido registrada
        $sql = "UPDATE Reservas SET hora_ingreso_real = ? WHERE reserva_id = ? AND hora_ingreso_real IS NULL";
    } 
    // 2. Lógica para registrar SALIDA
    elseif ($accion === 'salida') {
        // Solo actualiza si la hora de ingreso SÍ fue registrada y la salida NO
        $sql = "UPDATE Reservas SET hora_salida_real = ? WHERE reserva_id = ? AND hora_ingreso_real IS NOT NULL AND hora_salida_real IS NULL";
    } 
    else {
        $response['message'] = 'Error: Acción no reconocida.';
        $conn->close();
        echo json_encode($response);
        exit;
    }

    // 3. Ejecutar la consulta
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $hora_actual, $reserva_id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $response['success'] = true;
            $response['message'] = 'Acción registrada con éxito a las ' . $hora_actual;
        } else {
            // Esto pasa si, por ej., intentan registrar INGRESO dos veces
            $response['message'] = 'Error: La acción no se pudo registrar (ya estaba registrada o la secuencia es incorrecta).';
        }
    } else {
        $response['message'] = 'Error del servidor: ' . $stmt->error;
    }
    
    $stmt->close();
    
} else {
    $response['message'] = 'Error: Método no permitido (solo POST).';
}

$conn->close();
echo json_encode($response);
?>