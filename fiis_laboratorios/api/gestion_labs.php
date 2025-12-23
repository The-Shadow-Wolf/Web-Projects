<?php
// /api/gestion_labs.php
// API para HU-06: CRUD de Laboratorios y Equipos

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
    
    if (!isset($data['accion'])) {
        $response['message'] = 'Acción no definida.';
        echo json_encode($response); exit;
    }

    // --- ACCIÓN: CREAR LABORATORIO ---
    if ($data['accion'] === 'crear_lab') {
        $nombre = $data['nombre'];
        $ubicacion = $data['ubicacion'];
        $capacidad = (int)$data['capacidad']; // Cantidad de PCs a generar

        // 1. Crear el Laboratorio
        $stmt = $conn->prepare("INSERT INTO laboratorios (nombre_lab, ubicacion, capacidad_total) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $nombre, $ubicacion, $capacidad);
        
        if ($stmt->execute()) {
            $lab_id = $stmt->insert_id; // Obtenemos el ID generado
            
            // 2. Generar las PCs automáticamente
            $sql_pcs = "INSERT INTO equipos (laboratorio_id, numero_equipo, estado_equipo) VALUES ";
            $values = [];
            
            for ($i = 1; $i <= $capacidad; $i++) {
                $values[] = "($lab_id, $i, 'disponible')";
            }
            
            if (!empty($values)) {
                $sql_pcs .= implode(", ", $values);
                $conn->query($sql_pcs);
            }

            $response['success'] = true;
            $response['message'] = 'Laboratorio y ' . $capacidad . ' equipos creados con éxito.';
        } else {
            $response['message'] = 'Error al crear laboratorio: ' . $stmt->error;
        }
        $stmt->close();
    }

    // --- ACCIÓN: ELIMINAR LABORATORIO ---
    elseif ($data['accion'] === 'eliminar_lab') {
        $lab_id = $data['laboratorio_id'];
        
        // La restricción de clave foránea en la BD debería eliminar los equipos y reservas en cascada
        // si se configuró así. Si no, esto fallará si hay reservas.
        // Por seguridad, intentamos borrar.
        $stmt = $conn->prepare("DELETE FROM laboratorios WHERE laboratorio_id = ?");
        $stmt->bind_param("i", $lab_id);
        
        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Laboratorio eliminado.';
        } else {
            $response['message'] = 'No se puede eliminar (posiblemente tiene reservas asociadas).';
        }
        $stmt->close();
    }

    // --- ACCIÓN: CAMBIAR ESTADO DE EQUIPO (Mantenimiento) ---
    elseif ($data['accion'] === 'estado_equipo') {
        $equipo_id = $data['equipo_id'];
        $nuevo_estado = $data['estado']; // 'disponible' o 'en_mantenimiento'

        $stmt = $conn->prepare("UPDATE equipos SET estado_equipo = ? WHERE equipo_id = ?");
        $stmt->bind_param("si", $nuevo_estado, $equipo_id);
        
        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Estado del equipo actualizado.';
        } else {
            $response['message'] = 'Error al actualizar equipo.';
        }
        $stmt->close();
    }
    
    else {
        $response['message'] = 'Acción no válida.';
    }
}

$conn->close();
echo json_encode($response);
?>